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

include (dirname(__FILE__)."/../PHPMailer/PHPMailerAutoload.php");

/**
 Create a new DB connection and return its pointer.

 @param array $db_connection Array containing the datas for DB connection
 @return new mysqli($db_connection),
 */
function get_db_connection($db_connection) {
	if (array_key_exists("db_sock", $db_connection) && !empty($db_connection['db_sock'])) {
		$mysqli = new mysqli(null, $db_connection['db_user'], $db_connection['db_password'], $db_connection['db_name'], null, $db_connection['db_sock']);
	}
	else {
		$mysqli = new mysqli($db_connection['db_host'], $db_connection['db_user'], $db_connection['db_password'], $db_connection['db_name'], $db_connection['db_port']);
	}

	$mysqli->set_charset("utf8");
	if ($mysqli->connect_errno) {
		die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	}

	return $mysqli;
}

/**
 Execute checks on each input IdP.

 @param array $idp Array containing the IdP entity
 @param array $spEntityIDs containing the SPs entityID
 @return new mysqli($db_connection),
 */
function executeIdPchecks($idp, $spEntityIDs, $spACSurls, $db_connection, $checkHistory = 2) {
	$ignore_entity = false;
	$previous_status = NULL;
	$check_ok = true;
	$reason = '1 - OK';
	$messages = array();

	$mysqli = null;
	$last_check_history = $checkHistory - 1;

	if ($db_connection !== NULL) {
		$mysqli = get_db_connection($db_connection);
		$stmt = $mysqli->prepare("SELECT * FROM EntityDescriptors WHERE entityID = ? ORDER BY lastCheck") or die("Error: " . mysqli_error($mysqli));
		$stmt->bind_param("s", $idp['entityID']) or die("Error: " . mysqli_error($mysqli));
		$stmt->execute() or die("Error: " . mysqli_error($mysqli));
		$result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));

		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$previous_status = $row['currentResult'];
				$ignore_entity = $row['ignoreEntity'];
			}
		} else {
			$stmt = $mysqli->prepare("INSERT INTO EntityDescriptors (entityID, registrationAuthority, displayName, technicalContacts, supportContacts) VALUES (?, ?, ?, ?, ?)") or die("Error: " . mysqli_error($mysqli));
			$stmt->bind_param("sssss", $idp['entityID'], $idp['registrationAuthority'], $idp['displayName'],  $idp['technicalContacts'], $idp['supportContacts']) or die("Error: " . mysqli_error($mysqli));

			$stmt->execute() or die("Error: " . mysqli_error($mysqli));
		}
	}

	if ($ignore_entity == true) {
		print "Entity " . $idp['entityID'] . " ignored.\n";
		if ($mysqli !== NULL) $mysqli->close();
		return;
	}
	
	for ($i = 0; $i < count($spEntityIDs); $i++) {
		$result = checkIdp($idp['SingleSignOnService'], $spEntityIDs[$i], $spACSurls[$i]);

		$check_ok = array_key_exists('ok', $result) && $result['ok'];
		if ($check_ok) {
			$reason = '1 - OK';
		} else {
			$messages = $result['messages'];

			if (!$result['form_valid']) {
				$reason = '2 - FORM-Invalid';
			}
			elseif ($result['http_code'] != 200) {
				$reason = '3 - HTTP-Error';
			}
			elseif ($result['curl_return'] != '') {
				$reason = '3 - CURL-Error';
			}
		}

		// fai insert in tabella EntityChecks
		if ($mysqli !== NULL) {
			$stmt = $mysqli->prepare("INSERT INTO EntityChecks (entityID, spEntityID, serviceLocation, acsUrls, checkHtml, httpStatusCode, checkResult, checkExec) VALUES (?, ?, ?, ?, ?, ?, ?, ?)") or die("Error: " . mysqli_error($mysqli));
			$stmt->bind_param("sssssisi", $idp['entityID'], $spEntityIDs[$i], $idp['SingleSignOnService'], $spACSurls[$i], $result['html'], $result['http_code'], $reason, $last_check_history) or die("Error: " . mysqli_error($mysqli));
			$stmt->execute() or die("Error: " . mysqli_error($mysqli));
		}
	}

	// update EntityDescriptors
	if ($mysqli !== NULL) {
		$stmt = $mysqli->prepare("UPDATE EntityDescriptors SET lastCheck = ?, currentResult = ?, previousResult = ?, updated = 1 WHERE entityID = ?") or die("Error: " . mysqli_error($mysqli));
		$stmt->bind_param("ssss", date('Y-m-d\TH:i:s\Z'), $reason, $previous_status, $idp['entityID']) or die("Error: " . mysqli_error($mysqli));
		$stmt->execute() or die("Error: " . mysqli_error($mysqli));
	}

	if ($check_ok) {
		print "The IdP ".$idp['entityID']." consumed metadata correctly\n";
	}
	else {
		print "The IdP ".$idp['entityID']." did NOT consume metadata correctly.\n\n";
		print "Reason: " . $reason . "\n";
		print "Messages: " . print_r($messages, true) . "\n\n";
	}

	if ($mysqli !== NULL) $mysqli->close();
}

function store_feds_into_db($json_edugain_feds, $db_connection){
	$mysqli = get_db_connection($db_connection);
	$feds_list = json_decode($json_edugain_feds, true, 10, JSON_UNESCAPED_UNICODE);
	
	foreach ($feds_list as $fed){ 
		//If I find a registrationAuthority value for the federation
		if ($fed['reg_auth'] !== null && $fed['reg_auth'] !== ''){
			$stmt = $mysqli->prepare("SELECT * FROM Federations WHERE registrationAuthority = ?") or die("Error: " . mysqli_error($mysqli));
			$stmt->bind_param("s", $fed['reg_auth']) or die("Error: " . mysqli_error($mysqli));
			$stmt->execute() or die("Error: " . mysqli_error($mysqli));
			$result = $stmt->get_result() or die("Error: " . mysqli_error($mysqli));

			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					
					$stmt = $mysqli->prepare("UPDATE Federations SET updated = 1 WHERE registrationAuthority = ?") or die("Error: " . mysqli_error($mysqli));
					$stmt->bind_param("s", $fed['reg_auth']) or die("Error: " . mysqli_error($mysqli));
					$stmt->execute() or die("Error: " . mysqli_error($mysqli));
					
  					if ($fed['name'] !== $row['federationName']){
						$stmt = $mysqli->prepare("UPDATE Federations SET federationName = ? WHERE registrationAuthority = ?") or die("Error: " . mysqli_error($mysqli));
						$stmt->bind_param("ss", $fed['name'], $fed['reg_auth']);
						$stmt->execute() or die("Error: " . mysqli_error($mysqli));
  					}
  						
  					if ($fed['email'] !== $row['emailAddress']){
  						$mysqli->query($sql) or die("Error: " . $sql . ": " . mysqli_error($mysqli));

						$stmt = $mysqli->prepare("UPDATE Federations SET emailAddress = ? ,  WHERE registrationAuthority = ?") or die("Error: " . mysqli_error($mysqli));
						$stmt->bind_param("ss", $fed['email'], $fed['reg_auth']);
						$stmt->execute() or die("Error: " . mysqli_error($mysqli));
  					}
				}
			} else {
				$stmt = $mysqli->prepare("INSERT INTO Federations (federationName, emailAddress, registrationAuthority, updated) VALUES (?, ?, ?, 1)") or die("Error: " . mysqli_error($mysqli));
				$stmt->bind_param("sss", $fed['name'], $fed['email'], $fed['reg_auth']);
				$stmt->execute() or die("Error: " . mysqli_error($mysqli));
			}
		}
	}
	$mysqli->close();
}

/**
 Extract useful informations stored into a JSON UTF-8 file.

 @param String $json_idp_list The JSON file that contains the identity providers
 @return array idps[]("entityID" => "value",
							 "registrationAuthority" => "value",
							 "SingleSignOnService" => "value",
							 "technicalContacts" => array(),
							 "supportContacts" => array()),
 */
function extractIdPfromJSON($json_idp_list){
	
	$idps = array();
	
	$idps_list = json_decode($json_idp_list, true, 10, JSON_UNESCAPED_UNICODE);
	
	$count = 0;
	foreach ($idps_list as $idp){
		$count++;
		
		$idps[$count]['entityID'] = (string)$idp['entityID'];
		$idps[$count]['registrationAuthority'] = (string)$idp['registrationAuthority'];
		$idps[$count]['SingleSignOnService'] = (string)$idp['Location'];
		$idps[$count]['displayName'] = ""; 

		$aux1 = array();
		$aux2 = array();
		$aux3 = array();
		
		if ($idp['displayname']){
			$aux1 = explode("==", $idp['displayname']);
			foreach ($aux1 as $result) {
				$aux2 = explode(';', $result);
				$aux3[$aux2[0]] = $aux2[1];
			}
			
			$keys = array_keys($aux3);
			$firstElement = $aux3[$keys[0]];
			
			$idps[$count]['displayName'] = (array_key_exists('en', $aux3)) ? (string)$aux3['en'] : (string)$firstElement;

		} elseif ($idp['role_display_name']){
			$aux1 = explode("==", $idp['role_display_name']);
			
			foreach ($aux1 as $result) {
				$aux2 = explode(';', $result);
				$aux3[$aux2[0]] = $aux2[1];
			}

			$keys = array_keys($aux3);
			$firstElement = $aux3[$keys[0]];
				
			$idps[$count]['displayName'] = (array_key_exists('en', $aux3)) ? (string)$aux3['en'] : (string)$firstElement;
			echo "idp ".(string)$idp['entityID']."displayName = ".$idp[$count]['displayName'];
		} else{
			$idp[$count]['displayName'] = "";
		}
		
		if (!array_key_exists('technical', $idp['contacts'])){
			$idps[$count]['technicalContacts'] = "Technical Contact missing";
		}
		else{
			$techContacts = array ();
			
			$idps[$count]['technicalContacts'] = "";
			
			foreach ($idp['contacts']['technical'] as $techContact){
				
				if (array_key_exists('EmailAddress', $techContact['e_p'])){
					foreach ($techContact['e_p']['EmailAddress'] as $emailAddress){
						if (0 === strpos($emailAddress, 'mailto:')) {
							$techContacts[] = preg_replace('/(mailto:)/', '', $emailAddress);
						} else{
							$techContacts[] = $emailAddress;							
						}
					}
				}
			}
			$idps[$count]['technicalContacts'] = implode(",", $techContacts);
		}
		
		if (!array_key_exists('support', $idp['contacts'])){
			$idps[$count]['supportContacts'] = "";
		}
		else{
			$suppContacts = array();
			
			$idps[$count]['supportContacts'] = array();
			
			foreach ($idp['contacts']['support'] as $suppContact){
				
				if (array_key_exists('EmailAddress', $suppContact['e_p'])){
					foreach ($suppContact['e_p']['EmailAddress'] as $emailAddress){
						if (0 === strpos($emailAddress, 'mailto:')) {
							$suppContacts[] = preg_replace('/(mailto:)/', '', $emailAddress);
						} else{
							$suppContacts[] = $emailAddress;
						}
					}
				}
			}
			$idps[$count]['supportContacts'] = implode(",", $suppContacts);
		}
	}
	return $idps;
}

/**
 Extract useful informations stored into a SAML Metadata file.
  
 @param String $metadata The XML metadata that contains the identity providers
 @return array idps[]("entityID" => "value", 
		 					 "registrationAuthority" => "value", 
		 					 "SingleSignOnService" => "value", 
		 					 "technicalContacts" => array(), 
		 					 "supportContacts" => array()),
 */

function extractIdPfromXML ($metadata){
	
		//$xml = simplexml_load_string($metadata, null, LIBXML_COMPACT, "md", TRUE);
		$xml = simplexml_load_string($metadata, null, LIBXML_COMPACT);
	
		// Register the used namespaced into the SimpleXMLElement
		$ns = $xml->getNamespaces(true);

		// Consider only IDP' EntityDescriptors that have an HTTP-Redirect <md:SingleSignOnService>
 		$items = $xml->xpath("//*[local-name()='EntityDescriptor'][*[local-name()='IDPSSODescriptor']/*[local-name()='SingleSignOnService'][@Binding='urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect']]");

 		// Extract the entityID, the registrationAuthority, the SingleSignOnService (HTTP-Redirect), the technicalContacts and the supportContacts
 		$idps = array();
 		$count = 0;
 		foreach($items as $idp){	
 			$count++;

 			$idps[$count]['entityID'] = (string)$idp['entityID'];

 			$idps[$count]['registrationAuthority'] = (string)$idp->xpath("./*[local-name()='Extensions']/*[local-name()='RegistrationInfo']/@registrationAuthority")[0];
 			
 			$idps[$count]['SingleSignOnService'] = (string)$idp->xpath("./*[local-name()='IDPSSODescriptor']/*[local-name()='SingleSignOnService'][@Binding='urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect']/@Location")[0];
 			
 			$idp_technicalContacts = $idp->xpath("./*[local-name()='ContactPerson'][@contactType='technical']");

 			if (!$idp_technicalContacts) $idps[$count]['technicalContacts'] = "Technical Contact is missing";
 			
			$idps[$count]['technicalContacts'] = array();
 			$techContacts = array();
 			foreach ($idp_technicalContacts as $techContact){
 				$techContacts[] = ($ns['md']) ? $techContact->children($ns['md'])->EmailAddress : $techContact->children->EmailAddress;
 			}
 			
 			foreach ($techContacts as $tcCnt){
 				$idps[$count]['technicalContacts'][] = (string)$tcCnt;
 			}
 			 			
 			$idp_supportContacts = $idp->xpath("./*[local-name()='ContactPerson'][@contactType='support']");
 			
			$idps[$count]['supportContacts'] = array();
 			$suppContacts = array();
 			foreach ($idp_supportContacts as $suppContact){
 				$suppContacts[] = ($ns['md']) ? $suppContact->children($ns['md'])->EmailAddress : $suppContact->children->EmailAddress;
 			}
 			foreach ($suppContacts as $spCnt){
 				$idps[$count]['supportContacts'][] = (string)$spCnt;
 			}
 		}
		return $idps;
}

/**
   Generates an authentication request, sends it to the SAML2 
   HTTP-POST URL of an Provider Identity Provider and returns a result array.
   
   @param String $httpRedirectServiceLocation the HTTP-Redirect service location URL of an identity provider
   @return array("ok", "http_code", "curl_return", "messages", "form_valid")

*/

function checkIdp($httpRedirectServiceLocation, $spEntityID, $spACSurl){
   global $verbose;
   
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
   $curl = curl_init($url);
   curl_setopt_array($curl, array(
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_FRESH_CONNECT => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_COOKIEJAR => "/dev/null",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 30
   ));
   
   $html = curl_exec($curl);
   if ($html == false) {
     curl_setopt($curl, CURLOPT_SSLVERSION, 3);
     $html = curl_exec($curl);
   }
   $info = curl_getinfo($curl);
   $http_code = $info['http_code'];
   $error = array();
   $validForm = true;
   $ok = true;

   if($html == false || $http_code != 200){
      $ok = false;                  
      if(curl_error($curl)){
         if($verbose) echo "Curl error: ".curl_error($curl)."\n";
         $error[] = curl_error($curl);
      } else {
         if($verbose) echo "Status code: ".$info['http_code']."\n";
         $error[] = "Status code: ".$info['http_code'];
      }
   } else {
//       $pattern_username ='/<input.*[\n\r\s]+.*name=[\'"]?.*(username|otp|user)/i';
//       $pattern_password = '/<input.*[\n\r\s]+.*name=[\'"]?.*(password|pass)/i';
      $pattern_username ='/<input[\n\r\s]+[\S\n\r\s]*type=[\n\r\s]?[\'"]?(text|email)/i';
      $pattern_password = '/<input[\n\r\s]+[\S\n\r\s]*type=[\n\r\s]?[\'"]?password/i';
      
      $html = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $html));
      
      if(preg_match($pattern_username, $html)){
      	//okay
      } else {
      	$msg = "Did not find input for username.";
         $error[] = $msg;
         $validForm = false;
	    	$ok = false;
      }
         
      if(preg_match($pattern_password, $html)){
         //okay
      } else {
         $msg = "Did not find input for password.";
         $error[] = $msg;
         $validForm = false;
         $ok = false;
      }
   }
   
   if($verbose && !$ok){
      echo $httpRedirectServiceLocation." ERROR \n";
      //if($html) var_dump($html);
   }

   $ret = array(
      "ok" => $ok,
      "form_valid" => $validForm,
      "http_code" => $http_code,
      "curl_return" => curl_errno($curl),
      "messages" => $error
   );
   
   if($html){
      $ret["html"] = $html;
   } else {
      $ret["html"] = "";
   }
   
   return $ret;
}

function sendEmail($emailProperties, $recipient, $idps) {
	$mail = new PHPMailer;
	//$mail->SMTPDebug = 3; // Enable verbose debug output

	$mail->isSMTP();
	$mail->Host = $emailProperties['host'];
	$mail->SMTPAuth = true;

	if (!empty($emailProperties['user']) && !empty($emailProperties['password'])) {
		$mail->Username = $emailProperties['user'];
		$mail->Password = $emailProperties['password'];
	}

	if (settype($emailProperties['tls'], 'boolean')) {
		$mail->SMTPSecure = 'tls';
	}

	if (intval($emailProperties['port']) > 0) {
		$mail->Port = intval($emailProperties['port']);
	}

	$mail->From = $emailProperties['from'];
	$mail->FromName = 'MCCS monitoring service';

	if (!empty($emailProperties['test_recipient'])) {
		$mail->addAddress($emailProperties['test_recipient']);
	}
	else {
		$mail->addAddress($recipient);
	}
	$mail->addReplyTo('mccs@edugain.net');
	$mail->CharSet = 'UTF-8';
	$mail->isHTML(true);

	$mail->Subject = '[MCCS] Some IdP is not consuming metadata correctly';
	$altBody  = 'The MCCS service identified some IdP from your federation that seem to not being consuming correctly the eduGAIN metadata.';
	$body  = '<p>'.$altBody.'<br/></p>';

	$altBody .= '\n\n';
	$body .= '<table border="1">';
	$body .= '<thead><td><b>IdP name</b></td><td><b>Current Status</b></td><td><b>Previous Status</b></td><td><b>Technical Concact</b></td><td><b>Link</b></td></thead>';
	foreach ($idps as $entityID => $vals) {
		$altBody .= $vals['name'] . '('.$vals['current_status'].')\n';
		$body .= '<tr>';
		$body .= '<td>' . $vals['name'] . '</td>';
		$body .= '<td>' . $vals['current_status'] . '</td>';
		$body .= '<td>' . $vals['previous_status'] . '</td>';
		$body .= '<td>';
		foreach ($vals['tech_contacts'] as $contact) {
			$body .=  '<a href="mailto:' . $contact . '">' . $contact . '</a><br/>';
		}
		$body .= '</td>';
		$body .= '<td><a href="'.$emailProperties['baseurl'].'/test.php?f_entityID='.$entityID.'">View last checks</a></td>';
		$body .= '</tr>';
	}
	$altBody .= '\nVisit MCCS at ' . $emailProperties['baseurl'] . ' to understand more.\nThank you for your cooperation.\nRegards.';
	$body .= '</table>';
	$body .= '<p><br/>Thank you for your cooperation.<br/>Regards.</p>';

	$mail->AltBody = $altBody;
	$mail->Body    = $body;

	return $mail->send();
}

?>
