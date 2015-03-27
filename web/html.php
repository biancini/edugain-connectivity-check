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

$conf_array = parse_ini_file('../properties.ini', true);
$db_connection = $conf_array['db_connection'];

if (array_key_exists("db_sock", $db_connection) && !empty($db_connection['db_sock'])) {
        $mysqli = new mysqli(null, $db_connection['db_user'], $db_connection['db_password'], $db_connection['db_name'], null, $db_connection['db_sock']);
}       
else {  
        $mysqli = new mysqli($db_connection['db_host'], $db_connection['db_user'], $db_connection['db_password'], $db_connection['db_name'], $db_connection['db_port']);
} 

if ($mysqli->connect_errno) {
	header('HTTP/1.1 500 Internal Server Error');
	error_log("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

function getParameter($key, $default_value, $array=false) {
	$value = (array_key_exists($key, $_REQUEST) ? htmlspecialchars($_REQUEST[$key]) : $default_value);

	if (!$value || trim($value) == '') {
		$value = $default_value;
	}

	if ($array) {
		$value = explode(",", $value);
	}

	return $value;
}

$id = getParameter("id", "");

if (getParameter("show", "") == "html") {
	$sql = "SELECT checkHtml FROM EntityChecks WHERE id = ?";
	$stmt = $mysqli->prepare($sql) or die("Error: " . mysqli_error($mysqli));
	$stmt->bind_param("s", $id) or die("Error: " . mysqli_error($mysqli));
        $stmt->execute() or die("Error: " . mysqli_error($mysqli));
        $result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));
        $stmt->close();

	while ($row = $result->fetch_assoc()) {
		print $row['checkHtml'];
	}
}
else {
	$sql = "SELECT entityID, spEntityID, DATE_FORMAT(checkTime, '%d/%m/%Y at %H:%m:%s') as checkTime FROM EntityChecks WHERE id = ?";
	$stmt = $mysqli->prepare($sql) or die("Error: " . mysqli_error($mysqli));
	$stmt->bind_param("s", $id) or die("Error: " . mysqli_error($mysqli));
        $stmt->execute() or die("Error: " . mysqli_error($mysqli));
        $result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));
        $stmt->close();
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link media="screen" href="css/eduroam.css" type="text/css" rel="stylesheet"/>
	<title>edugain - mccs</title>
	</head>
	<body><center>
	<table class="container" cellpadding="5" cellspacing="0">
	<tr><td><a title="edugain home" href="http://www.geant.net/service/edugain/pages/home.aspx"><img src="images/edugain.png"></a></td></tr>
	<tr><td class="body">
	<div class="admin_naslov"><a href="index.php">Identity providers</a> | <a href="test.php">All IdP test results</a> | <a href="https://wiki.edugain.org/Metadata_Consumption_Check_Service" target="_blank">Instructions</a></div>
	<?php
	while ($row = $result->fetch_assoc()) {
		?>
		<div class="admin_naslov" style="background-color: #e9e9e9;">The following HTML code was returned during the test when login from
		the Identity Provider <i><?= $row['entityID'] ?></i> on the service <i><?= $row['spEntityID'] ?></i> on <?= $row['checkTime'] ?>:</div>
		<hr>
		<iframe src="html.php?show=html&id=<?=$id?>"></iframe>
		<?php
	}
	?>
	</td></tr></table>
	</center></body>
	</html>
	<?php
}

$mysqli->close();
?>
