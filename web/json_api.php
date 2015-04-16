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

include("utils.php");

function getEntities($mysqli) {
    $params = getAllParameters(array(
        array('show', 'list_idps', false),
        array('f_order', 'entityID', false),
        array('f_entityID', 'All', false),
        array('f_registrationAuthority', 'All', false),
        array('f_displayName', 'All', false),
        array('f_ignore_entity', 'All', false),
        array('f_last_check', 'All', false),
        array('f_current_result', 'All', false),
        array('f_previous_result', 'All', false),
    ));

    $sqlCount = "SELECT COUNT(*) FROM EntityDescriptors";
    $sql = "SELECT * FROM EntityDescriptors";

    $sqlConditions = "";
    $queryParams = array();
    addAllSqlConditions($sqlConditions, $queryParams, $params, array(
        array('f_entityID', 'entityID', true, NULL),
        array('f_registrationAuthority', 'registrationAuthority', true, NULL),
        array('f_displayName', 'displayName', true, NULL),
        array('f_ignore_entity', 'ignoreEntity', false, NULL),
        array('f_last_check', 'lastCheck', false, array('1' => 'DATE(lastCheck) = curdate()', '2' => 'DATE(lastCheck) = curdate() - interval 1 day')),
        array('f_current_result', 'currentResult', true, NULL),
        array('f_previous_result', 'previousResult', true, NULL),
    ));
    
    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
    }
    
    $queryParams = array_merge(array(str_repeat('s', count($queryParams))), $queryParams);
    
    $result = executeStatement($mysqli, true, $sqlCount . $sqlConditions, $queryParams);
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
    $result = executeStatement($mysqli, true, $sql . $sqlConditions, $queryParams);
    
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

function getChecks($mysqli) {
    $params = getAllParameters(array(
        array('show', 'list_idps', false),
        array('f_order', 'entityID', false),
        array('f_id_status', 'All', true),
        array('f_entityID', 'All', false),
        array('f_spEntityID', 'All', false),
        array('f_check_time', 'All', false),
        array('f_http_status_code', 'All', false),
        array('f_check_result', 'All', false),
    ));

    $sqlCount = "SELECT COUNT(*) FROM EntityChecks";
    $sql = "SELECT * FROM EntityChecks";

    $sqlConditions = "";
    $queryParams = array();
    addAllSqlConditions($sqlConditions, $queryParams, $params, array(
        array('f_id_status', 'checkResult', false, NULL),
        array('f_entityID', 'entityID', true, NULL),
        array('f_spEntityID', 'spEntityID', true, NULL),
        array('f_check_time', 'checkTime', false, array('1' => 'DATE(lastCheck) = curdate()', '2' => 'DATE(lastCheck) = curdate() - interval 1 day')),
        array('f_http_status_code', 'httpStatusCode', false, NULL),
        array('f_check_result', 'checkResult', true, NULL),
    ));

    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
    }

    $queryParams = array_merge(array(str_repeat('s', count($queryParams))), $queryParams);

    // find out how many rows are in the table
    $result = executeStatement($mysqli, true, $sqlCount . $sqlConditions, $queryParams);
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
    $result = executeStatement($mysqli, true, $sql . $sqlConditions, $queryParams);

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

$confArray = parse_ini_file('../properties.ini', true);
$dbConnection = $confArray['db_connection'];
$mysqli = getDbConnection($dbConnection);

$action = getParameter('action', 'entities');
if ($action == 'entities') {
    getEntities($mysqli);
}
elseif ($action == 'checks') {
    getChecks($mysqli);
}
else {
    throw new Exception("Wrong action, valid actions are entities or checks.");
}
?>
