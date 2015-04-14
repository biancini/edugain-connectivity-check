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

function addParameterToQuery($params, $name, $excludeParam) {
    if (!in_array($name, $excludeParam) && array_key_exists("f_order", $params)) {
        if (is_array($params[$name])) {
            return "&" . $name . "=" . implode(",", $params[$name]);
        }
        return "&" . $name . "=" . $params[$name];
    }
    return "";
}

function getCurrentUrl($params, $excludeParam=array()) {
    $url = $_SERVER['PHP_SELF'] . "?";

    $url .= "show=" . $params['show'];
    $url .= addParameterToQuery($params, 'f_order', $excludeParam);
    $url .= addParameterToQuery($params, 'f_order_direction', $excludeParam);
    $url .= addParameterToQuery($params, 'f_id_status', $excludeParam);
    $url .= addParameterToQuery($params, 'page', $excludeParam);
    $url .= addParameterToQuery($params, 'f_entityID', $excludeParam);
    $url .= addParameterToQuery($params, 'f_spEntityID', $excludeParam);
    $url .= addParameterToQuery($params, 'f_check_time', $excludeParam);
    $url .= addParameterToQuery($params, 'f_http_status_code', $excludeParam);
    $url .= addParameterToQuery($params, 'f_check_result', $excludeParam);

    return $url;
}

function createCheckUrl($spACSurl, $httpRedirectServiceLocation, $spEntityID) {
    date_default_timezone_set('UTC');
    $date = date('Y-m-d\TH:i:s\Z');
    $id = md5($date.rand(1, 1000000));

    $samlRequest = '
          <samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
             AssertionConsumerServiceURL="'.$spACSurl.'"
             Destination="'.$httpRedirectServiceLocation.'"
             ID="_'.$id.'"
             IssueInstant="'.$date.'"
             ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Version="2.0">
             <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">
                '.$spEntityID.'
             </saml:Issuer>
             <samlp:NameIDPolicy AllowCreate="1"/>
          </samlp:AuthnRequest>';

    $samlRequest = preg_replace('/[\s]+/',' ',$samlRequest);
    $samlRequest = urlencode( base64_encode( gzdeflate( $samlRequest ) ) );
    $url = $httpRedirectServiceLocation."?SAMLRequest=".$samlRequest;
    return $url;
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

function getUrlDirection($params, $field) {
    $ret  = getCurrentUrl($params, ["f_order", "f_order_direction"]);
    $ret .= "&f_order=$field&f_order_direction=";
    if ($params["f_order"] == $field && $params["f_order_direction"] == "ASC") {
        $ret .= "DESC";
    }
    else {
        $ret .= "ASC";
    }
    return $ret;
}

function concatenateWhere($sqlConditions) {
    if (!strstr($sqlConditions, "WHERE")) {
        return " WHERE";
    }
    else {
        return " AND";
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link media="screen" href="css/eduroam.css" type="text/css" rel="stylesheet"/>
<title>edugain - mccs</title>
</head>
<body>
<center>
    <table class="container" cellpadding="5" cellspacing="0">
        <tr>
            <td>
                <a title="edugain home" href="http://www.geant.net/service/edugain/pages/home.aspx"><img src="images/edugain.png"></a>
            </td>
        </tr>
        <tr>
            <td class="body">
                <?php
                $params["show"] = getParameter('show', 'list_idp_tests');
                $params["f_order"] = getParameter('f_order', 'entityID');
                $params["f_order_direction"] = getParameter('f_order_direction', 'DESC');
                $params["f_id_status"] = getParameter('f_id_status', 'All', true);
                $params["f_entityID"] = getParameter('f_entityID', 'All');
                $params["f_spEntityID"] = getParameter('f_spEntityID', 'All');
                $params["f_check_time"] = getParameter('f_check_time', 'All');
                $params["f_http_status_code"] = getParameter('f_http_status_code', 'All');
                $params["f_check_result"] = getParameter('f_check_result', 'All');
                ?>
                <div class="admin_naslov"><a href="index.php">Identity providers</a> | All IdP test results | <a href="https://wiki.edugain.org/Metadata_Consumption_Check_Service" target="_blank">Instructions</a></div>
                <div class="admin_naslov" style="background-color: #e9e9e9;">Show Tests with status:
                    <a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=3 - HTTP-Error,3 - CURL-Error" title="HTTP or CURL error while accessing IdP login page from check script" style="color:red">Error</a> |
                    <a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=2 - FORM-Invalid" title="Login form returned by IdP is invalid" style="color:orange">Warning</a> |
                    <a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=1 - OK" style="color:green" title="Parses correctly all eduGAIN metadata">OK</a> |
                    <a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=">Show all</a></div>
<div class="message"></div>
<form name="list_testsFRM" action="<?=getCurrentUrl($params)?>" method="post">
<table class="list_table">
    <tr>
        <th><a href="<?=getUrlDirection($params, "entityID") ?>" title="Sort by entityID.">entityID</a>
            <img src="images/<?= ($params["f_order"] == "entityID") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "spEntityID") ?>" title="Sort by SP EntityID.">SP entityID</a>
            <img src="images/<?= ($params["f_order"] == "spEntityID") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "checkTime") ?>" title="Sort by check time entity.">Test Time</a>
            <img src="images/<?= ($params["f_order"] == "checkTime") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "httpStatusCode") ?>" title="Sort by HTTP status code.">HTTP status code</a>
            <img src="images/<?= ($params["f_order"] == "httpStatusCode") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "checkResult")?>" title="Sort by test result.">Test result</a>
            <img src="images/<?= ($params["f_order"] == "checkResult") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th>Test</th>
        <th>HTML</th>
    </tr>
    <tr>
        <td class="filter_td">
            <input type="text" name="f_entityID" value="<?= $params['f_entityID'] == "All" ? "" : $params['f_entityID'] ?>" class="wide"/>
        </td>
            <td class="filter_td">
            <input type="text" name="f_spEntityID" value="<?= $params['f_spEntityID'] == "All" ? "" : $params['f_spEntityID'] ?>"/>
        </td>
            <td class="filter_td">
            <select name="f_check_time">
                <option value="All" <?= $params['f_check_time'] == "All" ? "selected" : "" ?>>All</option>
                <option value="1" <?= $params['f_check_time'] == "1" ? "selected" : "" ?>>Today</option>
                <option value="2" <?= $params['f_check_time'] == "2" ? "selected" : "" ?>>Yesterday</option>
            </select>
        </td>
            <td class="filter_td">
            <input type="text" name="f_http_status_code" value="<?= $params['f_http_status_code'] == "All" ? "" : $params['f_http_status_code'] ?>"/>
        </td>
            <td class="filter_td">
            <select name="f_check_result">
                <option value="All" <?= $params['f_check_result'] == "All" ? "selected" : "" ?>>All</option>
                <option value="1 - OK" <?= $params['f_check_result'] == "1 - OK" ? "selected" : "" ?>>OK</option>
                <option value="2 - FORM-Invalid" <?= $params['f_check_result'] == "2 - FORM-Invalid" ? "selected" : "" ?>>FORM-Invalid</option>
                <option value="3 - HTTP-Error" <?= $params['f_check_result'] == "3 - HTTP-Error" ? "selected" : "" ?>>HTTP-Error</option>
                <option value="3 - CURL-Error" <?= $params['f_check_result'] == "3 - CURL-Error" ? "selected" : "" ?>>CURL-Error</option>
            </select>
        </td>
        <td>&nbsp;</td>
        <td class="filter_td"><input type="submit" name="filter" value="Search"  class="filter_gumb"/></td>
    </tr>
    <tr>
        <td class="filter_td" colspan="3">Test params</td>
        <td class="filter_td" colspan="4">Test results</td>
    </tr>
    <?php
          $sql_count = "SELECT COUNT(*) FROM EntityChecks";
    $sql = "SELECT * FROM EntityChecks";
    $sqlConditions = "";
    $query_params = array();
    if ($params['f_id_status']) {
        if (in_array("NULL", $params['f_id_status'])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " checkResult IS NULL";
        }
        if (!in_array("All", $params['f_id_status'])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " checkResult in (";
            foreach ($params['f_id_status'] as $val) {
                $sqlConditions .= (substr($sqlConditions, -1) != "(") ? ", " : "";
                $sqlConditions .= "?";
                array_push($query_params, $val);
            }
            $sqlConditions .= ")";
        }
    }
    if ($params['f_entityID'] && $params['f_entityID'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " entityID LIKE ?";
        array_push($query_params, "%" . $params['f_entityID'] . "%");
    }
    if ($params['f_spEntityID'] && $params['f_spEntityID'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " spEntityID LIKE ?";
        array_push($query_params, "%" . $params['f_spEntityID'] . "%");
    }
    if ($params['f_check_time'] && $params['f_check_time'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        if ($params['f_check_time'] == "1") {
            $sqlConditions .= " DATE(checkTime) = curdate()";
        }
        elseif ($params['f_check_time'] == "2") {
            $sqlConditions .= " DATE(checkTime) = curdate() - interval 1 day";
        }
        else {
            // Do nothing
        }
    }
    if ($params['f_http_status_code'] && $params['f_http_status_code'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " httpStatusCode = ?";
        array_push($query_params, $params['f_http_status_code']);
    }
    if ($params['f_check_result'] && $params['f_check_result'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " checkResult = ?";
        array_push($query_params, $params['f_check_result']);
    }

    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
        $sqlConditions .= " " . mysqli_real_escape_string($mysqli, $params['f_order_direction']);
    }

    $query_params = array_merge(array(str_repeat('s', count($query_params))), $query_params);

    // find out how many rows are in the table 
    $stmt = $mysqli->prepare($sql_count . $sqlConditions);
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (count($query_params) > 1 && !call_user_func_array(array($stmt, 'bind_param'), refValues($query_params))) {
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
    if (count($query_params) > 1 && !call_user_func_array(array($stmt, 'bind_param'), refValues($query_params))) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    if (!$stmt->execute()) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }

    while ($row = $result->fetch_assoc()) {
        if ("1 - OK" == $row['checkResult']) {
            $color = "green";
        }
        elseif ("2 - FORM-Invalid" == $row['checkResult']) {
            $color = "yellow";
        }
        elseif ("3 - HTTP-Error" == $row['checkResult']) {
            $color = "red";
        }
        elseif ("3 - CURL-Error" == $row['checkResult']) {
            $color = "red";
        }
        else {
            $color = "white";
        }
        ?>
        <tr class="<?=$color?>">
                <td><?=$row['entityID']?></td>
                <td><?=$row['spEntityID']?></td>
                <td><?=$row['checkTime']?></td>
                <td><?=$row['httpStatusCode']?></td>
                <td><?=substr($row['checkResult'], 4)?></td>
                <td><a href="<?=createCheckUrl($row['acsUrls'], $row['serviceLocation'], $row['spEntityID'])?>" target="_blank">Perform test yourself</a></td>
                <td><a href="html.php?id=<?=$row['id']?>" target="_blank">Show HTML returned for this test</a></td>
        </tr>
        <?php
    }
    ?>
    <tr>
        <td colspan="9" align="center">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="9" align="center">Records found: <?=$numrows?></td>
    </tr>
    <tr>
        <td colspan="9" align="center">
            <?php
                $range = 3;
                if ($page > 1) {
                    echo " <a href='".getCurrentUrl($params, ["page"]) . "&page=1' title='First page'>&lt;&lt;</a> ";
                    $prevpage = $page - 1;
                    echo " <a href='".getCurrentUrl($params, ["page"]) . "&page=$prevpage' title='Previous page'>&lt;</a> ";
                }

                for ($x = ($page - $range); $x < (($page + $range) + 1); $x++) {
                    if (($x > 0) && ($x <= $totalpages)) {
                        if ($x == $page) {
                            echo " $x ";
                        } else {
                            echo " <a href='".getCurrentUrl($params, ["page"]) . "&page=$x' title='Page $x'>$x</a> ";
                        }
                    }
                }
                 
                if ($page != $totalpages) {
                    $nextpage = $page + 1;
                    echo " <a href='".getCurrentUrl($params, ["page"]) . "&page=$nextpage' title='Next page'>&gt;</a> ";
                    echo " <a href='".getCurrentUrl($params, ["page"]) . "&page=$totalpages' title='Last page'>&gt;&gt;</a> ";
                }
            ?>
        </td>
    </tr>
</table>
</form>    
            </td>
        </tr>
    </table>
</center>
</body>
</html>

