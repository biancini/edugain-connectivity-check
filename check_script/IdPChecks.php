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

require_once '../utils/QueryBuilder.php';
require_once '../utils/DBManager.php';
require_once 'StoreResultsDB.php';
require_once 'GetDataFromJson.php';
    
class IdpChecks {
    protected $storeResultsDb;
    protected $getDataFromJson;

    public function __construct() {
        $this->storeResultsDb = new StoreResultsDb();
        $this->getDataFromJson = new GetDataFromJson();

        $this->spEntityIDs = array();
        $this->spACSurls = array();

        $regexp = "/^sp_\d/";
        $this->confArray = parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
        $this->confArrayKeys = array_keys($this->confArray);
        $this->spsKeys[] = preg_grep($regexp, $this->confArrayKeys);
        foreach ($this->spsKeys as $key => $value) {
            foreach($value as $sp => $val) {
                $this->spEntityIDs[] = $this->confArray[$val]['entityID'];
                $this->spACSurls[] = $this->confArray[$val]['acs_url'];
            }
        }
 
        $this->parallel = intval($this->confArray['check_script']['parallel']);
        $this->checkHistory = intval($this->confArray['check_script']['check_history']);

        global $verbose;
        $verbose = $this->confArray['check_script']['verbose'];

        if (count($this->spEntityIDs) != count($this->spACSurls)) {
            throw new Exception("Configuration error. Please check properties.ini.");
        }
    }

    function executeAllChecks() {
        $this->storeResultsDb->updateFederations();

        $this->storeResultsDb->cleanOldEntityChecks();
        $idpList = $this->getDataFromJson->obtainIdPList();
        $this->storeResultsDb->updateIgnoredEntities();

        $count = 1;
        for ($i = 0; $i < $this->parallel; $i++) {
            $pid = pcntl_fork();
            if (!$pid) {
                //In child
                print "Executing check for " . $idpList[$count]['entityID'] . "\n";
                $this->executeIdPchecks($idpList[$count], $this->spEntityIDs, $this->spACSurls, $this->checkHistory);
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
                    print "Executing check for " . $idpList[$count]['entityID'] . "\n";
                    $this->executeIdPchecks($idpList[$count], $this->spEntityIDs, $this->spACSurls, $this->checkHistory);
                    return false;
                }
                $count++;
            } 
        }

        $this->storeResultsDb->deleteOldEntities();
        $this->storeResultsDb->storeFederationStats();
        return true;
    }

    function executeIdPchecks($idp, $spEntityIDs, $spACSurls, $checkHistory = 2) {
        $dbManager = new DBManager();
        list($ignoreEntity, $previousStatus) = $this->getEntityPreviousStatus($dbManager, $idp);

        if ($ignoreEntity) {
            // update EntityDescriptors
            $query = new QueryBuilder();
            $query->setSql('UPDATE EntityDescriptors SET updated = 1, currentResult = NULL, previousResult = NULL WHERE entityID = ?');
            $query->addQueryParam($idp['entityID'], 's');
            $dbManager->executeStatement(false, $query);
            print "Entity " . $idp['entityID'] . " ignored.\n";
            return;
        }

        $reason = '1 - OK';
        $lastCheckHistory = $checkHistory - 1;
        $query = new QueryBuilder();

        for ($i = 0; $i < count($spEntityIDs); $i++) {
            $result = $this->checkIdp($idp['entityID'], $idp['SingleSignOnService'], $spEntityIDs[$i], $spACSurls[$i]);
            $status = array_key_exists('status', $result) ? $result['status'] : -1;
            $reason = array_key_exists('message', $result) ? $result['message'] : '0 - UNKNOWN-Error';

            $query = new QueryBuilder();
            $query->setSql('INSERT INTO EntityChecks (entityID, spEntityID, serviceLocation, acsUrls, checkHtml, httpStatusCode, checkResult, checkExec) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $query->addQueryParam($idp['entityID'], 's');
            $query->addQueryParam($spEntityIDs[$i], 's');
            $query->addQueryParam($idp['SingleSignOnService'], 's');
            $query->addQueryParam($spACSurls[$i], 's');
            $query->addQueryParam($result['html'], 's');
            $query->addQueryParam($result['http_code'], 'i');
            $query->addQueryParam($reason, 's');
            $query->addQueryParam($lastCheckHistory, 'i');
            $dbManager->executeStatement(false, $query);
        }

        $query = new QueryBuilder();
        $query->setSql("UPDATE EntityDescriptors SET lastCheck = ?, currentResult = ?, previousResult = ?, updated = 1 WHERE entityID = ?");
        $query->addQueryParam(date('Y-m-d\TH:i:s\Z'), 's');
        $query->addQueryParam($reason, 's');
        $query->addQueryParam($previousStatus, 's');
        $query->addQueryParam($idp['entityID'], 's');
        $result = $dbManager->executeStatement(false, $query);

        if ($status === 0) {
            print "The IdP ".$idp['entityID']." consumed metadata correctly\n";
        } else {
            print "The IdP ".$idp['entityID']." did NOT consume metadata correctly.\n\n";
            print "Reason: " . $reason . "\n";
            print "Messages: " . $result['error'] . "\n\n";
        }
    }

    function getEntityPreviousStatus($dbManager, $idp) {
        $query = new QueryBuilder();
        $query->setSql('SELECT * FROM EntityDescriptors WHERE entityID = ? ORDER BY lastCheck');
        $query->addQueryParam($idp['entityID']);
        $result = $dbManager->executeStatement(true, $query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $previousStatus = $row['currentResult'];
                $ignoreEntity = $row['ignoreEntity'];
            }
            return array($ignoreEntity, $previousStatus);
        } else {
            $query = new QueryBuilder();
            $query->setSql("INSERT INTO EntityDescriptors (entityID, registrationAuthority, displayName, technicalContacts, supportContacts, serviceLocation) VALUES (?, ?, ?, ?, ?, ?)");
            $query->addQueryParam($idp['entityID'], 's');
            $query->addQueryParam($idp['registrationAuthority'], 's');
            $query->addQueryParam($idp['displayName'], 's');
            $query->addQueryParam($idp['technicalContacts'], 's');
            $query->addQueryParam($idp['supportContacts'], 's');
            $query->addQueryParam($idp['SingleSignOnService'], 's');
            $result = $dbManager->executeStatement(false, $query);

            return array(false, NULL);
        }
    }

    function checkIdp($idpEntityId, $httpRedirectServiceLocation, $spEntityID, $spACSurl) {
        global $verbose;
   
        date_default_timezone_set('UTC');
        $date = date('Y-m-d\TH:i:s\Z');
        $id = md5($date.rand(1, 1000000));
        $samlRequest = $this->getDataFromJson->generateSamlRequest($spACSurl, $httpRedirectServiceLocation, $id, $date, $spEntityID);
        $url = $httpRedirectServiceLocation."?SAMLRequest=".$samlRequest;
        list($curlError, $info, $html) = $this->getDataFromJson->getUrlWithCurl($url);
        $error = '';
        $status = 0;
        $message = '1 - OK';
        if ($curlError !== false) {
            $status = 3;
            $message = '3 - CURL-Error';
            if ($verbose) {
                echo $idpEntityId . " Curl error: ".$curlError."\n";
            }
            $error = $curlError;
        } else if ($info['http_code'] != 200 && $info['http_code'] != 401) {
            $status = 3;
            $message = '3 - HTTP-Error';
            if ($verbose) {
                echo $idpEntityId . " Status code: ".$info['http_code']."\n";
            }
            $error = "Status code: ".$info['http_code'];
        } else {
            $patternUsername = '/<input[\s]+[^>]*((type=\s*[\'"](text|email)[\'"]|user)|(name=\s*[\'"](name)[\'"]))[^>]*>/im';
            $patternPassword = '/<input[\s]+[^>]*(type=\s*[\'"]password[\'"]|password)[^>]*>/im';
            if (!preg_match($patternUsername, $html) || !preg_match($patternPassword, $html)) {
                $status = 2;
                $message = '2 - FORM-Invalid';
                if ($verbose) {
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
}
