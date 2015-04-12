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

$mysqli->set_charset("utf8");

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

function refValues($arr){
	if (strnatcmp(phpversion(),'5.3') >= 0) {
		$refs = array();
		foreach($arr as $key => $value)
			$refs[$key] = &$arr[$key];
		return $refs;
	}
	return $arr;
}

$action = getParameter('action', 'entities');

if ($action == 'entities') {
	$params["show"] = getParameter('show', 'list_idps');
	$params["f_order"] = getParameter('f_order', 'entityID');
	$params["f_id_status"] = getParameter('f_id_status', 'All', true);
	$params["f_entityID"] = getParameter('f_entityID', 'All');
	$params["f_registrationAuthority"] = getParameter('f_registrationAuthority', 'All');
	$params["f_displayName"] = getParameter('f_displayName', 'All');
	$params["f_ignore_entity"] = getParameter('f_ignore_entity', 'All');
	$params["f_last_check"] = getParameter('f_last_check', 'All');
	$params["f_current_result"] = getParameter('f_current_result', 'All');
	$params["f_previous_result"] = getParameter('f_previous_result', 'All');
	//error_log(print_r($params, true));

	$sql_count = "SELECT COUNT(*) FROM EntityDescriptors";
	$sql = "SELECT * FROM EntityDescriptors";
	$sql_conditions = "";
	$query_params = array();
	if ($params['f_id_status']) {
		if (in_array("NULL", $params['f_id_status'])) {
			if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
			else $sql_conditions .= " AND";
			$sql_conditions .= " currentResult IS NULL";
		}
		elseif (!in_array("All", $params['f_id_status'])) {
			if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
			else $sql_conditions .= " AND";
			$sql_conditions .= " currentResult in (";
			foreach ($params['f_id_status'] as $val) {
				if (substr($sql_conditions, -1) != "(") {
					$sql_conditions .= ", ";
				}
				$sql_conditions .= "?";
				array_push($query_params, $val);
			}
			$sql_conditions .= ")";
		}
	}
	if ($params['f_entityID'] && $params['f_entityID'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " entityID LIKE ?";
		array_push($query_params, "%" . $params['f_entityID'] . "%");
	}
	if ($params['f_registrationAuthority'] && $params['f_registrationAuthority'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " registrationAuthority LIKE ?";
		array_push($query_params, "%" . $params['f_registrationAuthority'] . "%");
	}
	if ($params['f_displayName'] && $params['f_displayName'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " displayName LIKE ?";
		array_push($query_params, "%" . $params['f_displayName'] . "%");
	}
	if ($params['f_ignore_entity'] && $params['f_ignore_entity'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " ignoreEntity = ?";
		array_push($query_params, ($params['f_ignore_entity'] == "True" ? 1 : 0));
	}
	if ($params['f_last_check'] && $params['f_last_check'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		if ($params['f_last_check'] == "1") {
			$sql_conditions .= " lastCheck >= DATE_FORMAT(curdate() - interval 30 day,'%m/%d/%Y')";
		}
	}
	if ($params['f_current_result'] && $params['f_current_result'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " currentResult LIKE ?";
		array_push($query_params, "%" . $params['f_current_result']);
	}
	if ($params['f_previous_result'] && $params['f_previous_result'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " previousResult = ?";
		array_push($query_params, $params['f_previous_result']);
	}
	
	if ($params['f_order']) {
		$sql_conditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
	}
	
	$query_params = array_merge(array(str_repeat('s', count($query_params))), $query_params);
	
	// find out how many rows are in the table
	$stmt = $mysqli->prepare($sql_count . $sql_conditions);
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (count($query_params) > 1) {
		if (!call_user_func_array(array($stmt, 'bind_param'), refValues($query_params))) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$result = $stmt->get_result();
	if (!$result) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$numrows = $result->fetch_row()[0];
	
	$rowsperpage = 30;
	$totalpages = ceil($numrows / $rowsperpage);
	$page = getParameter('page', '1');
	$page = is_numeric($page) ? (int) $page : 1;
	if ($page > $totalpages) $page = $totalpages;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $rowsperpage;
		
	$sql_conditions .= " LIMIT " . $offset . " , " . $rowsperpage;
	$stmt = $mysqli->prepare($sql . $sql_conditions);
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (count($query_params) > 1) {
		if (!call_user_func_array(array($stmt, 'bind_param'), refValues($query_params))) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$result = $stmt->get_result();
	if (!$result) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$count = 1;
	
	$entities = array();
	while ($row = $result->fetch_assoc()) {
		$entity = array(
			'entityID' => $row['entityID'],
			'registrationAuthority' => $row['registrationAuthority'],
			'displayName' => $row['displayName'],
			'technicalContacts' => $row['technicalContacts'],
			'supportContacts' => $row['supportContacts'],
			'ignoreEntity' => ($row['ignoreEntity'] == 1),
			'lastCheck' => $row['lastCheck'],
			'currentResult' => $row['currentResult'],
			'previousResult' => $row['previousResult'],
			
		);
		array_push($entities, $entity);
	}
	
	$return = array(
		'results' => $entities,
		'num_rows' => $numrows,
		'page' => $page,
	        'total_pages' => $totalpages,
	);
	print json_encode($return);
}
elseif ($action == 'checks') {
	$params["show"] = getParameter('show', 'list_idp_tests');
	$params["f_order"] = getParameter('f_order', 'entityID');
	$params["f_id_status"] = getParameter('f_id_status', 'All', true);
	$params["f_entityID"] = getParameter('f_entityID', 'All');
	$params["f_spEntityID"] = getParameter('f_spEntityID', 'All');
	$params["f_check_time"] = getParameter('f_check_time', 'All');
	$params["f_http_status_code"] = getParameter('f_http_status_code', 'All');
	$params["f_check_result"] = getParameter('f_check_result', 'All');
	//error_log(print_r($params, true));

      	$sql_count = "SELECT COUNT(*) FROM EntityChecks";
	$sql = "SELECT * FROM EntityChecks";
	$sql_conditions = "";
	$query_params = array();
	if ($params['f_id_status']) {
		if (in_array("NULL", $params['f_id_status'])) {
			if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
			else $sql_conditions .= " AND";
			$sql_conditions .= " checkResult IS NULL";
		}
		elseif (!in_array("All", $params['f_id_status'])) {
			if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
			else $sql_conditions .= " AND";
			$sql_conditions .= " checkResult in (";
			foreach ($params['f_id_status'] as $val) {
				if (substr($sql_conditions, -1) != "(") {
					$sql_conditions .= ", ";
				}
				$sql_conditions .= "?";
				array_push($query_params, $val);
			}
			$sql_conditions .= ")";
		}
	}
        if ($params['f_entityID'] && $params['f_entityID'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " entityID LIKE ?";
		array_push($query_params, "%" . $params['f_entityID'] . "%");
	}
        if ($params['f_spEntityID'] && $params['f_spEntityID'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " spEntityID LIKE ?";
		array_push($query_params, "%" . $params['f_spEntityID'] . "%");
	}
        if ($params['f_check_time'] && $params['f_check_time'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		if ($params['f_check_time'] == "1") {
			$sql_conditions .= " checkTime >= DATE_FORMAT(curdate() - interval 30 day,'%m/%d/%Y')";
		}
	}
        if ($params['f_http_status_code'] && $params['f_http_status_code'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " httpStatusCode = ?";
		array_push($query_params, $params['f_http_status_code']);
	}
        if ($params['f_check_result'] && $params['f_check_result'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " checkResult = ?";
		array_push($query_params, $params['f_check_result']);
	}

	if ($params['f_order']) {
		$sql_conditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
	}

	$query_params = array_merge(array(str_repeat('s', count($query_params))), $query_params);

	// find out how many rows are in the table 
	$stmt = $mysqli->prepare($sql_count . $sql_conditions);
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (count($query_params) > 1) {
		if (!call_user_func_array(array($stmt, 'bind_param'), refValues($query_params))) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (!$result = $stmt->get_result()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$numrows = $result->fetch_row()[0];

	$rowsperpage = 30;
	$totalpages = ceil($numrows / $rowsperpage);
	$page = getParameter('page', '1');
	$page = is_numeric($page) ? (int) $page : 1;
	if ($page > $totalpages) $page = $totalpages;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $rowsperpage;
	
	$sql_conditions .= " LIMIT " . $offset . " , " . $rowsperpage;
	//error_log($sql . $sql_conditions);
	$stmt = $mysqli->prepare($sql . $sql_conditions);
	if (!$stmt) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	if (count($query_params) > 1) {
		if (!call_user_func_array(array($stmt, 'bind_param'), refValues($query_params))) {
			throw new Exception("Error: " . mysqli_error($mysqli));
		}
	}
	if (!$stmt->execute()) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}
	$result = $stmt->get_result();
	if (!$result) {
		throw new Exception("Error: " . mysqli_error($mysqli));
	}

	$entities = array();
	while ($row = $result->fetch_assoc()) {
		$entity = array(
			'entityID' => $row['entityID'],
			'spEntityID' => $row['spEntityID'],
			'checkTime' => $row['checkTime'],
			'httpStatusCode' => $row['httpStatusCode'],
			'checkResult' => substr($row['checkResult'], 4),
			//'checkHtml' => $row['checkHtml'],
		);
		array_push($entities, $entity);
	}

	$return = array(
		'results' => $entities,
		'num_rows' => $numrows,
		'page' => $page,
	        'total_pages' => $totalpages,
	);
	print json_encode($return);
}
else {
	throw new Exception("Wrong action, valid actions are entities or checks.");
}
?>
