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

require_once '../utils/MailUtils.php';
require_once '../utils/QueryBuilder.php';
require_once '../utils/DBManager.php';

class Mailer {
    protected $mailer;
    protected $dbManager;
    protected $confArray;

    public function __construct() {
        $this->mailer = new MailUtils();
        $this->dbManager = new DBManager();
        $this->confArray = parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
        if (empty($this->confArray)){
            throw new Exception("'check_script/properties.ini.php' is missing or the 'mccs.php' script is running under the wrong directory.");
        }
    }

    function sendMailToFederation($fedData) {
        if (empty($fedData['emailAddress']) &&
             empty($fedData['sgDeputyEmail']) &&
             empty($fedData['sgDelegateEmail'])) {

            // Missing required information in fedData
            return;
        }

        if ($fedData['idp_form_invalid'] == 0 &&
            $fedData['idp_no_edugain_md'] == 0 &&
            $fedData['idp_curl_error'] == 0 &&
            $fedData['idp_http_error'] == 0 ) {

            // No issues on IdPs for federation
            return;
        }

        $emailProperties = $this->confArray['email'];
        $this->mailer->sendEmail($emailProperties, $fedData);
    }

    function collectIdPstatsForFed($queryResult) {
        $fedData = array();

        $fedData['idp_ok'] = 0;
        $fedData['idp_form_invalid'] = 0;
        $fedData['idp_no_edugain_md'] = 0;
        $fedData['idp_curl_error'] = 0;
        $fedData['idp_http_error'] = 0;
        $fedData['idp_disabled'] = 0;

        while ($curStat = $queryResult->fetch_assoc()) {
            switch($curStat['currentResult']){
                case "1 - OK":
                    $fedData['idp_ok'] = $curStat['numIdPs'];
                    break;

                case "2 - FORM-Invalid":
                    $fedData['idp_form_invalid'] = $curStat['numIdPs'];
                    break;

                case "2 - No-eduGAIN-Metadata":
                    $fedData['idp_no_edugain_md'] = $curStat['numIdPs'];
                    break;
                case "3 - CURL-Error":
                    $fedData['idp_curl_error'] = $curStat['numIdPs'];
                    break;

                case "3 - HTTP-Error":
                    $fedData['idp_http_error'] = $curStat['numIdPs'];
                    break;

                default:
                    $fedData['idp_disabled'] = $curStat['numIdPs'];
                    break;
            }
        }

        return $fedData;
    }

    function retrieveFedStat($fedData, $date) {
        $query = new QueryBuilder();

        $query->setSql("SELECT * FROM FederationStats WHERE registrationAuthority = ? AND checkDate = ?");
        $query->addQueryParam($fedData['regAuth'], 's');
        $query->addQueryParam($date, 's');

        $result = $this->dbManager->executeStatement(true, $query);
  
        return $this->collectIdPstatsForFed($result);
    }
  
    function notifyFederation($curFederation, $date) {

        $fedData = array();
        $fedData['name'] = $curFederation['federationName'];
        $fedData['regAuth'] = $curFederation['registrationAuthority'];
        $fedData['emailAddress'] = $curFederation['emailAddress'];
        $fedData['sgDeputyName'] = $curFederation['sgDeputyName'];
        $fedData['sgDeputySurname'] = $curFederation['sgDeputySurname'];
        $fedData['sgDeputyEmail'] = $curFederation['sgDeputyEmail'];
        $fedData['sgDelegateName'] = $curFederation['sgDelegateName'];
        $fedData['sgDelegateSurname'] = $curFederation['sgDelegateSurname'];
        $fedData['sgDelegateEmail'] = $curFederation['sgDelegateEmail'];

        $fedStats = $this->retrieveFedStat($fedData, $date);
        $mailParams = array_merge($fedData, $fedStats);

        $this->sendMailToFederation($mailParams);
    }
    
    function notifyAllFederations() {
        $date = date("Y-m-d");

        $query = new QueryBuilder();
        $query->setSql("SELECT * FROM Federations");
        $fedResult = $this->dbManager->executeStatement(true, $query);

        while ($curFederation = $fedResult->fetch_assoc()) { 
            $this->notifyFederation($curFederation, $date);
        } 
    }
}
