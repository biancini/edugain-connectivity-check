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

if (($metadataXML = file_get_contents($map_url, false, stream_context_create($arrContextOptions)))===false){
	print "Error fetching eduGAIN metadata XML\n";
} else {
	libxml_use_internal_errors(true);
	$idpList = extractIdPfromXML($metadataXML);
	if (!$idpList) {
		print "Error loading eduGAIN metadata XML\n";
		foreach(libxml_get_errors() as $error) {
			print "\t", $error->message;
		}
	} else {
		$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
		if ($mysqli->connect_errno) {
			throw new Exception("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		}

		$count = 0;
		foreach ($idpList as $idp){
			$count++;

			$ignore_entity = false;
			$previous_status = NULL;
			$check_ok = true;
			$reason = NULL;
			$messages = array();

			$sql = "SELECT * FROM EntityDescriptors WHERE entityID = '" . $idp['entityID'] . "' ORDER BY lastCheck";
			$result = $mysqli->query($sql);
			if (!$result) {
				throw new Exception("Error: " . $sql . ": " . mysqli_error($mysqli));
			}

			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$previous_status = $row['currentResult'];
					$ignore_entity = $row['ignoreEntity'];
				}
			} else {
				$sql  = 'INSERT INTO EntityDescriptors (entityID, registrationAuthority, technicalContacts, supportContacts) VALUES (';

				$sql .= "'" . $idp['entityID'] . "', ";
				$sql .= "'" . $idp['registrationAuthority'] . "', ";
				$sql .= "'" . join(",", $idp['technicalContacts']) . "', ";
				$sql .= "'" . join(",", $idp['supportContacts']) . "'";
				$sql .= ")";

				if (!$mysqli->query($sql)) {
					throw new Exception("Error: " . $sql . ": " . mysqli_error($mysqli));
				}
			}

			if ($ignore_entity == true) {
				print "Entity " . $idp['entityID'] . " ignored.\n";
				continue;
			}

			for ($i = 0; $i < count($spEntityIDs); $i++) {
				$result = checkIdp($idp['SingleSignOnService'], $spEntityIDs[$i], $spACSurls[$i]);

				fai insert in tabella EntityChecks
				$sql  = 'INSERT INTO EntityChecks (entityID, spEntityID, checkHtml, httpStatusCode, checkResult) VALUES (';
				$sql .= "'" . $idp['entityID'] . "', ";
				$sql .= "'" . $spEntityIDs[$i] . "', ";
				$sql .= "'" . mysql_real_escape_string($result['html']) . "', ";
				$sql .= $result['http_code'] . ", ";

				if ($result['ok']) {
					$sql .= "'1 - OK'";
				} else {
					$check_ok = false;
					$messages = $result['messages'];

					if (!$result['form_valid']) {
						$reason = '2 - FORM-Invalid';
					}
					elseif ($result['http_code'] != 200) {
						$reason = '3 - HTTP-Error';
					}
					elseif ($result['curl_return'] != '') {
						$reason = '3 - CURL-Error';
					}

					$sql .= "'" . $reason . "'";
				}

				$sql .= ")";
				if (!$mysqli->query($sql)) {
					throw new Exception("Error: " . $sql . ": " . mysqli_error($mysqli));
				}

				update EntityDescriptors
				$sql = "UPDATE EntityDescriptors SET ";
				$sql .= "lastCheck = '" . date("Y-m-d H:i:s"). "' ";
				$sql .= ", currentResult = '" . $reason . "' ";
				if ($previous_status != NULL) $sql .= ", previousResult = '" . $previous_status . "' ";
				$sql .= "WHERE entityID = '" . $idp['entityID'] . "' ";
				if (!$mysqli->query($sql)) {
					throw new Exception("Error: " . $sql . ": " . mysqli_error($mysqli));
				}
			}

			if ($check_ok) {
				//print "The IdP ".$idp['entityID']." consumed metadata correctly\n";
			}
			else {
				print "The IdP ".$idp['entityID']." did NOT consume metadata correctly.\n";
				print "Reason: " . $reason . "\n";
				print "Messages: " . print_r($messages, true) . "\n";
			}
			print "\n";
		}

		$mysqli->close();
	}
}

?>
