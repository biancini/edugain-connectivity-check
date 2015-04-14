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

$conf_array = parse_ini_file('../properties.ini', true);
$dbConnection = $conf_array['db_connection'];

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
    $url .= addParameterToQuery($params, 'f_registrationAuthority', $excludeParam);
    $url .= addParameterToQuery($params, 'f_displayName', $excludeParam);
    $url .= addParameterToQuery($params, 'f_ignore_entity', $excludeParam);
    $url .= addParameterToQuery($params, 'f_last_check', $excludeParam);
    $url .= addParameterToQuery($params, 'f_current_result', $excludeParam);

    return $url;
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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link media="screen" href="css/ui.switchbutton.min.css" type="text/css" rel="stylesheet"/>
<link media="screen" href="css/eduroam.css" type="text/css" rel="stylesheet"/>
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery.tmpl.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.switchbutton.min.js"></script>
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
                $params["show"] = getParameter('show', 'list_idps');
                $params["f_order"] = getParameter('f_order', 'currentResult');
                $params["f_order_direction"] = getParameter('f_order_direction', 'DESC');
                $params["f_id_status"] = getParameter('f_id_status', 'All', true);
                $params["f_entityID"] = getParameter('f_entityID', 'All');
                $params["f_registrationAuthority"] = getParameter('f_registrationAuthority', 'All');
                $params["f_displayName"] = getParameter('f_displayName', 'All');
                $params["f_ignore_entity"] = getParameter('f_ignore_entity', 'All');
                $params["f_last_check"] = getParameter('f_last_check', 'All');
                $params["f_current_result"] = getParameter('f_current_result', 'All');
                ?>
                <div class="admin_naslov">Identity providers | <a href="test.php">All IdP test results</a> | <a href="https://wiki.edugain.org/index.php?title=Metadata_Consumption_Check_Service" target="_blank">Instructions</a></div>
                <div class="admin_naslov" style="background-color: #e9e9e9;">Show IdPs with status:
                <a href="<?=getCurrentUrl($params, ["f_id_status", "f_ignore_entity"])?>&f_id_status=3 - HTTP-Error,3 - CURL-Error" title="HTTP or CURL error while accessing IdP login page from check script" style="color:red">Error</a> | 
                <a href="<?=getCurrentUrl($params, ["f_id_status", "f_ignore_entity"])?>&f_id_status=2 - FORM-Invalid" title="Login form returned by IdP is invalid" style="color:orange">Warning</a> |
                <a href="<?=getCurrentUrl($params, ["f_id_status", "f_ignore_entity"])?>&f_id_status=1 - OK" style="color:green" title="Parses correctly all eduGAIN metadata">OK</a> | 
                <a href="<?=getCurrentUrl($params, ["f_id_status", "f_ignore_entity"])?>&f_ignore_entity=True" style="color:grey" title="Show IdP disabled from MCCS checks">Disabled</a> |
                <a href="<?=getCurrentUrl($params, ["f_id_status", "f_ignore_entity"])?>&f_id_status=">Show all</a></div>
<div class="message"></div>
<form name="list_idpsFRM" action="<?=getCurrentUrl($params)?>" method="post">
<table class="list_table">
    <tr>
        <th><a href="<?=getUrlDirection($params, "displayName")?>" title="Sort by display name.">Display Name</a>
        <img src="images/<?= ($params["f_order"] == "displayName") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "entityID")?>" title="Sort by entityID.">entityID</a>
        <img src="images/<?= ($params["f_order"] == "entityID") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "registrationAuthority")?>" title="Sort by registration authority.">Registration Authority</a>
        <img src="images/<?= ($params["f_order"] == "registrationAuthority") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th>Contacts</th>
        <th style="min-width: 100px"><a href="<?=getUrlDirection($params, "lastCheck")?>" title="Sort by last test.">Last Test</a>
        <img src="images/<?= ($params["f_order"] == "lastCheck") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th><a href="<?=getUrlDirection($params, "currentResult")?>" title="Sort by current result.">Current Result</a>
        <img src="images/<?= ($params["f_order"] == "currentResult") ? strtolower($params["f_order_direction"]) : "sort" ?>.gif"/></th>
        <th>Tests</th>
    </tr>
    <tr>
        <td class="filter_td">
            <input type="text" name="f_displayName" value="<?= $params['f_displayName'] == "All" ? "" : $params['f_displayName'] ?>"/>
        </td>
        <td class="filter_td">
            <input type="text" name="f_entityID" value="<?= $params['f_entityID'] == "All" ? "" : $params['f_entityID'] ?>" class="wide"/>
        </td>
            <td class="filter_td">
            <input type="text" name="f_registrationAuthority" value="<?= $params['f_registrationAuthority'] == "All" ? "" : $params['f_registrationAuthority'] ?>"/>
        </td>
            <td class="filter_td"><center><b>T</b>: Technical, <b>S</b>: Support</center></td>
        <td class="filter_td">&nbsp;<!--
            <select name="f_last_check">
                <option value="All" <?= $params['f_last_check'] == "All" ? "selected" : "" ?>>All</option>
                <option value="1" <?= $params['f_last_check'] == "1" ? "selected" : "" ?>>Today</option>
                <option value="2" <?= $params['f_last_check'] == "2" ? "selected" : "" ?>>Yesterday</option>
            </select>
         -->
        </td>
        <td class="filter_td">
            <select name="f_current_result">
                <option value="All" <?= $params['f_current_result'] == "All" ? "selected" : "" ?>>All</option>
                <option value="1 - OK" <?= $params['f_current_result'] == "1 - OK" ? "selected" : "" ?>>OK</option>
                <option value="2 - FORM-Invalid" <?= $params['f_current_result'] == "2 - FORM-Invalid" ? "selected" : "" ?>>FORM-Invalid</option>
                <option value="3 - HTTP-Error" <?= $params['f_current_result'] == "3 - HTTP-Error" ? "selected" : "" ?>>HTTP-Error</option>
                <option value="3 - CURL-Error" <?= $params['f_current_result'] == "3 - CURL-Error" ? "selected" : "" ?>>CURL-Error</option>
            </select>
        </td>
        <td class="filter_td" colspan="3"><input type="submit" name="filter" value="Search"  class="filter_gumb"/></td>
    </tr>
    <tr>
        <td class="filter_td" colspan="4">IdP data</td>
        <td class="filter_td" colspan="3">Last test results</td>
    </tr>
    <?php
    $sqlCount = "SELECT COUNT(*) FROM EntityDescriptors";
    $sql = "SELECT * FROM EntityDescriptors LEFT JOIN Federations ON EntityDescriptors.registrationAuthority = Federations.registrationAuthority";
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
    if ($params ['f_displayName'] && $params['f_displayName'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " displayName LIKE ?";
        array_push($queryParams, "%" . $params['f_displayName'] . "%");
    }
    if ($params['f_entityID'] && $params['f_entityID'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " entityID LIKE ?";
        array_push($queryParams, "%" . $params['f_entityID'] . "%");
    }
    if ($params['f_registrationAuthority'] && $params['f_registrationAuthority'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " EntityDescriptors.registrationAuthority LIKE ?";
        array_push($queryParams, "%" . $params['f_registrationAuthority'] . "%");
    }
    if ($params['f_ignore_entity'] && $params['f_ignore_entity'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " ignoreEntity = ?";
        array_push($queryParams, ($params['f_ignore_entity'] == "True" ? 1 : 0));
    }
    if ($params['f_last_check'] && $params['f_last_check'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        if ($params['f_last_check'] == "1") {
            $sqlConditions .= " DATE(lastCheck) = curdate()";
        }
        elseif ($params['f_last_check'] == "2") {
            $sqlConditions .= " DATE(lastCheck) = curdate() - interval 1 day";
        }
	else {
            // Do nothing
        }
    }
    if ($params['f_current_result'] && $params['f_current_result'] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        $sqlConditions .= " currentResult = ?";
        array_push($queryParams, $params['f_current_result']);
    }

    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY EntityDescriptors." . mysqli_real_escape_string($mysqli, $params['f_order']);
        $sqlConditions .= " " . mysqli_real_escape_string($mysqli, $params['f_order_direction']);
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
    
    $stmt = $mysqli->prepare($sql . $sqlConditions);
    if (!$stmt) {
        throw new Exception("Error: " . mysqli_error($mysqli));
    }
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
    $count = 1;

    while ($row = $result->fetch_assoc()) {
        if ($row['ignoreEntity'] == 1) {
            $color = "silver";
        }
        elseif ("1 - OK" == $row['currentResult']) {
            $color = "green";
        }
        elseif ("2 - FORM-Invalid" == $row['currentResult']) {
            $color = "yellow";
        }
        elseif ("3 - HTTP-Error" == $row['currentResult']) {
            $color = "red";
        }
        elseif ("3 - CURL-Error" == $row['currentResult']) {
            $color = "red";
        }
        else {
            $color = "white";
        }
        ?>
        <tr class="<?=$color?>">
                <td><?=$row['displayName']?></td>
                <td><?=$row['entityID']?></td>
                <td><?=$row['federationName']?><br/>
                    <?=$row['registrationAuthority']?></td>
                <td><?php
                $contacts = explode(",", $row['technicalContacts']);
                foreach ($contacts as $contact) {
                    if (!empty($contact)) {
                        print "<b>T</b>: <a href=\"mailto:" . $contact . "\">" . $contact . "</a><br/>";
                    }
                }

                $contacts = explode(",", $row['supportContacts']);
                foreach ($contacts as $contact) {
                    if (!empty($contact)) {
                        print "<b>S</b>: <a href=mailto:\"" . $contact . "\">" . $contact . "</a><br/>";
                     }
                }
            ?></td>
                <td><?=$row['lastCheck']?></td>
                <td><?=substr($row['currentResult'], 4)?></td>
            <td><a href="test.php?f_entityID=<?=$row['entityID']?>" title="View checks status for this entity.">View</a></td>
        </tr>
        <?php

        $count++;
    }
    ?>
    <tr>
        <td colspan="8" align="center">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="8" align="center">Records found: <?=$numrows?></td>
    </tr>
    <tr>
        <td colspan="8" align="center">
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

