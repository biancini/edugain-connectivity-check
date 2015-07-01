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
    array('show', 'list_idp_tests', false),
    array('f_order', 'entityID', false),
    array('f_order_direction', 'DESC', false),
    array('f_id_status', 'All', true),
    array('f_entityID', 'All', false),
    array('f_spEntityID', 'All', false),
    array('f_check_time', 'All', false),
    array('f_http_status_code', 'All', false),
    array('f_check_result', 'All', false),
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
    $url .= addParameterToQuery($params, 'f_spEntityID', $excludeParam);
    $url .= addParameterToQuery($params, 'f_check_time', $excludeParam);
    $url .= addParameterToQuery($params, 'f_http_status_code', $excludeParam);
    $url .= addParameterToQuery($params, 'f_check_result', $excludeParam);
    $url .= addParameterToQuery($params, 'rpp', $excludeParam);

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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link media="screen" href="css/eduroam.css" type="text/css" rel="stylesheet"/>
<title>eduGAIN Connectivity Check</title>
<script type="text/javascript">
function changeItemsPerPage(new_rpp) {
    var url = "<?=getCurrentUrl($params, ["rpp"])?>";
    if (url.indexOf('?') > -1){
        url += '&rpp=' + new_rpp
    } else {
        url += '?rpp=' + new_rpp
    }
    window.location.href = url;
}
</script>
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
    $sqlCount = "SELECT COUNT(*) FROM EntityChecks";
    $sql = "SELECT * FROM EntityChecks";

    $sqlConditions = "";
    $queryParams = array();
    addAllSqlConditions($sqlConditions, $queryParams, $params, array(
        array('f_id_status', 'checkResult', false, NULL),
        array('f_entityID', 'entityID', true, NULL),
        array('f_spEntityID', 'spEntityID', true, NULL),
        array('f_check_time', 'checkTime', false, array('1' => 'DATE(checkTime) = curdate()', '2' => 'DATE(checkTime) = curdate() - interval 1 day')),
        array('f_http_status_code', 'httpStatusCode', false, NULL),
        array('f_check_result', 'checkResult', true, NULL),
    ));

    if ($params['f_order']) {
        $sqlConditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
        $sqlConditions .= " " . mysqli_real_escape_string($mysqli, $params['f_order_direction']);
    }

    $queryParams = array_merge(array(str_repeat('s', count($queryParams))), $queryParams);

    // find out how many rows are in the table 
    $result = executeStatement($mysqli, true, $sqlCount . $sqlConditions, $queryParams);
    $numrows = $result->fetch_row()[0];

    $rowsperpage = getParameter('rpp', '30');
    if ($rowsperpage == 'All') $rowsperpage = $numrows;
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
        <td colspan="9" align="center">
        Records found: <?=$numrows?>
        (showing pages of <select id="rpp" name="rpp" onchange="changeItemsPerPage(this.value)">
        <?php
        foreach (array(10, 20, 30, 40, 50, 100) as $rpp) {
            ?>
            <option value="<?=$rpp?>" <?php if ($rpp == $rowsperpage) { ?> selected <?php } ?>><?=$rpp?></option>
            <?php
        } ?>
        <option value="All" <?php if ($numrows == $rowsperpage) { ?> selected <?php } ?>>All</option>
        </select> elements)
        </td>
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

