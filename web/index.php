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
	<tr class="green">
		<td><a href="?f_id_sp=61&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">http://sp.lat.csc.fi</a></td>
        <td>
			http://www.csc.fi/haka
		</td>
        <td>
			LAT – Language Archive Tools
		</td>
		<td>
			2013-10-01 20:35:19
		</td>
		<td>
			2015-02-11 03:14:10
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://lat.csc.fi/corpora/Info/Lat_Privacy_Policy.html">https://lat.csc.fi/corpora/Info/Lat_Privacy_Policy.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814881&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814881&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814881&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=111&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">http://weblogin2.geant.net/adfs/services/trust</a>
		</td>
        <td>
			http://ukfederation.org.uk
		</td>
        <td>
			GÉANT Intranet
		</td>
		<td>
			2014-03-16 12:07:04
		</td>
		<td>
			2015-02-11 03:14:43
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="http://weblogin2.geant.net/PrivacyPolicy/GEANTIntranetPrivacyPolicy.htm">http://weblogin2.geant.net/PrivacyPolicy/GEANTIntranetPrivacyPolicy.htm</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814922&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814922&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814922&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=126&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://attribute-viewer.aai.switch.ch/interfederation-test/shibboleth</a>
		</td>
        <td>
			http://rr.aai.switch.ch/
		</td>
        <td>
			AAI Viewer Interfederation Test
		</td>
		<td>
			2014-05-14 19:07:08
		</td>
		<td>
			2015-02-11 03:14:21
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://attribute-viewer.aai.switch.ch/interfederation-test/privacy-statement.html">https://attribute-viewer.aai.switch.ch/interfederation-test/privacy-statement.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814890&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814890&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814890&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=62&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://clarin.ids-mannheim.de/shibboleth</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			Institut für Deutsche Sprache (IDS) - CLARIN services
		</td>
		<td>
			2013-10-01 20:35:19
		</td>
		<td>
			2015-02-11 03:13:53
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://clarin.ids-mannheim.de/privacy.html">https://clarin.ids-mannheim.de/privacy.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814861&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814861&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814861&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=181&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://clarin.oeaw.ac.at/shibboleth</a>
		</td>
        <td>
			http://eduid.at
		</td>
        <td>
			CLARIN Centre Vienna
		</td>
		<td>
			2014-10-18 05:07:06
		</td>
		<td>
			2015-02-11 03:14:37
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://clarin.oeaw.ac.at/ccv/privacy">https://clarin.oeaw.ac.at/ccv/privacy</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814912&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814912&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814912&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate">View</a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=116&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://colreg.de.dariah.eu/colreg</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			Collection Registry
		</td>
		<td>
			2014-04-11 11:07:05
		</td>
		<td>
			2015-02-11 03:13:54
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://de.dariah.eu/ServicePrivacyPolicy">https://de.dariah.eu/ServicePrivacyPolicy</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814862&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814862&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814862&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate">View</a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=117&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://de.dariah.eu/shibboleth</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			DARIAH DE Portal
		</td>
		<td>
			2014-04-11 11:07:05
		</td>
		<td>
			2015-02-11 03:13:54
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://de.dariah.eu/ServicePrivacyPolicy">https://de.dariah.eu/ServicePrivacyPolicy</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814863&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814863&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814863&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=118&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://dev2.dariah.eu/shibboleth</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			Dariah Wiki
		</td>
		<td>
			2014-04-11 11:07:05
		</td>
		<td>
			2015-02-11 03:13:55
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://de.dariah.eu/ServicePrivacyPolicy">https://de.dariah.eu/ServicePrivacyPolicy</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814864&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814864&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814864&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=85&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://doccom.iml.unibe.ch/shibboleth</a>
		</td>
        <td>
			http://rr.aai.switch.ch/
		</td>
        <td>
			DOCCOM  German University Bern
		</td>
		<td>
			2013-12-11 10:22:35
		</td>
		<td>
			2015-02-11 03:14:20
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://doccom.iml.unibe.ch/Customizing/global/agreement/privacypolicy_en.html">https://doccom.iml.unibe.ch/Customizing/global/agreement/privacypolicy_en.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814889&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814889&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814889&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=115&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://educonf-directory.geant.net/simplesaml/module.php/saml/sp/metadata.php/eduCONF</a>
		</td>
        <td>
			http://ukfederation.org.uk
		</td>
        <td>
			eduCONF
		</td>
		<td>
			2014-04-10 15:07:09
		</td>
		<td>
			2015-02-11 03:14:43
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://educonf-directory.geant.net/privacy_policy.php">https://educonf-directory.geant.net/privacy_policy.php</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814923&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814923&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814923&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate">View</a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=63&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://filesender.funet.fi</a>
		</td>
        <td>
			http://www.csc.fi/haka
		</td>
        <td>
			Funet FileSender
		</td>
		<td>
			2013-10-01 20:35:20
		</td>
		<td>
			2015-02-11 03:14:13
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://filesender.funet.fi/privacypolicy.html">https://filesender.funet.fi/privacypolicy.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814883&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814883&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814883&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=64&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://foodl.org/simplesaml/module.php/saml/sp/metadata.php/saml</a>
		</td>
        <td>
			http://feide.no/
		</td>
        <td>
			Foodle
		</td>
		<td>
			2013-10-01 20:35:21
		</td>
		<td>
			2015-02-11 03:14:18
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://rnd.feide.no/software/foodle/foodle-privacy-policy/">https://rnd.feide.no/software/foodle/foodle-privacy-policy/</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814887&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814887&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814887&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate">View</a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=169&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://fs-elcira-srv01.dir.garr.it/simplesamlphp/module.php/saml/sp/metadata.php/default-sp</a>
		</td>
        <td>
			http://www.idem.garr.it/
		</td>
        <td>
			Filesender for ELCIRA
		</td>
		<td>
			2014-09-19 05:07:08
		</td>
		<td>
			2015-02-11 03:14:27
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://fs-elcira-srv01.dir.garr.it/elcira/privacy_en.html">https://fs-elcira-srv01.dir.garr.it/elcira/privacy_en.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814903&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814903&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814903&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=171&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://gitlab-dev.in2p3.fr/sp</a>
		</td>
        <td>
			https://federation.renater.fr/
		</td>
        <td>
			IN2P3 - Gitlab dev
		</td>
		<td>
			2014-10-03 05:07:11
		</td>
		<td>
			2015-02-11 03:13:50
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://gitlab-dev.in2p3.fr/about/privacy.html">https://gitlab-dev.in2p3.fr/about/privacy.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814859&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814859&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814859&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=173&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://gitlab.in2p3.fr/sp</a>
		</td>
        <td>
			https://federation.renater.fr/
		</td>
        <td>
			IN2P3 - Gitlab
		</td>
		<td>
			2014-10-08 05:11:50
		</td>
		<td>
			2015-02-11 03:13:49
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://gitlab.in2p3.fr/about/privacy.html">https://gitlab.in2p3.fr/about/privacy.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814858&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814858&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814858&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=176&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://lists.geant.net</a>
		</td>
        <td>
			https://federation.renater.fr/
		</td>
        <td>
			Geant test mailing list service
		</td>
		<td>
			2014-10-11 05:07:12
		</td>
		<td>
			2015-02-11 03:13:52
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://lists.geant.net/coc.html">https://lists.geant.net/coc.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814860&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814860&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814860&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=107&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://openskos.meertens.knaw.nl/shibboleth</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			OpenSKOS | Meertens
		</td>
		<td>
			2014-03-05 11:07:08
		</td>
		<td>
			2015-02-11 03:13:56
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="http://www.meertens.knaw.nl/cms/en/collections/data-protection">http://www.meertens.knaw.nl/cms/en/collections/data-protection</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814865&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814865&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814865&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate">View</a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=127&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://registration.dariah.eu/shibboleth</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			DARIAH Registration
		</td>
		<td>
			2014-05-15 15:07:10
		</td>
		<td>
			2015-02-11 03:13:57
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://de.dariah.eu/ServicePrivacyPolicy">https://de.dariah.eu/ServicePrivacyPolicy</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814866&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814866&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814866&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=141&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://rems.elixir-finland.org/shibboleth</a>
		</td>
        <td>
			http://www.csc.fi/haka
		</td>
        <td>
			REMS ELIXIR
		</td>
		<td>
			2014-06-26 21:07:15
		</td>
		<td>
			2015-02-11 03:14:14
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://rems.elixir-finland.org/privacy-policy">https://rems.elixir-finland.org/privacy-policy</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814884&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814884&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814884&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate">View</a>
		</td>
	</tr>
	<tr class="green">
		<td>
			<a href="?f_id_sp=66&f_coc_found=1&f_last_seen=1&page=1&f_order=ts+desc&show=list_sp_tests&f_is_changed=1">https://repos.ids-mannheim.de/shibboleth</a>
		</td>
        <td>
			https://www.aai.dfn.de
		</td>
        <td>
			Institute for the German Language (IDS) - Respository
		</td>
		<td>
			2013-10-01 20:35:22
		</td>
		<td>
			2015-02-11 03:13:58
		</td>
		<td>
			Yes
		</td>
		<td>
			All attributes present, privacy statement has a link to CoC
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a target="_blank" href="https://repos.ids-mannheim.de/privacy.html">https://repos.ids-mannheim.de/privacy.html</a>
		</td>
		<td>
			200
		</td>
		<td>
			OK
		</td>
		<td>
			text/html
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814867&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=sp_test_source&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814867&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=headers&template=notemplate">View</a>
		</td>
		<td>
			<a target="_blank" href="?sp_test_id=814867&f_coc_found=1&f_last_seen=1&page=1&f_order=entityID&show=cookies&template=notemplate"></a>
		</td>
	</tr>
	<tr>
		<td colspan="15" align="center">&nbsp;
			
		</td>
	</tr>
	<tr>
		<td colspan="15" align="center">Records found: 38</td>
	</tr>
	<tr>
                                 $show='list_idps';
                                 $f_coc_found=1;
                                 $f_last_seen=1;
                                 $f_order='entityID';
                                 $f_id_status=2;

		<td colspan="15" align="center">
			1&nbsp;
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

