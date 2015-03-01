<?php
            
include ("utils.php");

//Calcolo Tempo di Esecuzione

$mic_time = microtime();
$mic_time = explode(" ",$mic_time);
$mic_time = $mic_time[1] + $mic_time[0];
$start_time = $mic_time;

// --------------------------
            
$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);

$map_url = $conf_array['check_script']['map_url'];

$spEntityIDs = array();
$spACSurls = array();

$regexp = "/^sp_\d/";

$conf_array_keys = array_keys($conf_array);
$sps_keys[] = preg_grep ( $regexp , $conf_array_keys);
foreach ($sps_keys as $key => $value){
	foreach($value as $sp => $val){
		$spEntityIDs[] = $conf_array[$val]['entityID'];
		$spACSurls[] = $conf_array[$val]['acs_url'];
	}
}

$parallel = intval($conf_array['check_script']['parallel']);

if (count($spEntityIDs) != count($spACSurls)) {
	die("Configuration error. Please check properties.ini.");
}

$db_connection = $conf_array['db_connection'];

$edugain_feds_url = $conf_array['edugain_db_json']['json_feds_url'];
$edugain_idps_url = $conf_array['edugain_db_json']['json_idps_url'];

$arrContextOptions=array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

if (($json_edugain_feds = file_get_contents($edugain_feds_url, false, stream_context_create($arrContextOptions)))===false){
	print "Error fetching JSON eduGAIN Federation members\n";
} else {
	store_feds_into_db($json_edugain_feds, $db_connection);
}

if (($json_edugain_idps = file_get_contents($edugain_idps_url, false, stream_context_create($arrContextOptions)))===false){
	print "Error fetching JSON eduGAIN IdPs\n";
} else {
	$idpList = extractIdPfromJSON($json_edugain_idps);
	
	if (!$idpList) {
		print "Error loading eduGAIN JSON IdPs\n";
	} else {
		$count = 1;
		for ($i = 0; $i < $parallel; $i++) {
			$pid = pcntl_fork();
			if (!$pid) {
				//In child
				print "Executing check for " . $idpList[$count]['entityID'] . "\n";
				executeIdPchecks($idpList[$count], $spEntityIDs, $spACSurls, $db_connection);
				exit(0);
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
					executeIdPchecks($idpList[$count], $spEntityIDs, $spACSurls, $db_connection);
					exit(0);
				}
				$count++;
			} 
		}
	}
	
	$mic_time = microtime();
	$mic_time = explode(" ",$mic_time);
	$mic_time = $mic_time[1] + $mic_time[0];
	$endtime = $mic_time;
	$total_execution_time = ($endtime - $start_time);
	print "\n\nTotal Executaion Time ".$total_execution_time." seconds.\n";
}

// if (($metadataXML = file_get_contents($map_url, false, stream_context_create($arrContextOptions)))===false){
// 	print "Error fetching eduGAIN metadata XML\n";
// } else {
// 	libxml_use_internal_errors(true);
// 	$idpList = extractIdPfromXML($metadataXML);
// 	if (!$idpList) {
// 		print "Error loading eduGAIN metadata XML\n";
// 		foreach(libxml_get_errors() as $error) {
// 			print "\t", $error->message;
// 		}
// 	} else {
// 		$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
// 		if ($mysqli->connect_errno) {
// 			die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
// 		}

// 		$count = 0;
// 		foreach ($idpList as $idp){
// 			$count++;

// 			$ignore_entity = false;
// 			$previous_status = NULL;
// 			$check_ok = true;
// 			$reason = NULL;
// 			$messages = array();

// 			$sql = "SELECT * FROM EntityDescriptors WHERE entityID = '" . $idp['entityID'] . "' ORDER BY lastCheck";
// 			$result = $mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));

// 			if ($result->num_rows > 0) {
// 				while ($row = $result->fetch_assoc()) {
// 					$previous_status = $row['currentResult'];
// 					$ignore_entity = $row['ignoreEntity'];
// 				}
// 			} else {
// 				$sqr  = 'INSERT INTO EntityDescriptors (entityID, registrationAuthority, technicalContacts, supportContacts) VALUES (';

// 				$sql .= "'" . $idp['entityID'] . "', ";
// 				$sql .= "'" . $idp['registrationAuthority'] . "', ";
// 				$sql .= "'" . join(",", $idp['technicalContacts']) . "', ";
// 				$sql .= "'" . join(",", $idp['supportContacts']) . "'";
// 				$sql .= ")";

// 				$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));
// 			}

// 			if ($ignore_entity == true) {
// 				print "Entity " . $idp['entityID'] . " ignored.\n";
// 				continue;
// 			}

// 			for ($i = 0; $i < count($spEntityIDs); $i++) {
// 				$result = checkIdp($idp['SingleSignOnService'], $spEntityIDs[$i], $spACSurls[$i]);

// 				// fai insert in tabella EntityChecks
// 				$sql  = 'INSERT INTO EntityChecks (entityID, spEntityID, checkHtml, httpStatusCode, checkResult) VALUES (';
// 				$sql .= "'" . $idp['entityID'] . "', ";
// 				$sql .= "'" . $spEntityIDs[$i] . "', ";
// 				$sql .= "'" . mysql_real_escape_string($result['html']) . "', ";
// 				$sql .= $result['http_code'] . ", ";

// 				if ($result['ok']) {
// 					$sql .= "'1 - OK'";
// 				} else {
// 					$check_ok = false;
// 					$messages = $result['messages'];

// 					if (!$result['form_valid']) {
// 						$reason = '2 - FORM-Invalid';
// 					}
// 					elseif ($result['http_code'] != 200) {
// 						$reason = '3 - HTTP-Error';
// 					}
// 					elseif ($result['curl_return'] != '') {
// 						$reason = '3 - CURL-Error';
// 					}

// 					$sql .= "'" . $reason . "'";
// 				}

// 				$sql .= ")";
// 				$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));

// 				// update EntityDescriptors
// 				$sql = "UPDATE EntityDescriptors SET ";
// 				$sql .= "lastCheck = '" . date("Y-m-d H:i:s"). "' ";
// 				$sql .= ", currentResult = '" . $reason . "' ";
// 				if ($previous_status != NULL) $sql .= ", previousResult = '" . $previous_status . "' ";
// 				$sql .= "WHERE entityID = '" . $idp['entityID'] . "' ";
// 				$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));
// 			}

// 			if ($check_ok) {
// 				//print "The IdP ".$idp['entityID']." consumed metadata correctly\n";
// 			}
// 			else {
// 				print "The IdP ".$idp['entityID']." did NOT consume metadata correctly.\n";
// 				print "Reason: " . $reason . "\n";
// 				print "Messages: " . print_r($messages, true) . "\n";
// 			}
// 			print "\n";
// 		}

// 		$mysqli->close();
// 	}
// }

?>
