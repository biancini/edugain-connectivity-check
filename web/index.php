<?php
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
	$value = (array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : $default_value);

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
	if (!in_array("f_id_status", $excludeParam) && array_key_exists("f_id_status", $params)) $url .= "&f_id_status=" . implode(",", $params['f_id_status']);
	if (!in_array("page", $excludeParam) && array_key_exists("page", $params)) $url .= "&page=" . $params['page'];
        if (!in_array("f_entityID", $excludeParam) && array_key_exists("f_entityID", $params)) $url .= "&f_entityID=" . $params['f_entityID'];
        if (!in_array("f_registrationAuthority", $excludeParam) && array_key_exists("f_registrationAuthority", $params)) $url .= "&f_registrationAuthority=" . $params['f_registrationAuthority'];
        if (!in_array("f_displayName", $excludeParam) && array_key_exists("f_displayName", $params)) $url .= "&f_displayName=" . $params['f_displayName'];
        if (!in_array("f_ignore_entity", $excludeParam) && array_key_exists("f_ignore_entity", $params)) $url .= "&f_ignore_entity=" . $params['f_ignore_entity'];
        if (!in_array("f_last_check", $excludeParam) && array_key_exists("f_last_check", $params)) $url .= "&f_last_check=" . $params['f_last_check'];
        if (!in_array("f_current_result", $excludeParam) && array_key_exists("f_current_result", $params)) $url .= "&f_current_result=" . $params['f_current_result'];
        if (!in_array("f_previous_result", $excludeParam) && array_key_exists("f_previous_result", $params)) $url .= "&f_previous_result=" . $params['f_previous_result'];

	return $url;
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
				?>
				<div class="admin_naslov">Identity providers | <a href="test.php">All IdP test results</a> | <a href="https://wiki.edugain.org/Monitoring_tool_instructions" target="_blank">Instructions</a></div>
				<div class="admin_naslov" style="background-color: #e9e9e9;">Show IdPs with status:
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=1 - OK" style="color:green" title="Parses correctly all eduGAIN metadata">green</a> | 
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=NULL" title="No checks performed" style="color:black">white</a> | 
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=2 - FORM-Invalid" title="Login form returned by IdP is invalid" style="color:yellow">yellow</a> |
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=3 - HTTP-Error,3 - CURL-Error" title="HTTP or CURL error while accessing IdP login page from check script" style="color:red">red</a> | 
				<a href="<?=getCurrentUrl($params, ["f_id_status"])?>&f_id_status=">Show all records</a></div>
<div class="message"></div>
<form name="list_idpsFRM" action="<?=getCurrentUrl($params)?>" method="post">
<table class="list_table">
	<tr>
		<td class="filter_td">
			<input type="text" name="f_entityID" value="<?= $params['f_entityID'] == "All" ? "" : $params['f_entityID'] ?>" class="wide"/>
		</td>
	        <td class="filter_td">
			<input type="text" name="f_registrationAuthority" value="<?= $params['f_registrationAuthority'] == "All" ? "" : $params['f_registrationAuthority'] ?>"/>
		</td>
	        <td class="filter_td">
			<input type="text" name="f_displayName" value="<?= $params['f_displayName'] == "All" ? "" : $params['f_displayName'] ?>"/>
		</td>
	        <td class="filter_td">
			&nbsp;
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
		<td class="filter_td">
			<select name="f_previous_result">
				<option value="All" <?= $params['f_previous_result'] == "All" ? "selected" : "" ?>>All</option>
				<option value="1 - OK" <?= $params['f_previous_result'] == "1 - OK" ? "selected" : "" ?>>OK</option>
				<option value="2 - FORM-Invalid" <?= $params['f_previous_result'] == "2 - FORM-Invalid" ? "selected" : "" ?>>FORM-Invalid</option>
				<option value="3 - HTTP-Error" <?= $params['f_previous_result'] == "3 - HTTP-Error" ? "selected" : "" ?>>HTTP-Error</option>
				<option value="3 - CURL-Error" <?= $params['f_previous_result'] == "3 - CURL-Error" ? "selected" : "" ?>>CURL-Error</option>
			</select>
		</td>
		<td class="filter_td" colspan="3"><input type="submit" name="filter" value="Search"  class="filter_gumb"/></td>
	</tr>
	<tr>
		<th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=entityID" title="Sort by entityID.">entityID</a></th>
        	<th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=registrationAuthority" title="Sort by registration authority.">Registration authority</a></th>
        	<th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=displayName" title="Sort by display name.">Display name</a></th>
		<th>technicalContacts</th>
		<th>supportContacts</th>
	        <th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=ignoreEntity" title="Sort by ignore entity.">Ignore entity</a></th>
		<th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=lastCheck" title="Sort by last test.">Last test</a></th>
		<th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=currentResult" title="Sort by current result.">Current result</a></th>
		<th><a href="<?=getCurrentUrl($params, ["f_order"])?>&f_order=previousResult" title="Sort by previous result.">Previous result</a></th>
		<th>Tests</th>
	</tr>
	<tr>
		<td class="filter_td" colspan="5">IdP data</td>
		<td class="filter_td" colspan="5">Last test results</td>
	</tr>
	<?php
      	$sql_count = "SELECT COUNT(*) FROM EntityDescriptors";
	$sql = "SELECT * FROM EntityDescriptors";
	$sql_conditions = "";
	if ($params['f_id_status']) {
		if (in_array("NULL", $params['f_id_status'])) {
			if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
			else $sql_conditions .= " AND";
			$sql_conditions .= " currentResult IS NULL";
		}
		elseif (!in_array("All", $params['f_id_status'])) {
			if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
			else $sql_conditions .= " AND";
			$sql_conditions .= " currentResult in ('".implode("','", $params['f_id_status'])."')";
		}
	}
        if ($params['f_entityID'] && $params['f_entityID'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " entityID LIKE '%" . $params['f_entityID'] . "%'";
	}
        if ($params['f_registrationAuthority'] && $params['f_registrationAuthority'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " registrationAuthority LIKE '%" . $params['f_registrationAuthority'] . "%'";
	}
        if ($params['f_displayName'] && $params['f_displayName'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " displayName LIKE '%" . $params['f_displayName'] . "%'";
	}
        if ($params['f_ignore_entity'] && $params['f_ignore_entity'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " ignoreEntity = " . ($params['f_ignore_entity'] == "True" ? 1 : 0);
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
		$sql_conditions .= " currentResult = '" . $params['f_current_result'] . "'";
	}
        if ($params['f_previous_result'] && $params['f_previous_result'] != "All") {
		if (!strstr($sql_conditions, "WHERE")) $sql_conditions .= " WHERE";
		else $sql_conditions .= " AND";
		$sql_conditions .= " previousResult = '" . $params['f_previous_result'] . "'";
	}

	if ($params['f_order']) {
		$sql_conditions .= " ORDER BY " . $params['f_order'];
	}

	// find out how many rows are in the table 
	$result = $mysqli->query($sql_count . $sql_conditions) or error_log("Error: " . $sql_count . $sql_conditions . ": " . mysqli_error($mysqli));
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
	$result = $mysqli->query($sql . $sql_conditions) or error_log("Error: " . $sql . $sql_conditions . ": " . mysqli_error($mysqli));

	while ($row = $result->fetch_assoc()) {
		if ("1 - OK" == $row['currentResult']) $color = "green";
		elseif ("2 - FORM-Invalid" == $row['currentResult']) $color = "yellow";
		elseif ("3 - HTTP-Error" == $row['currentResult']) $color = "red";
		elseif ("3 - CURL-Error" == $row['currentResult']) $color = "red";
		else $color = "white";
		?>
		<tr class="<?=$color?>">
        		<td><?=$row['entityID']?></td>
	        	<td><?=$row['registrationAuthority']?></td>
	        	<td><?=$row['displayName']?></td>
        		<td><?php
				$contacts = explode(",", $row['technicalContacts']);
				foreach ($contacts as $contact) {
					print "<a href=\"mailto:" . $contact . "\">" . $contact . "</a><br/>";
				}
			?></td>
        		<td><?php
				$contacts = explode(",", $row['supportContacts']);
				foreach ($contacts as $contact) {
					print "<a href=mailto:\"" . $contact . "\">" . $contact . "</a><br/>";
				}
			?></td>
	        	<td><?=$row['ignoreEntity'] == 1 ? "True" : "False"?></td>
        		<td><?=$row['lastCheck']?></td>
        		<td><?=substr($row['currentResult'], 4)?></td>
	        	<td><?=substr($row['previousResult'], 4)?></td>
			<td><a href="test.php?f_entityID=<?=$row['entityID']?>" title="View checks status for this entity.">View</a></td>
		</tr>
		<?php
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

