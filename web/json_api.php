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

function getParameter($key, $defaultValue, $array=false) {
    $value = (array_key_exists($key, $_REQUEST) ? htmlspecialchars($_REQUEST[$key]) : $defaultValue);

    if (!$value || trim($value) == '') {
        $value = $defaultValue;
    }

    if ($array) {
        $value = explode(",", $value);
    }

    return $value;
}

function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) {
        $refs = array();
        foreach($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    return $arr;
}

function concatenateWhere($sqlConditions) {
    if (!strstr($sqlConditions, "WHERE")) {
        return " WHERE";
    }
    else {
        return " AND";
    }
}

$confArray = parse_ini_file('../properties.ini', true);
$dbConnection = $confArray['db_connection'];

if (array_key_exists("db_sock", $dbConnection) && !empty($dbConnection['db_sock'])) {
    $mysqli = new mysqli(null, $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], null, $dbConnection['db_sock']);
}
else {
    $mysqli = new mysqli($dbConnection['db_host'], $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], $dbConnection['db_port']);
}

if ($mysqli->connect_errno) {
    header('HTTP/1.1 500 Internal Server Error');
    error_log("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");

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

    $sqlCount = "SELECT COUNT(*) FROM EntityDescriptors";
    $sql = "SELECT * FROM EntityDescriptors";
    $sqlConditions = "";
    $queryParams = array();
    if ($params['f_id_status']) {
        if (in_array("NULL", $params['f_id_status'])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " currentResult IS NULL";
        }
        if (!in_array("All", $params['f_id_status'])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " currentResult in (";
            foreach ($params['f_id_status'] as $val) {
                $sqlConditions .= (substr($sqlConditions, -1) != "(") ? ", ": "";
                $sqlConditions .= "?";
                array_push($queryParams, $val);
            }
            $sqlConditions .= ")";
        }
    }
    if ($params['f_entityID'] && $params['f_entityID'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " entityID LIKE ?";
        array_push($queryParams, "%" . $params['f_entityID'] . "%");
    }
    if ($params['f_registrationAuthority'] && $params['f_registrationAuthority'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " registrationAuthority LIKE ?";
        array_push($queryParams, "%" . $params['f_registrationAuthority'] . "%");
    }
    if ($params['f_displayName'] && $params['f_displayName'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " displayName LIKE ?";
        array_push($queryParams, "%" . $params['f_displayName'] . "%");
    }
    if ($params['f_ignore_entity'] && $params['f_ignore_entity'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " ignoreEntity = ?";
        array_push($queryParams, ($params['f_ignore_entity'] == "True" ? 1 : 0));
    }
    if ($params['f_last_check'] && $params['f_last_check'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        if ($params['f_last_check'] == "1") {
            $sqlConditions .= " lastCheck >= DATE_FORMAT(curdate() - interval 30 day,'%m/%d/%Y')";
        }
    }
    if ($params['f_current_result'] && $params['f_current_result'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " currentResult LIKE ?";
        array_push($queryParams, "%" . $params['f_current_result']);
    }
    if ($params['f_previous_result'] && $params['f_previous_result'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " previousResult = ?";
        array_push($queryParams, $params['f_previous_result']);
    }
    
    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
    }
    
    $queryParams = array_merge(array(str_repeat('s', count($queryParams))), $queryParams);
    
    // find out how many rows are in the table
    $stmt = $mysqli->prepare($sqlCount . $sqlConditions);
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (count($queryParams) > 1 && !call_user_func_array(array($stmt, 'bind_param'), refValues($queryParams))) {
        throw new Exception("Error: " . mysqli_error($mysqli));
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
    if ($page > $totalpages) {
        $page = $totalpages;
    }
    if ($page < 1) {
        $page = 1;
    }
    $offset = ($page - 1) * $rowsperpage;
        
    $sqlConditions .= " LIMIT " . $offset . " , " . $rowsperpage;
    $stmt = $mysqli->prepare($sql . $sqlConditions);
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (count($queryParams) > 1 && !call_user_func_array(array($stmt, 'bind_param'), refValues($queryParams))) {
        throw new Exception("Error: " . mysqli_error($mysqli));
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

    $sqlCount = "SELECT COUNT(*) FROM EntityChecks";
    $sql = "SELECT * FROM EntityChecks";
    $sqlConditions = "";
    $queryParams = array();
    if ($params['f_id_status']) {
        if (in_array("NULL", $params['f_id_status'])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " checkResult IS NULL";
        }
        if (!in_array("All", $params['f_id_status'])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " checkResult in (";
            foreach ($params['f_id_status'] as $val) {
                if (substr($sqlConditions, -1) != "(") {
                    $sqlConditions .= ", ";
                }
                $sqlConditions .= "?";
                array_push($queryParams, $val);
            }
            $sqlConditions .= ")";
        }
    }
    if ($params['f_entityID'] && $params['f_entityID'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " entityID LIKE ?";
        array_push($queryParams, "%" . $params['f_entityID'] . "%");
    }
    if ($params['f_spEntityID'] && $params['f_spEntityID'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " spEntityID LIKE ?";
        array_push($queryParams, "%" . $params['f_spEntityID'] . "%");
    }
    if ($params['f_check_time'] && $params['f_check_time'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        if ($params['f_check_time'] == "1") {
            $sqlConditions .= " checkTime >= DATE_FORMAT(curdate() - interval 30 day,'%m/%d/%Y')";
        }
    }
    if ($params['f_http_status_code'] && $params['f_http_status_code'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " httpStatusCode = ?";
        array_push($queryParams, $params['f_http_status_code']);
    }
    if ($params['f_check_result'] && $params['f_check_result'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " checkResult = ?";
        array_push($queryParams, $params['f_check_result']);
    }

    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
    }

    $queryParams = array_merge(array(str_repeat('s', count($queryParams))), $queryParams);

    // find out how many rows are in the table
    $stmt = $mysqli->prepare($sqlCount . $sqlConditions);
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (count($queryParams) > 1 && !call_user_func_array(array($stmt, 'bind_param'), refValues($queryParams))) {
        throw new Exception("Error: " . mysqli_error($mysqli));
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
    if ($page > $totalpages) {
        $page = $totalpages;
    }
    if ($page < 1) {
        $page = 1;
    }
    $offset = ($page - 1) * $rowsperpage;
    
    $sqlConditions .= " LIMIT " . $offset . " , " . $rowsperpage;
    $stmt = $mysqli->prepare($sql . $sqlConditions);
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (count($queryParams) > 1 && !call_user_func_array(array($stmt, 'bind_param'), refValues($queryParams))) {
        throw new Exception("Error: " . mysqli_error($mysqli));
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
