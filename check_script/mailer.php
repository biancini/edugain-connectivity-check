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

$mailer = new MailUtils();
$dbManager = new DBManager();

$conf_array = parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
$email_properties = $conf_array['email'];

$date = date("Y-m-d");

$query = new QueryBuilder();
$query->setSql("SELECT * FROM Federations");
$fed_result = $dbManager->executeStatement(true, $query);

while ($cur_federation = $fed_result->fetch_assoc()) { 
    $query = new QueryBuilder();
    $query->setSql("SELECT * FROM FederationStats WHERE registrationAuthority = ? AND checkDate = ?");
    $query->addQueryParam($cur_federation['registrationAuthority'], 's');
    $query->addQueryParam($date, 's');
    $result = $dbManager->executeStatement(true, $query);
    $fed_data = array();
    while ($cur_stat = $result->fetch_assoc()) {
       $fed_data['name'] = $cur_federation['federationName'];
       $fed_data['regAuth'] = $cur_federation['registrationAuthority'];
       $fed_data['emailAddress'] = $cur_federation['emailAddress'];
       $fed_data['sgDeputyEmail'] = $cur_federation['sgDeputyEmail'];
       $fed_data['sgDelegateEmail'] = $cur_federation['sgDelegateEmail'];
       switch($cur_stat['currentResult']){
         case "1 - OK":
            $fed_data['idp_ok'] = $cur_stat['numIdPs'];
            break;

         case "2 - FORM-Invalid":
            $fed_data['idp_form_invalid'] = $cur_stat['numIdPs'];
            break;

         case "3 - CURL-Error":
            $fed_data['idp_curl_error'] = $cur_stat['numIdPs'];
            break;

         case "3 - HTTP-Error":
            $fed_data['idp_http_error'] = $cur_stat['numIdPs'];
            break;

         case NULL:
            $fed_data['idp_disabled'] = $cur_stat['numIdPs'];
            break;
        }
    }

    if ((!empty($fed_data['emailAddress']) ||
         !empty($fed_data['sgDeputyEmail']) ||
         !empty($fed_data['sgDelegateEmail'])) &&
         !empty($fed_data['idp_form_invalid']) ||
         !empty($fed_data['idp_curl_error']) ||
         !empty($fed_data['idp_http_error']) ) {
           $mailer->sendEmail($email_properties, $fed_data);
    }
} 
