<?php
            
include ("utils.php");

$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);

$db_connection = $conf_array['db_connection'];
$email_properties = $conf_array['email'];

$mysqli = get_db_connection($db_connection);
$sql = "SELECT * FROM Federations";
$fed_result = $mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));

while ($cur_federation = $fed_result->fetch_assoc()) { 
	// federationName registrationAuthority emailAddress
	$sql  = "SELECT * FROM EntityDescriptors WHERE ";
	$sql .= "registrationAuthority = '" . $obj['registrationAuthority'] . "' AND ";
	$sql .= "ignoreEntity = 0 AND ";
	$sql .= "currentResult <> '1 - OK' AND ";
	$sql .= "previousResult <> '1 - OK'";

	$result = $mysqli->query($query) or die("Error: " . $sql . ": " . mysqli_error($mysqli));
	$idps = array();
	while ($cur_idp = $result->fetch_assoc()) {
		$idps[$cur_idp['entityID']] = array();
		$idps[$cur_idp['entityID']]['name'] = $cur_idp['displayName'];
		$idps[$cur_idp['entityID']]['current_status'] = $cur_idp['currentResult'];
		$idps[$cur_idp['entityID']]['previous_status'] = $cur_idp['previousResult'];
		$idps[$cur_idp['entityID']]['tech_contacts'] = $cur_idp['technicalContacts'];
	}

	if (!empty($cur_federation['emailAddress']) && count($idps) > 0) {
		sendEmail($email_properties, $cur_federation['emailAddress'], $idps);
	}
} 

$mysqli->close();
?>
