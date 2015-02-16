<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
$conf_array = parse_ini_file('../properties.ini', true);
?>
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
				 $show='list_idps';
				 $f_coc_found=1;
				 $f_last_seen=1;
				 $f_order='entityID';
				 $f_id_status=2;
				?>
				<div class="admin_naslov">Identity providers | <a href="?show=list_idp_tests">All IdP test results</a> | <a href="https://wiki.edugain.org/Monitoring_tool_instructions" target="_blank">Instructions</a></div>
				<div class="admin_naslov" style="background-color: #e9e9e9;">Show IdPs with status:
				<a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=<?=$f_order?>&f_id_status=1" style="color:green" title="All REQUIRED and RECOMMENDED CoC SAML2 metadata elements in place + the privacy statement has a link that refers to the defined CoC URL">green</a> | 
				<a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=<?=$f_order?>&f_id_status=1" title="No CoC EntityAttribute in place (the SP doesn't even claim conformance with the CoC so the CoC spec is not applicable)" style="color:black">white</a> | 
				<a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=<?=$f_order?>&f_id_status=3" title="One or more of RECOMMENDED elements missing" style="color:yellow">yellow</a> |
				<a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=<?=$f_order?>&f_id_status=4" title="Something REQUIRED is missing (including a lang='en' attribute in a RECOMMENDED element such as mdui:displayname or description) or the privacy statement doesn't link to the defined CoC URL" style="color:red">red</a> | 
				<a href="?show=<?=$show?>">Show all records</a></div>
<div class="message"></div>
<form name="list_idpsFRM" action="?show=<?=$show?>" method="post">
<table class="list_table">
	<tr>
		<td class="filter_td">
			<input type="text" name="f_entityID" value="" class="wide"/>
		</td>
	        <td class="filter_td">
			<input type="text" name="f_registrationAuthority" value=""/>
		</td>
	        <td class="filter_td">
			<input type="text" name="f_DisplayName" value=""/>
		</td>
		<td class="filter_td">&nbsp;
			
		</td>
		<td class="filter_td">
			<select name="f_last_seen">
				<option value="">All</option>
				<option value="1"selected>Last 30 days</option>
			</select>
		</td>
		<td class="filter_td">
			<select name="f_coc_found">
				<option value="">All</option>
				<option value="1" selected>Yes</option>
				<option value="2" >No</option>
			</select>
		</td>
		<td class="filter_td">
			<select name="f_id_status">
				<option value="">All</option>
				<option value="1">no CoC EntityAttribute in place</option>
				<option value="2">All attributes present, privacy statement has a link to CoC</option>
				<option value="3">All required attributes present, one or more recommended attributes missing, privacy statement has a link to CoC</option>
				<option value="4">Required attribute is missing or privacy statement doesn't link to CoC</option>
				<option value="5">Other</option>

			</select>
		</td>
		<td class="filter_td">&nbsp;</td>
		<td class="filter_td">&nbsp;</td>
		<td class="filter_td"><input type="text" name="f_code" value="" class="narrow"/></td>
		<td class="filter_td"><input type="text" name="f_code_txt" value="" class="narrow" /></td>
		<td class="filter_td"><input type="text" name="f_content_type" value="" class="narrow" /></td>
		<td class="filter_td" colspan="3"><input type="submit" name="filter" value="Search"  class="filter_gumb"/></td>
	</tr>
	<tr>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=entityID" title="Sort by entityID.">entityID</a></th>
        	<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=registrationAuthority" title="Sort by registrationAuthority.">registrationAuthority</a></th>
	        <th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=DisplayName" title="Sort by DisplayName.">DisplayName</a></th>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=first_seen" title="Sort by first seen.">First seen</a></th>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=last_seen" title="Sort by last seen.">Last seen</a></th>
		<th title="CoC link found in privacy policy page">CoC found</th>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=status" title="Sort by status.">Status</a></th>
		<th>Comment</th>
		<th>PrivacyStatementURL</th>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=code" title="Sort by status.">Status code</a></th>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=code_txt" title="Sort by status text.">Status text</a></th>
		<th><a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&f_order=content_type" title="Sort by content type.">Content type</a></th>
		<th>View</th>
		<th>Headers</th>
		<th>Cookies</th>
	</tr>
	<tr>
		<td class="filter_td" colspan="5">SP data</td>
		<td class="filter_td" colspan="10">Last test results</td>
	</tr>
	<?php
	//SELECT con ciclo for
	?>
	<tr class="green">
		<td><a href="?f_id_sp=61&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">http://sp.lat.csc.fi</a></td>
        	<td>http://www.csc.fi/haka</td>
	        <td>LAT – Language Archive Tools</td>
		<td>2013-10-01 20:35:19</td>
		<td>2015-02-11 03:14:10</td>
		<td>Yes</td>
		<td>All attributes present, privacy statement has a link to CoC</td>
		<td>&nbsp;</td>
		<td><a target="_blank" href="https://lat.csc.fi/corpora/Info/Lat_Privacy_Policy.html">https://lat.csc.fi/corpora/Info/Lat_Privacy_Policy.html</a></td>
		<td>200</td>
		<td>OK</td>
		<td>text/html</td>
		<td><a target="_blank" href="?sp_test_id=814881&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a></td>
		<td><a target="_blank" href="?sp_test_id=814881&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a></td>
		<td><a target="_blank" href="?sp_test_id=814881&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a></td>
	</tr>
	<?php
	//end SELECT
	?>
	<tr>
		<td colspan="15" align="center">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="15" align="center">Records found: 38</td>
	</tr>
	<tr>
		<td colspan="15" align="center">1&nbsp;
			<a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&page=2&f_order=<?=$f_order?>" title="Page 2.">2</a>&nbsp;		
			&nbsp;<a href="?show=<?=$show?>&f_coc_found=<?=$f_coc_found?>&f_last_seen=<?=$f_last_seen?>&page=2&f_order=<?=$f_order?>" title="Next page">&gt;</a>
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

