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
include (dirname(__FILE__)."/../Twig/lib/Twig/Autoloader.php");

class MailUtils {
    function sendEmail($emailProperties, $fed_data) {
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

 /*        if (!empty($emailProperties['test_recipient'])) {
            $mail->addAddress($emailProperties['test_recipient']);
        }
        else {
            $mail->addAddress($recipient);
        }
*/

        if (!empty($fed_data['sgDeputyEmail'])){
            $mail->addAddress($fed_data['sgDeputyEmail']);
        }
        if (!empty($fed_data['sgDelegateEmail'])){
            $mail->addAddress($fed_data['sgDelegateEmail']);
        }
        if (!empty($fed_data['emailAddress'])){
            $mail->addAddress($fed_data['emailAddress']);
        }


        $mail->addReplyTo('eccs@edugain.net');
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        $mail->Subject = '[ECCS] Some IdP is not consuming metadata correctly';

        Twig_Autoloader::register();

        $loader = new Twig_Loader_Filesystem(dirname(__FILE__)."/../templates");
        $twig = new Twig_Environment($loader);

        $template_html = $twig->loadTemplate('mail_4_fed_op.html');
        $template_txt  = $twig->loadTemplate('mail_4_fed_op.txt');

        $body = $template_html->render(array(
            'federationName'   => $fed_data['name'],
            'reg_auth'         => $fed_data['regAuth'],
            'idp_ok'           => $fed_data['idp_ok'],
            'idp_form_invalid' => $fed_data['idp_form_invalid'],
            'idp_curl_error'   => $fed_data['idp_curl_error'],
            'idp_http_error'   => $fed_data['idp_http_error'],
            'idp_disabled'     => $fed_data['idp_disabled'],
        ));

        $altBody = $template_txt->render(array(
            'federationName'   => $fed_data['name'],
            'reg_auth'         => $fed_data['regAuth'],
            'idp_ok'           => $fed_data['idp_ok'],
            'idp_form_invalid' => $fed_data['idp_form_invalid'],
            'idp_curl_error'   => $fed_data['idp_curl_error'],
            'idp_http_error'   => $fed_data['idp_http_error'],
            'idp_disabled'     => $fed_data['idp_disabled'],
        ));


        $mail->Body = $body;
        $mail->AltBody = $altBody;
        return $mail->send();
    }
}
