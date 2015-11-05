<?php
# Copyright 2015 GÃ©ant Association
#
# Licensed under the GEANT Standard Open Source (the "License")
# you may not use this file except in compliance with the License.
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# This software was developed by Consortium GARR. The research leading to
# these results has received funding from the European Community's Seventh
# Framework Programme (FP7/2007-2013) under grant agreement nÂº 238875
# (GEANT).
 
require_once 'GetFile.php';
 
class GetDataFromJson {
    protected $edugainFedsUrl;
    protected $edugainIdpsUrl;
    protected $getFileObj;
 
    public function __construct($confArray = null, $getFileObj = null) {
        $confArray = $confArray ? $confArray : parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
        $this->edugainFedsUrl = $confArray['edugain_db_json']['json_feds_url'];
        $this->edugainIdpsUrl = $confArray['edugain_db_json']['json_idps_url'];
        $this->getFileObj = $getFileObj ? $getFileObj : new GetFile();
    }
 
    function obtainFederationsList() {
        $fedsList = false;
        $jsonEdugainFeds = $this->getFileObj->getFileFromUrl($this->edugainFedsUrl);
 
        if ($jsonEdugainFeds !== false) {
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $fedsList = json_decode($jsonEdugainFeds, true, 10, JSON_UNESCAPED_UNICODE);
            }
            else {
                $fedsList = json_decode($jsonEdugainFeds, true, 10);
            }
        }
 
        if ($fedsList === false) {
            throw new Exception("Error fetching JSON eduGAIN Federation members");
        }
 
        return $fedsList;
    }
 
    function obtainIdPList() {
        $idpList = false;
        $jsonEdugainIdps = $this->getFileObj->getFileFromUrl($this->edugainIdpsUrl);
        if ($jsonEdugainIdps !== false) {
            $idpList = $this->extractIdPfromJSON($jsonEdugainIdps);
        }
   
        if ($idpList === false) {
            throw new Exception("Error loading eduGAIN JSON IdPs");
        }
       
        return $idpList;
    }
 
    private function getDisplayName($idp) {
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
 
    private function getIdpMailContact($idp, $type) {
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
                } else {
                    $contacts[] = $emailAddress;
                }
            }
        }
 
        return implode(",", $contacts);
    }
 
    private function extractIdPfromJSON($jsonIdpList) {
        $idpsList = json_decode($jsonIdpList, true, 10, JSON_UNESCAPED_UNICODE);
        $idps = array();
               
        foreach ($idpsList as $idp) {
            $idps[] = array(
                'entityID' => (string) $idp['entityID'],
                'registrationAuthority' => (string) $idp['registrationAuthority'],
                'SingleSignOnService' => (string) $idp['Location'],
                'displayName' => $this->getDisplayName($idp),
                'technicalContacts' => $this->getIdpMailContact($idp, 'technical'),
                'supportContacts' => $this->getIdpMailContact($idp, 'support'),
            );
        }
 
        return $idps;
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
       return urlencode(base64_encode(gzdeflate($samlRequest)));
    }
}
