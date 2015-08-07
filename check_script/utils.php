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

include (dirname(__FILE__)."/../PHPMailer/PHPMailerAutoload.php");

define('ENTITY_ID', 'entityID');
define('ERROR', 'Error: ');

/**
 Create a new DB connection and return its pointer.

 @param array $dbConnection Array containing the datas for DB connection
 @return new mysqli($dbConnection),
 */
function getDbConnection($dbConnection) {
    if (array_key_exists("db_sock", $dbConnection) && !empty($dbConnection['db_sock'])) {
        $mysqli = new mysqli(null, $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], null, $dbConnection['db_sock']);
    }
    else {
        $mysqli = new mysqli($dbConnection['db_host'], $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], $dbConnection['db_port']);
    }

    $mysqli->set_charset("utf8");
    if ($mysqli->connect_errno) {
        throw new Exception("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    }

    return $mysqli;
}

function refValues($arr) {
    //Reference is required for PHP 5.3+
    if (strnatcmp(phpversion(),'5.3') >= 0) { 
        $refs = array(); 
        foreach($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs; 
    } 
    return $arr; 
} 

/**
 Execute a prepared satement on the DB and returns resultset

 @param array $dbConnection Array containing the datas for DB connection
 @return new mysqli($dbConnection),
 */
function executeStatement($mysqli, $r, $sql, $params) {
    if ($mysqli === NULL) {
        return;
    }

    $stmt = $mysqli->prepare($sql);

    if ($params != NULL && !call_user_func_array(array($stmt, "bind_param"), refValues($params))) {
        throw new Exception(ERROR . mysqli_error($mysqli));
    }

    $stmt->execute();

    if ($r === true) {
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception(ERROR . mysqli_error($mysqli));
        }

        return $result;
    }

    return true;
}

function getEntityPreviousStatus($mysqli, $idp) {
    if ($mysqli === NULL) {
        return array(false, NULL);
    }

    $result = executeStatement($mysqli, true, "SELECT * FROM EntityDescriptors WHERE entityID = ? ORDER BY lastCheck", array("s", $idp[ENTITY_ID]));
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $previousStatus = $row['currentResult'];
            $ignoreEntity = $row['ignoreEntity'];
        }
        return array($ignoreEntity, $previousStatus);
    } else {
        executeStatement($mysqli, false, "INSERT INTO EntityDescriptors (entityID, registrationAuthority, displayName, technicalContacts, supportContacts, serviceLocation) VALUES (?, ?, ?, ?, ?, ?)", array("ssssss", $idp[ENTITY_ID], $idp['registrationAuthority'], $idp['displayName'],  $idp['technicalContacts'], $idp['supportContacts'], $idp['SingleSignOnService']));
        return array(false, NULL);
    }
}

/**
 Execute checks on each input IdP.

 @param array $idp Array containing the IdP entity
 @param array $spEntityIDs containing the SPs entityID
 @return new mysqli($dbConnection),
 */
function executeIdPchecks($idp, $spEntityIDs, $spACSurls, $dbConnection, $checkHistory = 2) {
    $mysqli = ($dbConnection !== NULL) ? getDbConnection($dbConnection) : null;
    list($ignoreEntity, $previousStatus) = getEntityPreviousStatus($mysqli, $idp);

    if ($ignoreEntity == true) {
        // update EntityDescriptors
        executeStatement($mysqli, false, "UPDATE EntityDescriptors SET currentResult = NULL WHERE entityID = ?", array("s", $idp[ENTITY_ID]));

        print "Entity " . $idp[ENTITY_ID] . " ignored.\n";

	     $mysqli->close();
        return;
    }
    
    $reason = '1 - OK';
    $lastCheckHistory = $checkHistory - 1;

    for ($i = 0; $i < count($spEntityIDs); $i++) {
        $result = checkIdp($idp['SingleSignOnService'], $spEntityIDs[$i], $spACSurls[$i]);
        $status = array_key_exists('status', $result) ? $result['status'] : -1;
        $reason = array_key_exists('message', $result) ? $result['message'] : '0 - UNKNOWN-Error';

        // fai insert in tabella EntityChecks
        executeStatement($mysqli, false, "INSERT INTO EntityChecks (entityID, spEntityID, serviceLocation, acsUrls, checkHtml, httpStatusCode, checkResult, checkExec) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", array("sssssisi", $idp[ENTITY_ID], $spEntityIDs[$i], $idp['SingleSignOnService'], $spACSurls[$i], $result['html'], $result['http_code'], $reason, $lastCheckHistory));
    }

    // update EntityDescriptors
    executeStatement($mysqli, false, "UPDATE EntityDescriptors SET lastCheck = ?, currentResult = ?, previousResult = ?, updated = 1 WHERE entityID = ?", array("ssss", date('Y-m-d\TH:i:s\Z'), $reason, $previousStatus, $idp[ENTITY_ID]));

    if ($status === 0) {
        print "The IdP ".$idp[ENTITY_ID]." consumed metadata correctly\n";
    }
    else {
        print "The IdP ".$idp[ENTITY_ID]." did NOT consume metadata correctly.\n\n";
        print "Reason: " . $reason . "\n";
        print "Messages: " . print_r($result['error'], true) . "\n\n";
    }

    if ($mysqli !== NULL) {
        $mysqli->close();
    }
}

function storeFedsIntoDb($jsonEdugainFeds, $dbConnection) {
    $mysqli = getDbConnection($dbConnection);
    $fedsList = json_decode($jsonEdugainFeds, true, 10, JSON_UNESCAPED_UNICODE);
    
    foreach ($fedsList as $fed) { 
        //If I find a registrationAuthority value for the federation
        if ($fed['reg_auth'] === null || $fed['reg_auth'] === '') {
            continue;
        }
        $result = executeStatement($mysqli, true, "SELECT * FROM Federations WHERE registrationAuthority = ?", array("s", $fed['reg_auth']));

        if ($result->num_rows <= 0) {
            executeStatement($mysqli, false, "INSERT INTO Federations (federationName, emailAddress, registrationAuthority, updated) VALUES (?, ?, ?, 1)", array("sss", $fed['name'], $fed['email'], $fed['reg_auth']));
            continue;
        }

        while ($row = $result->fetch_assoc()) {
            executeStatement($mysqli, false, "UPDATE Federations SET updated = 1 WHERE registrationAuthority = ?", array("s", $fed['reg_auth']));
                    
            if ($fed['name'] !== $row['federationName']) {
                executeStatement($mysqli, false, "UPDATE Federations SET federationName = ? WHERE registrationAuthority = ?", array("ss", $fed['name'], $fed['reg_auth']));
            }
                          
            if ($fed['email'] !== $row['emailAddress']) {
                executeStatement($mysqli, false, "UPDATE Federations SET emailAddress = ? WHERE registrationAuthority = ?", array("ss", $fed['email'], $fed['reg_auth']));
            }
        }
    }
    $mysqli->close();
}

function getDisplayName($idp) {
    if ($idp['displayname']) {
        $aux1 = explode("==", $idp['displayname']);
        foreach ($aux1 as $result) {
            $aux2 = explode(';', $result);
            $aux3[$aux2[0]] = $aux2[1];
        }
           
        $keys = array_keys($aux3);
        $firstElement = $aux3[$keys[0]];
            
        return (array_key_exists('en', $aux3)) ? (string) $aux3['en'] : (string) $firstElement;
    }

    if ($idp['role_display_name']) {
        $aux1 = explode("==", $idp['role_display_name']);
        
        foreach ($aux1 as $result) {
            $aux2 = explode(';', $result);
            $aux3[$aux2[0]] = $aux2[1];
        }

        $keys = array_keys($aux3);
        $firstElement = $aux3[$keys[0]];
            
        return (array_key_exists('en', $aux3)) ? (string)$aux3['en'] : (string)$firstElement;
    }

    return "";
}

function getIdpMailContact($idp, $type) {
    if (!array_key_exists($type, $idp['contacts'])) {
        return "";
    }

    $contacts = array();
    foreach ($idp['contacts'][$type] as $contact) {
        if (!array_key_exists('EmailAddress', $contact['e_p'])) {
            continue;
        }

        foreach ($contact['e_p']['EmailAddress'] as $emailAddress) {
            if (0 === strpos($emailAddress, 'mailto:')) {
                $contacts[] = preg_replace('/(mailto:)/', '', $emailAddress);
            } else{
                $contacts[] = $emailAddress;
            }
        }
    }

    return implode(",", $contacts);
}

/**
 Extract useful informations stored into a JSON UTF-8 file.

 @param String $jsonIdpList The JSON file that contains the identity providers
 @return array idps[]("entityID" => "value",
                             "registrationAuthority" => "value",
                             "SingleSignOnService" => "value",
                             "technicalContacts" => array(),
                             "supportContacts" => array()),
 */
function extractIdPfromJSON($jsonIdpList) {
    $idpsList = json_decode($jsonIdpList, true, 10, JSON_UNESCAPED_UNICODE);
    $idps = array();

    foreach ($idpsList as $idp) {
        $idps[] = array(
            /*ENTITY_ID => (string) $idp[ENTITY_ID],*/
            ENTITY_ID => (string) $idp['entityID'],
            'registrationAuthority' => (string) $idp['registrationAuthority'],
            'SingleSignOnService' => (string) $idp['Location'],
            'displayName' => getDisplayName($idp),
            'technicalContacts' => getIdpMailContact($idp, 'technical'),
            'supportContacts' => getIdpMailContact($idp, 'support'),
        );
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

function extractIdPfromXML ($metadata) {
        $xml = simplexml_load_string($metadata, null, LIBXML_COMPACT);
    
        // Register the used namespaced into the SimpleXMLElement
        $ns = $xml->getNamespaces(true);

        // Consider only IDP' EntityDescriptors that have an HTTP-Redirect <md:SingleSignOnService>
         $items = $xml->xpath("//*[local-name()='EntityDescriptor'][*[local-name()='IDPSSODescriptor']/*[local-name()='SingleSignOnService'][@Binding='urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect']]");

         // Extract the entityID, the registrationAuthority, the SingleSignOnService (HTTP-Redirect), the technicalContacts and the supportContacts
         $idps = array();
         $count = 0;
         foreach($items as $idp) {    
             $count++;

             $idps[$count][ENTITY_ID] = (string)$idp[ENTITY_ID];

             $idps[$count]['registrationAuthority'] = (string)$idp->xpath("./*[local-name()='Extensions']/*[local-name()='RegistrationInfo']/@registrationAuthority")[0];
             
             $idps[$count]['SingleSignOnService'] = (string)$idp->xpath("./*[local-name()='IDPSSODescriptor']/*[local-name()='SingleSignOnService'][@Binding='urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect']/@Location")[0];
             
             $idpTechnicalContacts = $idp->xpath("./*[local-name()='ContactPerson'][@contactType='technical']");

             if (!$idpTechnicalContacts) {
                 $idps[$count]['technicalContacts'] = "Technical Contact is missing";
             }
             
             $idps[$count]['technicalContacts'] = array();
             $techContacts = array();
             foreach ($idpTechnicalContacts as $techContact) {
                 $techContacts[] = ($ns['md']) ? $techContact->children($ns['md'])->EmailAddress : $techContact->children->EmailAddress;
             }
             
             foreach ($techContacts as $tcCnt) {
                 $idps[$count]['technicalContacts'][] = (string)$tcCnt;
             }
                          
             $idpSupportContacts = $idp->xpath("./*[local-name()='ContactPerson'][@contactType='support']");
             
             $idps[$count]['supportContacts'] = array();
             $suppContacts = array();
             foreach ($idpSupportContacts as $suppContact) {
                 $suppContacts[] = ($ns['md']) ? $suppContact->children($ns['md'])->EmailAddress : $suppContact->children->EmailAddress;
             }
             foreach ($suppContacts as $spCnt) {
                 $idps[$count]['supportContacts'][] = (string)$spCnt;
             }
         }
        return $idps;
}

function obtainCharset($contentType, $html) {
    $charset = NULL;

    /* 1: HTTP Content-Type: header */
    preg_match('@([\w/+]+)(;\s*charset=(\S+))?@i', $contentType, $matches);
    if (!$charset && isset($matches[3])) {
        $charset = $matches[3];
    }

    /* 2: <meta> element in the page */
    preg_match('@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s*charset=([^\s"]+))?@i', $html, $matches);
    if (!$charset && isset($matches[3])) {
        $charset = $matches[3];
    }

    /* 3: <xml> element in the page */
    preg_match('@<\?xml.+encoding="([^\s"]+)@si', $html, $matches);
    if (!$charset && isset($matches[1])) {
        $charset = $matches[1];
    }

    /* 4: PHP's heuristic detection */
    $encoding = mb_detect_encoding($html);
    if (!$charset && $encoding) {
        $charset = $encoding;
    }

    /* 5: Default for HTML */
    $charset = "ISO 8859-1";

    return $charset;
}


function cleanUtf8Curl($html, $curl) {
    if (!is_string($html)) {
        return $html;
    }

    $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $charset = obtainCharset($contentType, $html);

    /* Convert it if it is anything but UTF-8 */
    /* You can change "UTF-8"  to "UTF-8//IGNORE" to 
    ignore conversion errors and still output something reasonable */
    if (isset($charset) && strtoupper($charset) != "UTF-8") {
        $html = iconv($charset, 'UTF-8', $html);
    }

    return $html;
}


function generateSamlRequest($spACSurl, $httpRedirectServiceLocation, $id, $date, $spEntityID) {
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

   $samlRequest = preg_replace('/[\s]+/', ' ', $samlRequest);
   $samlRequest = urlencode( base64_encode(gzdeflate($samlRequest)));

   return $samlRequest;
}

function getUrlWithCurl($url) {
   $curl = curl_init($url);

   $html = false;
   $curlError = false;
   for ($vers = 0; $vers <= 4; $vers++) {
     if ($html === false) {
       curl_setopt_array($curl, array(
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_FRESH_CONNECT => true,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_SSL_VERIFYHOST => false,
          CURLOPT_COOKIEJAR => "/dev/null",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 45,
          CURLOPT_CONNECTTIMEOUT => 60,
          CURLOPT_SSLVERSION => $vers,
          CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:36.0) Gecko/20100101 Firefox/36.0',
       ));
       $html = curl_exec($curl);

       if ($html === false) {
         $curlError = curl_error($curl);
       }
     }
   }
   
   $info = curl_getinfo($curl);

   $html = cleanUtf8Curl($html, $curl);
   $html = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $html));

   curl_close($curl);
   return array($curlError, $info, $html);
}

/**
   Generates an authentication request, sends it to the SAML2 
   HTTP-POST URL of an Provider Identity Provider and returns a result array.
   
   @param String $httpRedirectServiceLocation the HTTP-Redirect service location URL of an identity provider
   @return array("status", "http_code", "error", "html")
*/
function checkIdp($httpRedirectServiceLocation, $spEntityID, $spACSurl) {
   global $verbose;
   
   date_default_timezone_set('UTC');
   $date = date('Y-m-d\TH:i:s\Z');
   $id = md5($date.rand(1, 1000000));
   $samlRequest = generateSamlRequest($spACSurl, $httpRedirectServiceLocation, $id, $date, $spEntityID);
   $url = $httpRedirectServiceLocation."?SAMLRequest=".$samlRequest;
   list($curlError, $info, $html) = getUrlWithCurl($url);

   $error = array();
   $status = 0;
   $message = '1 - OK';

   if ($curlError !== false) {
      $status = 3;
      $message = '3 - CURL-Error';
      if ($verbose) {
          echo "Curl error: ".$curlError."\n";
      }
      $error[] = $curlError;
   } else if ($info['http_code'] != 200 && $info['http_code'] != 401) {
      $status = 3;
      $message = '3 - HTTP-Error';
      if ($verbose) {
          echo "Status code: ".$info['http_code']."\n";
      }
      $error[] = "Status code: ".$info['http_code'];
   } else {
      $patternUsername = '/<input[\s]+[^>]*(type=\s*[\'"](text|email)[\'"]|user)[^>]*>/im';
      $patternPassword = '/<input[\s]+[^>]*(type=\s*[\'"]password[\'"])[^>]*>/im';

      if (!preg_match($patternUsername, $html) || !preg_match($patternPassword, $html)) {
         $status = 2;
         $message = '2 - FORM-Invalid';
         if ($verbose) {
             echo "Did not find input for username or password.\n";
         }
         $error[] = "Did not find input for username or password.";
      }
   }

   return array(
      "status" => $status,
      "message" => $message,
      "http_code" => $info['http_code'],
      "error" => $error,
      "html" => ($html) ? $html : "",
   );
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
    $mail->FromName = 'eduGAIN Connectivity Check Service';

    if (!empty($emailProperties['test_recipient'])) {
        $mail->addAddress($emailProperties['test_recipient']);
    }
    else {
        $mail->addAddress($recipient);
    }
    $mail->addReplyTo('eccs@edugain.net');
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $mail->Subject = '[ECCS] Some IdP is not consuming metadata correctly';
    $altBody  = 'The eduGAIN Connectivity Check service identified some IdP from your federation that seem to not being consuming correctly the eduGAIN metadata.';
    $body  = '<p>'.$altBody.'<br/></p>';

    $altBody .= '\n\n';
    $body .= '<table border="1">';
    $body .= '<thead><td><b>IdP name</b></td><td><b>Current Status</b></td><td><b>Previous Status</b></td><td><b>Technical Concact</b></td><td><b>Link</b></td></thead>';
    foreach ($idps as $curEntityID => $vals) {
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
        $body .= '<td><a href="'.$emailProperties['baseurl'].'/test.php?f_entityID='.$curEntityID.'">View last checks</a></td>';
        $body .= '</tr>';
    }
    $altBody .= '\nVisit eduGAIN Connectivity Check Service at ' . $emailProperties['baseurl'] . ' to understand more.\nThank you for your cooperation.\nRegards.';
    $body .= '</table>';
    $body .= '<p><br/>Thank you for your cooperation.<br/>Regards.</p>';

    $mail->AltBody = $altBody;
    $mail->Body    = $body;

    return $mail->send();
}

?>
