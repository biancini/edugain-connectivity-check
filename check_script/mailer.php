<?php
            
include ("utils.php");

$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);

$db_connection = $conf_array['db_connection'];
$email_properties = $conf_array['email'];

$mysqli = get_db_connection($db_connection);

$stmt = $mysqli->prepare("SELECT * FROM Federations") or die("Error: " . mysqli_error($mysqli));
$stmt->bind_param("s", $cur_federation['registrationAuthority']) or die("Error: " . mysqli_error($mysqli));
$stmt->execute() or die("Error: " . mysqli_error($mysqli));
$fed_result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));

while ($cur_federation = $fed_result->fetch_assoc()) { 
	$stmt = $mysqli->prepare("SELECT * FROM EntityDescriptors WHERE registrationAuthority = ? AND ignoreEntity = 0 AND  currentResult <> '1 - OK' AND  previousResult <> '1 - OK'") or die("Error: " . mysqli_error($mysqli));
	$stmt->bind_param("s", $cur_federation['registrationAuthority']) or die("Error: " . mysqli_error($mysqli));
	$stmt->execute() or die("Error: " . mysqli_error($mysqli));

	$result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));
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
