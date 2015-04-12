<?php
# Copyright 2015 Géant Association
#
# Licensed under the GÉANT Standard Open Source (the "License");
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
            
include ("utils.php");

$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);

$db_connection = $conf_array['db_connection'];
$email_properties = $conf_array['email'];

$mysqli = get_db_connection($db_connection);

$stmt = $mysqli->prepare("SELECT * FROM Federations");
if (!$stmt) {
    throw new Exception("Error: " . mysqli_error($mysqli));
}
if (!$stmt->execute()) {
    throw new Exception("Error: " . mysqli_error($mysqli));
}
$fed_result = $stmt->get_result();
if (!$fed_result) {
    throw new Exception("Error: " . mysqli_error($mysqli));
}

while ($cur_federation = $fed_result->fetch_assoc()) { 
    $stmt = $mysqli->prepare("SELECT * FROM EntityDescriptors WHERE registrationAuthority = ? AND ignoreEntity = 0 AND  currentResult <> '1 - OK' AND  previousResult <> '1 - OK'");
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (!$stmt->bind_param("s", $cur_federation['registrationAuthority'])) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (!$stmt->execute()) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    $idps = array();
    while ($cur_idp = $result->fetch_assoc()) {
        $idps[$cur_idp['entityID']] = array();
        $idps[$cur_idp['entityID']]['name'] = $cur_idp['displayName'];
        $idps[$cur_idp['entityID']]['current_status'] = substr($cur_idp['currentResult'], 4);
        $idps[$cur_idp['entityID']]['previous_status'] = substr($cur_idp['previousResult'], 4);
        $idps[$cur_idp['entityID']]['tech_contacts'] = explode(",", $cur_idp['technicalContacts']);
    }

    if (!empty($cur_federation['emailAddress']) && count($idps) > 0) {
        sendEmail($email_properties, $cur_federation['emailAddress'], $idps);
    }
} 

$mysqli->close();
?>
