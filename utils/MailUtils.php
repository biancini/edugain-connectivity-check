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

class MailUtils() {
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
}
