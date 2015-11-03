<?php
# Copyright 2015 Géant Association
#
# Licensed under the GÉANT Standard Open Source (the "License")
# you may not use this file except in compliance with the License.
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# This software was developed by Consortium GARR. The research leading to
# these results has received funding from the European Community¹s Seventh
# Framework Programme (FP7/2007-2013) under grant agreement nº 238875
# (GÉANT).

require_once 'StoreResultsDB.php';
require_once 'GetDataFromJson.php';
    
class IdpChecks {
    protected $storeResultsDb;
    protected $getDataFromJson;
    protected $spEntityIDs = array();
    protected $spACSurls = array();
    protected $parallel;
    protected $checkHistory;
    protected $verbose;

    public function __construct() {
        $confArray = parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
        if(empty($confArray)){
            throw new Exception("'check_script/properties.ini.php' is missing or the 'mccs.php' script is running under the wrong directory.");

        }

        $this->storeResultsDb = new StoreResultsDb();
        $this->getDataFromJson = new GetDataFromJson();
        $this->parallel = intval($confArray['check_script']['parallel']);
        $this->checkHistory = intval($confArray['check_script']['check_history']);
        $this->verbose = $confArray['check_script']['verbose'];

        $regexp = "/^sp_\d/";
        $confArrayKeys = array_keys($confArray);
        $spsKeys[] = preg_grep($regexp, $confArrayKeys);
        foreach (array_values($spsKeys) as $value) {
            foreach(array_values($value) as $val) {
                $this->spEntityIDs[] = $confArray[$val]['entityID'];
                $this->spACSurls[] = $confArray[$val]['acs_url'];
            }
        }
 
        if (count($this->spEntityIDs) != count($this->spACSurls)) {
            throw new Exception("Configuration error. Please check properties.ini.");
        }
    }

    function executeAllChecks() {
        $fedsList = $this->getDataFromJson->obtainFederationsList();
        $this->storeResultsDb->storeFedsIntoDb($fedsList);

        $this->storeResultsDb->cleanOldEntityChecks();
        $idpList = $this->getDataFromJson->obtainIdPList();
        $this->storeResultsDb->updateIgnoredEntities();

        $count = 1;
        for ($i = 0; $i < $this->parallel; $i++) {
            $pid = pcntl_fork();
            if (!$pid) {
                //In child
                $this->storeResultsDb->resetDbConnection();
                print "Executing check for " . $idpList[$count]['entityID'] . "\n";
                $this->executeIdPchecks($idpList[$count]);
                return false;
            }
            $count++;
        }

        while (pcntl_waitpid(0, $status) != -1) { 
            $status = pcntl_wexitstatus($status);
            if ($count <= count($idpList)) {
                $pid = pcntl_fork();
                if (!$pid) {
                    //In child
                    $this->storeResultsDb->resetDbConnection();
                    print "Executing check for " . $idpList[$count]['entityID'] . "\n";
                    $this->executeIdPchecks($idpList[$count]);
                    return false;
                }
                $count++;
            } 
        }

        // End of all cycles
        $this->storeResultsDb->resetDbConnection();
        $this->storeResultsDb->deleteOldEntities();
        $this->storeResultsDb->storeFederationStats();
        return true;
    }

    function executeIdPchecks($idp) {
        list($ignoreEntity, $previousStatus) = $this->storeResultsDb->getEntityPreviousStatus($idp);

        if ($ignoreEntity) {
            // update EntityDescriptors
            $this->storeResultsDb->updateDisabledEntities($idp['entityID']);
            print "Entity " . $idp['entityID'] . " ignored.\n";
            return;
        }

        $reason = '1 - OK';
        $lastCheckHistory = $this->checkHistory - 1;

        for ($i = 0; $i < count($this->spEntityIDs); $i++) {
            $result = $this->checkIdp($idp['entityID'], $idp['SingleSignOnService'], $this->spEntityIDs[$i], $this->spACSurls[$i]);
            $status = array_key_exists('status', $result) ? $result['status'] : -1;
            $reason = $result['message'] ? $result['message'] : '0 - UNKNOWN-Error';

            $this->storeResultsDb->insertCheck($idp['entityID'], $this->spEntityIDs[$i], $idp['SingleSignOnService'], $this->spACSurls[$i], $result['html'], $result['http_code'], $reason, $lastCheckHistory);
        }

        $this->storeResultsDb->updateEntityLastCheckStatus($reason, $previousStatus, $idp['entityID']);

        if ($status === 0) {
            print "The IdP ".$idp['entityID']." consumed metadata correctly\n";
        } else {
            print "The IdP ".$idp['entityID']." did NOT consume metadata correctly.\n\n";
            print "Reason: " . $reason . "\n";
            print "Messages: " . $result['error'] . "\n\n";
        }
    }

    private function checkIdp($idpEntityId, $httpRedirectServiceLocation, $spEntityID, $spACSurl) {
        date_default_timezone_set('UTC');
        $date = date('Y-m-d\TH:i:s\Z');
        $id = md5($date.rand(1, 1000000));
        $samlRequest = $this->getDataFromJson->generateSamlRequest($spACSurl, $httpRedirectServiceLocation, $id, $date, $spEntityID);
        $url = $httpRedirectServiceLocation."?SAMLRequest=".$samlRequest;
        list($curlError, $info, $html) = $this->getUrlWithCurl($url);
        $error = '';
        $status = 0;
        $message = '1 - OK';
        if ($curlError !== false) {
            $status = 3;
            $message = '3 - CURL-Error';
            if ($this->verbose) {
                echo $idpEntityId . " Curl error: ".$curlError."\n";
            }
            $error = $curlError;
        } else if ($info['http_code'] != 200 && $info['http_code'] != 401) {
            $status = 3;
            $message = '3 - HTTP-Error';
            if ($this->verbose) {
                echo $idpEntityId . " Status code: ".$info['http_code']."\n";
            }
            $error = "Status code: ".$info['http_code'];
        } else {
            $patternUsername = '/<input[\s]+[^>]*((type=\s*[\'"](text|email)[\'"]|user)|(name=\s*[\'"](name)[\'"]))[^>]*>/im';
            $patternPassword = '/<input[\s]+[^>]*(type=\s*[\'"]password[\'"]|password)[^>]*>/im';
            if (!preg_match($patternUsername, $html) || !preg_match($patternPassword, $html)) {
                $status = 2;
                $message = '2 - FORM-Invalid';
                if ($this->verbose) {
                    echo $idpEntityId . " Did not find input for username or password.\n";
                }
                $error = "Did not find input for username or password.";
            }
        }
        return array(
            'status' => $status,
            'message' => $message,
            'http_code' => $info['http_code'],
            'error' => $error,
            'html' => ($html) ? $html : "",
        );
    }

    private function getUrlWithCurl($url) {
        $curl = curl_init($url);

        $html = false;
        $curlError = false;
        for ($vers = 0; $vers <= 6; $vers++) {
            /* One of CURL_SSLVERSION_DEFAULT (0),
                      CURL_SSLVERSION_TLSv1   (1),
                      CURL_SSLVERSION_SSLv2   (2),
                      CURL_SSLVERSION_SSLv3   (3),
                      CURL_SSLVERSION_TLSv1_0 (4),
                      CURL_SSLVERSION_TLSv1_1 (5) 
                   or CURL_SSLVERSION_TLSv1_2 (6).
             */
            if ($vers === 2) {
                //Disable SSLv2
                continue;
            }

            if ($html === false) {
                curl_setopt_array($curl, array(
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_FRESH_CONNECT => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_COOKIEJAR => "/dev/null",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 90,
                    CURLOPT_CONNECTTIMEOUT => 90,
                    CURLOPT_SSLVERSION => $vers,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:36.0) Gecko/20100101 Firefox/36.0',
                ));
                $html = curl_exec($curl);

                if ($html === false) {
                    $curlError = curl_error($curl);
                } else {
                    $curlError = false;
                }
            }
        }
   
        $info = curl_getinfo($curl);

        $html = $this->cleanUtf8Curl($html, $curl);
        $html = preg_replace('/\s*$^\s*/m', "\n", $html);
        $html = preg_replace('/[ \t]+/', ' ', $html);
        $html = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $html);

        curl_close($curl);
        return array($curlError, $info, $html);
    }

    private function cleanUtf8Curl($html, $curl) {
        if (!is_string($html)) {
            return $html;
        }
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $charset = $this->obtainCharset($contentType, $html);
        /* Convert it if it is anything but UTF-8 */
        /* You can change "UTF-8"  to "UTF-8//IGNORE" to 
        ignore conversion errors and still output something reasonable */
        if (isset($charset) && strtoupper($charset) != "UTF-8") {
            $html = iconv($charset, 'UTF-8', $html);
        }
        return $html;
    }

    private function obtainCharset($contentType, $html) {
        $charset = NULL;
        /* 1: HTTP Content-Type: header */
        preg_match('@([\w/+]+)(;\s*charset=(\S+))?@i', $contentType, $matches);
        if (!$charset && isset($matches[3])) {
            $charset = $matches[3];
        }
        /* 2: <meta> element in the page */
        preg_match('@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s*charset=([^\s"]+))?@i', $html, $matches);
        if (!$charset && isset($matches[3])) {
            $charset = $matches[3];
        }
        /* 3: <xml> element in the page */
        preg_match('@<\?xml.+encoding="([^\s"]+)@si', $html, $matches);
        if (!$charset && isset($matches[1])) {
           $charset = $matches[1];
        }
        /* 4: PHP's heuristic detection */
        $encoding = mb_detect_encoding($html);
        if (!$charset && $encoding) {
            $charset = $encoding;
        }
        /* 5: Default for HTML */
        if (!$charset) {
            $charset = "ISO 8859-1";
        }
        return $charset;
    }
}