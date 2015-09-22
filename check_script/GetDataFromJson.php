<?php
# Copyright 2015 Géant Association
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
# Framework Programme (FP7/2007-2013) under grant agreement nº 238875
# (GEANT).

class GetDataFromJson {
    protected $edugainFedsUrl;
    protected $edugainIdpsUrl;
    protected $arrContextOptions;

    public function __construct() {
        $confArray = parse_ini_file(dirname(__FILE__) . '/properties.ini.php', true);
        $this->edugainFedsUrl = $confArray['edugain_db_json']['json_feds_url'];
        $this->edugainIdpsUrl = $confArray['edugain_db_json']['json_idps_url'];

        $this->arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
    }

    function obtainFederationsList() {
        $fedsList = false;
        $jsonEdugainFeds = file_get_contents($this->edugainFedsUrl, false, stream_context_create($this->arrContextOptions));

        if ($jsonEdugainFeds !== false) {
            $fedsList = json_decode($jsonEdugainFeds, true, 10, JSON_UNESCAPED_UNICODE);
        }

        if ($fedsList === false) {
            throw new Exception("Error fetching JSON eduGAIN Federation members");
        }
        return $fedsList;
    }

    function obtainIdPList() {
        $idpList = false;
        $jsonEdugainIdps = file_get_contents($this->edugainIdpsUrl, false, stream_context_create($this->arrContextOptions));
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

    function extractIdPfromJSON($jsonIdpList) {
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

    private function obtainCharset($contentType, $html) {
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
        if (!$charset) {
            $charset = "ISO 8859-1";
        }

        return $charset;
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
