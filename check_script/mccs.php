<?php
            
include ("utils.php");
            
$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);
$map_url = $conf_array['check_script']['map_url'];

$spEntityIDs = array();
$spEntityIDs[] = $conf_array['check_script']['spEntityID_1'];
$spEntityIDs[] = $conf_array['check_script']['spEntityID_2'];

$spACSurls = array();
$spACSurls[] = $conf_array['check_script']['spACSurl_1'];
$spACSurls[] = $conf_array['check_script']['spACSurl_2'];

if (count($spEntityIDs) != count($spACSurls)) {
	die("Configuration error. Please check properties.ini.");
}

$db_host = $conf_array['db_connection']['db_host'];
$db_port = $conf_array['db_connection']['db_port'];
$db_name = $conf_array['db_connection']['db_name'];
$db_user = $conf_array['db_connection']['db_user'];
$db_password = $conf_array['db_connection']['db_password'];

$arrContextOptions=array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

if (($metadataXML = file_get_contents($map_url, false, stream_context_create($arrContextOptions)))===false){
	echo "Error fetching eduGAIN metadata XML\n";
} else {
	libxml_use_internal_errors(true);
	$idpList = extractIdPfromXML($metadataXML);
	if (!$idpList) {
		echo "Error loading eduGAIN metadata XML\n";
		foreach(libxml_get_errors() as $error) {
			echo "\t", $error->message;
		}
	} else {
		$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
		if ($mysqli->connect_errno) {
			die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		}

		$count = 0;
		foreach ($idpList as $idp){
			$count++;

			$ignore_entity = false;
			$previous_status = NULL;
			$check_ok = true;
			$reason = 'OK';
			$messages = array();

			$sql = "SELECT * FROM EntityDescriptors WHERE entityID = '" . $idp['entityID'] . "' ORDER BY lastCheck";
			$result = $mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));

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

				$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));
			}

			if ($ignore_entity == true) {
				print "Entity " . $idp['entityID'] . " ignored.\n";
				continue;
			}

			for ($i = 0; $i < count($spEntityIDs); $i++) {
				$result = checkIdp($idp['SingleSignOnService'], $spEntityIDs[$i], $spACSurls[$i]);

				// fai insert in tabella EntityChecks
				$sql  = 'INSERT INTO EntityChecks (entityID, spEntityID, checkHtml, httpStatusCode, checkResult) VALUES (';
				$sql .= "'" . $idp['entityID'] . "', ";
				$sql .= "'" . $spEntityIDs[$i] . "', ";
				$sql .= "'" . mysql_real_escape_string($result['html']) . "', ";
				$sql .= $result['http_code'] . ", ";

				if ($result['ok']) {
					$sql .= "'OK'";
				} else {
					$check_ok = false;
					$messages = $result['messages'];

					if (!$result['form_valid']) {
						$reason = 'FORM-Invalid';
					}
					elseif ($result['http_code'] != 200) {
						$reason = 'HTTP-Error';
					}
					elseif ($result['curl_return'] != '') {
						$reason = 'CURL-Error';
					}

					$sql .= "'" . $reason . "'";
				}

				$sql .= ")";
				$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));

				// update EntityDescriptors
				$sql = "UPDATE EntityDescriptors SET ";
				$sql .= "currentResult = '" . $reason . "' ";
				if ($previous_status != NULL) $sql .= ", previousResult = '" . $previous_status . "' ";
				$sql .= "WHERE entityID = '" . $idp['entityID'] . "' ";
				$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));
			}

			if ($check_ok) {
				//echo "The IdP ".$idp['entityID']." consumed metadata correctly\n";
			}
			else {
				echo "The IdP ".$idp['entityID']." did NOT consume metadata correctly.\n";
				echo "Reason: " . $reason . "\n";
				echo "Messages: " . print_r($messages, true) . "\n";
			}
			echo "\n";
		}

		$mysqli->close();
	}
}

?>
