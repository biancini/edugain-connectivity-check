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

$params = getAllParameters(array(
    array('show', 'list_idps', false),
    array('f_order', 'currentResult', false),
    array('f_order_direction', 'DESC', false),
    array('f_id_status', 'All', true),
    array('f_entityID', 'All', false),
    array('f_registrationAuthority', 'All', false),
    array('f_displayName', 'All', false),
    array('f_ignore_entity', 'All', false),
    array('f_last_check', 'All', false),
    array('f_current_result', 'All', false),
    array('rpp', 'All', false),
));

$confArray = parse_ini_file('../properties.ini', true);
$dbConnection = $confArray['db_connection'];
$mysqli = getDbConnection($dbConnection);

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
    $url .= addParameterToQuery($params, 'rpp', $excludeParam);

    return $url;
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
<script type="text/javascript">
function changeItemsPerPage(new_rpp) {
    var url = "<?=getCurrentUrl($params, ["rpp"])?>";
    if (url.indexOf('?') > -1) {
        url += '&rpp=' + new_rpp
    } else {
        url += '?rpp=' + new_rpp
    }
    window.location.href = url;
}
</script>
<title>eduGAIN Connectivity Check</title>
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
                <div class="admin_naslov"><h2>eduGAIN Connectivity Check service</h2></div>
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
    addAllSqlConditions($sqlConditions, $queryParams, $params, array(
        array('f_id_status', 'currentResult', false, NULL),
        array('f_displayName', 'displayName', true, NULL),
        array('f_entityID', 'entityID', true, NULL),
        array('f_registrationAuthority', 'EntityDescriptors.registrationAUthority', true, NULL),
        array('f_ignore_entity', 'ignoreEntity', false, array("True" => 1, "False" => 0)),
        array('f_last_check', 'lastCheck', false, array('1' => 'DATE(lastCheck) = curdate()', '2' => 'DATE(lastCheck) = curdate() - interval 1 day')),
        array('f_current_result', 'currentResult', true, NULL),
    ));

    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY EntityDescriptors." . mysqli_real_escape_string($mysqli, $params['f_order']);
        $sqlConditions .= " " . mysqli_real_escape_string($mysqli, $params['f_order_direction']);
    }

    $queryParams = array_merge(array(str_repeat('s', count($queryParams))), $queryParams);

    // find out how many rows are in the table
    $result = executeStatement($mysqli, true, $sqlCount . $sqlConditions, $queryParams);
    $numrows = $result->fetch_row()[0];

    $rowsperpage = getParameter('rpp', '30');
    if ($rowsperpage == 'All') {
        $rowsperpage = $numrows;
    }
    $rowsperpage = is_numeric($rowsperpage) ? (int) $rowsperpage : 30;
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
            <?php
            if ($row['ignoreEntity'] == 0) {
            ?>
                <td><?=$row['lastCheck']?></td>
                <td><?=substr($row['currentResult'], 4)?></td>
                <td><a href="test.php?f_entityID=<?=$row['entityID']?>" title="View checks status for this entity.">View</a></td>
            <?php
            } else {
            ?>
                <td colspan="2"><?=$row['ignoreReason']?></td>
                <td>&nbsp;</td>
            <?php
            } 
            ?>
        </tr>
        <?php

        $count++;
    }
    ?>
    <tr>
        <td colspan="8" align="center">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="8" align="center">
        Records found: <?=$numrows?>
        (showing pages of <select id="rpp" name="rpp" onchange="changeItemsPerPage(this.value)">
        <?php
        foreach (array(10, 20, 30, 40, 50, 100) as $rpp) {
            print "<option value=\"$rpp\"";
            if ($rpp == $rowsperpage) {
                print " selected ";
            }
            print ">$rpp</option>";
        } ?>
        <option value="All" <?php if ($numrows == $rowsperpage) { ?> selected <?php } ?>>All</option>
        </select> elements)
        </td>
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

<?php
$mysqli->close();
?>
