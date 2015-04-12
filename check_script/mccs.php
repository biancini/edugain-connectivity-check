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
$sps_keys[] = preg_grep ($regexp, $conf_array_keys);
foreach ($sps_keys as $key => $value) {
	foreach($value as $sp => $val) {
		$spEntityIDs[] = $conf_array[$val]['entityID'];
		$spACSurls[] = $conf_array[$val]['acs_url'];
	}
}

$parallel = intval($conf_array['check_script']['parallel']);
$checkHistory = intval($conf_array['check_script']['check_history']);

if (count($spEntityIDs) != count($spACSurls)) {
	throw new Exception("Configuration error. Please check properties.ini.");
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
	$mysqli = get_db_connection($db_connection);
	$stmt = $mysqli->prepare("UPDATE Federations SET updated = 0");
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$mysqli->close();

	store_feds_into_db($json_edugain_feds, $db_connection);

	$mysqli = get_db_connection($db_connection);
	$stmt = $mysqli->prepare("DELETE FROM Federations WHERE updated = 0");
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$mysqli->close();
}

if (($json_edugain_idps = file_get_contents($edugain_idps_url, false, stream_context_create($arrContextOptions)))===false){
	print "Error fetching JSON eduGAIN IdPs\n";
} else {
	
	$mysqli = get_db_connection($db_connection);
	$stmt = $mysqli->prepare("DELETE FROM EntityChecks WHERE checkExec = 0");
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if(!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	
	$stmt = $mysqli->prepare("UPDATE EntityChecks SET checkExec = checkExec - 1");
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$mysqli->close();
	
	$idpList = extractIdPfromJSON($json_edugain_idps);
	
	if (!$idpList) {
		print "Error loading eduGAIN JSON IdPs\n";
	} else {
		$mysqli = get_db_connection($db_connection);
		$stmt = $mysqli->prepare("UPDATE EntityDescriptors SET updated = 0");
		if (!$stmt) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
		if (!$stmt->execute()) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
		$mysqli->close();

		$count = 1;
		for ($i = 0; $i < $parallel; $i++) {
			$pid = pcntl_fork();
			if (!$pid) {
				//In child
				print "Executing check for " . $idpList[$count]['entityID'] . "\n";
				executeIdPchecks($idpList[$count], $spEntityIDs, $spACSurls, $db_connection, $checkHistory);
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
					executeIdPchecks($idpList[$count], $spEntityIDs, $spACSurls, $db_connection, $checkHistory);
					exit(0);
				}
				$count++;
			} 
		}

		$mysqli = get_db_connection($db_connection);
		$stmt = $mysqli->prepare("DELETE FROM EntityDescriptors WHERE updated = 0");
		if (!$stmt) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
		if (!$stmt->execute()) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
		$mysqli->close();
	}
	
	$mic_time = microtime();
	$mic_time = explode(" ",$mic_time);
	$mic_time = $mic_time[1] + $mic_time[0];
	$endtime = $mic_time;
	$total_execution_time = ($endtime - $start_time);
	print "\n\nTotal Executaion Time ".$total_execution_time." seconds.\n";
}

?>
