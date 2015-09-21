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

$conf_array = parse_ini_file(dirname(__FILE__) . 'properties.ini.php', true);
$email_properties = $conf_array['email'];

$query = new QueryBuilder();
$query->setSql("SELECT * FROM Federations");
$fed_result = $dbManager->executeStatement(true, $query);

while ($cur_federation = $fed_result->fetch_assoc()) { 
    $query = new QueryBuilder();
    $query->setSql("SELECT * FROM EntityDescriptors WHERE registrationAuthority = ? AND ignoreEntity = 0 AND  currentResult <> '1 - OK' AND  previousResult <> '1 - OK'");
    $query->addQueryParam($cur_federation['registrationAuthority'], 's');
    $result = $dbManager->executeStatement(true, $query);
    $idps = array();
    while ($cur_idp = $result->fetch_assoc()) {
        $idps[$cur_idp['entityID']] = array();
        $idps[$cur_idp['entityID']]['name'] = $cur_idp['displayName'];
        $idps[$cur_idp['entityID']]['current_status'] = substr($cur_idp['currentResult'], 4);
        $idps[$cur_idp['entityID']]['previous_status'] = substr($cur_idp['previousResult'], 4);
        $idps[$cur_idp['entityID']]['tech_contacts'] = explode(",", $cur_idp['technicalContacts']);
    }

    if (!empty($cur_federation['emailAddress']) && count($idps) > 0) {
        $mailer.>sendEmail($email_properties, $cur_federation['emailAddress'], $idps);
    }
} 
