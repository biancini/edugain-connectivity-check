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

function getCurrentUrl($params, $excludeParam=array()) {
	$url = $_SERVER['PHP_SELF'] . "?";

	$url .= "show=" . $params['show'];
	if (!in_array("f_order", $excludeParam) && array_key_exists("f_order", $params)) $url .= "&f_order=" . $params['f_order'];
	if (!in_array("f_order_direction", $excludeParam) && array_key_exists("f_order_direction", $params)) $url .= "&f_order_direction=" . $params['f_order_direction'];
	if (!in_array("f_id_status", $excludeParam) && array_key_exists("f_id_status", $params)) $url .= "&f_id_status=" . implode(",", $params['f_id_status']);
	if (!in_array("page", $excludeParam) && array_key_exists("page", $params)) $url .= "&page=" . $params['page'];
        if (!in_array("f_entityID", $excludeParam) && array_key_exists("f_entityID", $params)) $url .= "&f_entityID=" . $params['f_entityID'];
        if (!in_array("f_registrationAuthority", $excludeParam) && array_key_exists("f_registrationAuthority", $params)) $url .= "&f_registrationAuthority=" . $params['f_registrationAuthority'];
        if (!in_array("f_displayName", $excludeParam) && array_key_exists("f_displayName", $params)) $url .= "&f_displayName=" . $params['f_displayName'];
        if (!in_array("f_ignore_entity", $excludeParam) && array_key_exists("f_ignore_entity", $params)) $url .= "&f_ignore_entity=" . $params['f_ignore_entity'];
        if (!in_array("f_last_check", $excludeParam) && array_key_exists("f_last_check", $params)) $url .= "&f_last_check=" . $params['f_last_check'];
        if (!in_array("f_current_result", $excludeParam) && array_key_exists("f_current_result", $params)) $url .= "&f_current_result=" . $params['f_current_result'];

	return $url;
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
				//error_log(print_r($params, true));
				?>
				<div class="admin_naslov">Identity providers | <a href="test.php">All IdP test results</a> | <a href="https://wiki.edugain.org/index.php?title=Metadata_Consumption_Check_Service" target="_blank">Instructions</a></div>
				<div class="admin_naslov" style="background-color: #e9e9e9;">Show IdPs with status:
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=3 - HTTP-Error,3 - CURL-Error" title="HTTP or CURL error while accessing IdP login page from check script" style="color:red">Error</a> | 
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=2 - FORM-Invalid" title="Login form returned by IdP is invalid" style="color:orange">Warning</a> |
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=1 - OK" style="color:green" title="Parses correctly all eduGAIN metadata">OK</a> | 
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=">Show all</a></div>
<div class="message"></div>
<form name="list_idpsFRM" action="<?=getCurrentUrl($params)?>" method="post">
<table class="list_table">
	<tr>
        	<th><a href="<?=getCurrentUrl($params, ["f_order", "f_order_direction"])?>&f_order=displayName&f_order_direction=<?= ($params["f_order"] == "displayName" && $params["f_order_direction"] == "ASC") ? "DESC" : "ASC" ?>" title="Sort by display name.">Display Name</a></th>
		<th><a href="<?=getCurrentUrl($params, ["f_order", "f_order_direction"])?>&f_order=entityID&f_order_direction=<?= ($params["f_order"] == "entityID" && $params["f_order_direction"] == "ASC") ? "DESC" : "ASC" ?>" title="Sort by entityID.">entityID</a></th>
        	<th><a href="<?=getCurrentUrl($params, ["f_order", "f_order_direction"])?>&f_order=registrationAuthority&f_order_direction=<?= ($params["f_order"] == "registrationAuthority" && $params["f_order_direction"] == "ASC") ? "DESC" : "ASC" ?>" title="Sort by registration authority.">Registration Authority</a></th>
		<th>Contacts</th>
	        <th><a href="<?=getCurrentUrl($params, ["f_order", "f_order_direction"])?>&f_order=ignoreEntity&f_order_direction=<?= ($params["f_order"] == "ignoreEntity" && $params["f_order_direction"] == "ASC") ? "DESC" : "ASC" ?>" title="Sort by ignore entity.">Ignore Entity</a></th>
		<th><a href="<?=getCurrentUrl($params, ["f_order", "f_order_direction"])?>&f_order=lastCheck&f_order_direction=<?= ($params["f_order"] == "lastTest" && $params["f_order_direction"] == "ASC") ? "DESC" : "ASC" ?>" title="Sort by last test.">Last Test</a></th>
		<th><a href="<?=getCurrentUrl($params, ["f_order", "f_order_direction"])?>&f_order=currentResult&f_order_direction=<?= ($params["f_order"] == "currentResult" && $params["f_order_direction"] == "ASC") ? "DESC" : "ASC" ?>" title="Sort by current result.">Current Result</a></th>
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
	        <td class="filter_td">
			&nbsp;
		</td>
	        <td class="filter_td">
			<select name="f_ignore_entity">
				<option value="All" <?= $params['f_ignore_entity'] == "All" ? "selected" : "" ?>>All</option>
				<option value="True" <?= $params['f_ignore_entity'] == "True" ? "selected" : "" ?>>True</option>
				<option value="False" <?= $params['f_ignore_entity'] == "False" ? "selected" : "" ?>>False</option>
			</select>
		</td>
		<td class="filter_td">
			<select name="f_last_check">
				<option value="All" <?= $params['f_last_check'] == "All" ? "selected" : "" ?>>All</option>
				<option value="1" <?= $params['f_last_check'] == "1" ? "selected" : "" ?>>Last 30 days</option>
			</select>
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
		<td class="filter_td" colspan="4">Last test results</td>
	</tr>
	<?php
      	$sql_count = "SELECT COUNT(*) FROM EntityDescriptors";
	$sql = "SELECT * FROM EntityDescriptors LEFT JOIN Federations ON EntityDescriptors.registrationAuthority = Federations.registrationAuthority";
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
        if ($params['f_displayName'] && $params['f_displayName'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " displayName LIKE ?";
		array_push($query_params, "%" . $params['f_displayName'] . "%");
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
		$sql_conditions .= " EntityDescriptors.registrationAuthority LIKE ?";
		array_push($query_params, "%" . $params['f_registrationAuthority'] . "%");
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
		$sql_conditions .= " currentResult = ?";
		array_push($query_params, $params['f_current_result']);
	}

	if ($params['f_order']) {
		$sql_conditions .= " ORDER BY " . mysqli_real_escape_string($mysqli, $params['f_order']);
		$sql_conditions .= " " . mysqli_real_escape_string($mysqli, $params['f_order_direction']);
	}

	$query_params = array_merge(array(str_repeat('s', count($query_params))), $query_params);

	// find out how many rows are in the table
	$stmt = $mysqli->prepare($sql_count . $sql_conditions) or die("Error: " . mysqli_error($mysqli));
        if (count($query_params) > 1) {
		call_user_func_array(array($stmt, 'bind_param'), refValues($query_params)) or die("Error: " . mysqli_error($mysqli));
	}
	$stmt->execute() or die("Error: " . mysqli_error($mysqli));
	$result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));
	$numrows = $result->fetch_row()[0];

	$rowsperpage = 30;
	$totalpages = ceil($numrows / $rowsperpage);
	$page = getParameter('page', '1');
	$page = is_numeric($page) ? (int) $page : 1;
	if ($page > $totalpages) $page = $totalpages;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $rowsperpage;
	
	$sql_conditions .= " LIMIT " . $offset . " , " . $rowsperpage;
	$stmt = $mysqli->prepare($sql . $sql_conditions) or die("Error: " . mysqli_error($mysqli));
        if (count($query_params) > 1) {
		call_user_func_array(array($stmt, 'bind_param'), refValues($query_params)) or die("Error: " . mysqli_error($mysqli));
	}
	$stmt->execute() or die("Error: " . mysqli_error($mysqli));
	$result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));
	$count = 1;

	while ($row = $result->fetch_assoc()) {
		if ("1 - OK" == $row['currentResult']) $color = "green";
		elseif ("2 - FORM-Invalid" == $row['currentResult']) $color = "yellow";
		elseif ("3 - HTTP-Error" == $row['currentResult']) $color = "red";
		elseif ("3 - CURL-Error" == $row['currentResult']) $color = "red";
		else $color = "white";
		?>
		<tr class="<?=$color?>">
	        	<td><?=$row['displayName']?></td>
        		<td><?=$row['entityID']?></td>
	        	<td><?=$row['federationName']?><br/><?=$row['registrationAuthority']?></td>
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
	        	<td><!--div style="width: 110px"><?php
				if ($row['ignoreEntity'] == 1) {
					?><input type="checkbox" id="toggle_<?=$count?>" checked="checked" disabled="disabled"/><?php
				} else {
					?><input id="toggle_<?=$count?>" type="checkbox" disabled="disabled"/><?php
				}
			?></div><script>
			$("#toggle_<?=$count?>").switchbutton({
				checkedLabel: 'True',
				uncheckedLabel: 'False'
			}).change(function(){
				console.log("Toggle for <?=$row['entityID']?> " + ($(this).prop("checked") ? "checked" : "unchecked"));
			});
			</script--><?php
                                if ($row['ignoreEntity'] == 1) {
                                        ?>True<?php
                                } else {
                                        ?>False<?php
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
		<td colspan="10" align="center">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="10" align="center">Records found: <?=$numrows?></td>
	</tr>
	<tr>
		<td colspan="10" align="center">
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

