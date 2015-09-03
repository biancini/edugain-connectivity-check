<?php
# Copyright 2015 Géant Association
#
# Licensed under the G�~IANT Standard Open Source (the "License")
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
# (G�~IANT

require_once 'EccsService.php';

class RunTest extends EccsService {
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

    function handle() {
        $acsUrl = $this->getParameter("acsUrl", null);
        $serviceLocation = $this->getParameter("serviceLocation", null);
        $spEntityID = $this->getParameter("spEntityID", null);

        if (!$acsUrl || !$serviceLocation || !$spEntityID) {
            throw new Exception("Wrong parameter passed, you have to specify acsUrl, serviceLocation and spEntityID.");
        }

        return static::createCheckUrl($acsUrl, $serviceLocation, $spEntityID);
    }
}

$handler = new RunTest;
header("Location: " . $handler->handle());
