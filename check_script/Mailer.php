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
    protected $conf_array;

    public function __construct() {
        $this->mailer = new MailUtils();
        $this->dbManager = new DBManager();
        $this->conf_array = parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
    }

    function sendMailToFederation($fed_data) {
        if (empty($fed_data['emailAddress']) &&
             empty($fed_data['sgDeputyEmail']) &&
             empty($fed_data['sgDelegateEmail'])) {

            // Missing required information in fed_data
            return;
        }

        if ($fed_data['idp_form_invalid'] == 0 &&
            $fed_data['idp_curl_error'] == 0 &&
            $fed_data['idp_http_error'] == 0 ) {

            // No issues on IdPs for federation
            return;
        }

        $email_properties = $this->conf_array['email'];
        $this->mailer->sendEmail($email_properties, $fed_data);
    }

    function collectIdPstatsForFed($query_result) {
        $fedData = array();

        $fedData['idp_ok'] = 0;
        $fedData['idp_form_invalid'] = 0;
        $fedData['idp_curl_error'] = 0;
        $fedData['idp_http_error'] = 0;
        $fedData['idp_disabled'] = 0;

        while ($cur_stat = $query_result->fetch_assoc()) {
            switch($cur_stat['currentResult']){
                case "1 - OK":
                    $fedData['idp_ok'] = $cur_stat['numIdPs'];
                    break;

                case "2 - FORM-Invalid":
                    $fedData['idp_form_invalid'] = $cur_stat['numIdPs'];
                    break;

                case "3 - CURL-Error":
                    $fedData['idp_curl_error'] = $cur_stat['numIdPs'];
                    break;

                case "3 - HTTP-Error":
                    $fedData['idp_http_error'] = $cur_stat['numIdPs'];
                    break;

                default:
                    $fedData['idp_disabled'] = $cur_stat['numIdPs'];
                    break;
            }
        }

        return $fedData;
    }

    function retrieveFedStat($fed_data, $date) {
        $query = new QueryBuilder();

        $query->setSql("SELECT * FROM FederationStats WHERE registrationAuthority = ? AND checkDate = ?");
        $query->addQueryParam($fed_data['regAuth'], 's');
        $query->addQueryParam($date, 's');
        $result = $this->dbManager->executeStatement(true, $query);
  
        return $this->collectIdPstatsForFed($result);
    }
  
    function notifyFederation($cur_federation, $date) {

        $fed_data = array();
        $fed_data['name'] = $cur_federation['federationName'];
        $fed_data['regAuth'] = $cur_federation['registrationAuthority'];
        $fed_data['emailAddress'] = $cur_federation['emailAddress'];
        $fed_data['sgDeputyName'] = $cur_federation['sgDeputyName'];
        $fed_data['sgDeputySurname'] = $cur_federation['sgDeputySurname'];
        $fed_data['sgDeputyEmail'] = $cur_federation['sgDeputyEmail'];
        $fed_data['sgDelegateName'] = $cur_federation['sgDelegateName'];
        $fed_data['sgDelegateSurname'] = $cur_federation['sgDelegateSurname'];
        $fed_data['sgDelegateEmail'] = $cur_federation['sgDelegateEmail'];

        $fed_stats = $this->retrieveFedStat($fed_data, $date);
        $mail_params = array_merge($fed_data, $fed_stats);

        $this->sendMailToFederation($mail_params);
    }
    
    function notifyAllFederations() {
        $date = date("Y-m-d");

        $query = new QueryBuilder();
        $query->setSql("SELECT * FROM Federations");
        $fed_result = $this->dbManager->executeStatement(true, $query);

        while ($cur_federation = $fed_result->fetch_assoc()) { 
            $this->notifyFederation($cur_federation, $date);
        } 
    }
}
