<?php

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
      CURLOPT_COOKIEJAR => "/dev/null",
      CURLOPT_RETURNTRANSFER => true 
   ));
   
   $html = curl_exec($curl);
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
      $pattern_username = '/<input.*name=[\'"]?(j_)?username/i';
      $pattern_password = '/<input.*name=[\'"]?(j_)?password/i';

      if( stripos($html, "Message did not meet security requirements") !== false){
         $msg = "$httpRedirectServiceLocation found our request did not meet security requirements. It could be that the time on the server is out of sync or probably the request took too long.";
         monlog($msg,3);
      } else {
         if(preg_match($pattern_username, $html)){
            //okay
         } else {
            $msg = "Did not find input for username.";
            $error[] = $msg;
            $validForm = $ok = false;
         }
         
         if(preg_match($pattern_password, $html)){
            //okay
         } else {
            $msg = "Did not find input for password.";
            $error[] = $msg;
            $validForm = $ok = false;
         }
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
   }
   
   return $ret;
}
?>
