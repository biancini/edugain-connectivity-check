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
    function sendEmail($emailProperties, $fedData) {
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
           if (!empty($fedData['sgDeputyEmail'])) {
              $mail->addAddress($fedData['sgDeputyEmail']);
           }

           if (!empty($fedData['sgDelegateEmail'])) {
              $mail->addAddress($fedData['sgDelegateEmail']);
           }

           if (!empty($fedData['emailAddress']) &&
                empty($fedData['sgDeputyEmail']) &&
                empty($fedData['sgDelegateEmail'])) {
                 $mail->addAddress($fedData['emailAddress']);
           }
        }

        $mail->addReplyTo($emailProperties['replyTo']);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        $mail->Subject = '[ECCS] Non-working eduGAIN IdPs in '.$fedData['name'];

        Twig_Autoloader::register();

        $loader = new Twig_Loader_Filesystem(dirname(__FILE__)."/../templates");
        $twig = new Twig_Environment($loader);

        $templateHtml = $twig->loadTemplate('mail_html_template.html');
        $templateTxt  = $twig->loadTemplate('mail_text_template.txt');

        /* Usefull for CSV file of ECCS Results 

        $templateCSV  = $twig->loadTemplate('cvs_template.txt');

        $csv = $templateCSV->render(array(
            'eccs_baseUrl'       => $emailProperties['baseurl'],
            'federationName'     => $fedData['name'],
            'reg_auth'           => $fedData['regAuth'],
            'idp_ok'             => $fedData['idp_ok'],
            'idp_form_invalid'   => $fedData['idp_form_invalid'],
            'idp_curl_error'     => $fedData['idp_curl_error'],
            'idp_no_edugain_md'  => $fedData['idp_no_edugain_md'],
            'idp_http_error'     => $fedData['idp_http_error'],
            'idp_disabled'       => $fedData['idp_disabled'],
            'sg_deputy_name'     => $fedData['sgDeputyName'],
            'sg_deputy_surname'  => $fedData['sgDeputySurname'],
            'sg_deputy_email'    => $fedData['sgDeputyEmail'],
            'sg_delegate_name'   => $fedData['sgDelegateName'],
            'sg_delegate_surname'=> $fedData['sgDelegateSurname'],
            'sg_delegate_email'  => $fedData['sgDelegateEmail'],
        ));
        */

        $body = $templateHtml->render(array(
            'eccs_baseUrl'       => $emailProperties['baseurl'],
            'federationName'     => $fedData['name'],
            'reg_auth'           => $fedData['regAuth'],
            'idp_ok'             => $fedData['idp_ok'],
            'idp_form_invalid'   => $fedData['idp_form_invalid'],
            'idp_curl_error'     => $fedData['idp_curl_error'],
            'idp_no_edugain_md'  => $fedData['idp_no_edugain_md'],
            'idp_http_error'     => $fedData['idp_http_error'],
            'idp_disabled'       => $fedData['idp_disabled'],
            'sg_deputy_name'     => $fedData['sgDeputyName'],
            'sg_deputy_surname'  => $fedData['sgDeputySurname'],
            'sg_deputy_email'    => $fedData['sgDeputyEmail'],
            'sg_delegate_name'   => $fedData['sgDelegateName'],
            'sg_delegate_surname'=> $fedData['sgDelegateSurname'],
            'sg_delegate_email'  => $fedData['sgDelegateEmail'],
        ));

        $altBody = $templateTxt->render(array(
            'eccs_baseUrl'       => $emailProperties['baseurl'],
            'federationName'     => $fedData['name'],
            'reg_auth'           => $fedData['regAuth'],
            'idp_ok'             => $fedData['idp_ok'],
            'idp_form_invalid'   => $fedData['idp_form_invalid'],
            'idp_curl_error'     => $fedData['idp_curl_error'],
            'idp_no_edugain_md'  => $fedData['idp_no_edugain_md'],
            'idp_http_error'     => $fedData['idp_http_error'],
            'idp_disabled'       => $fedData['idp_disabled'],
            'sg_deputy_name'     => $fedData['sgDeputyName'],
            'sg_deputy_surname'  => $fedData['sgDeputySurname'],
            'sg_deputy_email'    => $fedData['sgDeputyEmail'],
            'sg_delegate_name'   => $fedData['sgDelegateName'],
            'sg_delegate_surname'=> $fedData['sgDelegateSurname'],
            'sg_delegate_email'  => $fedData['sgDelegateEmail'],
        ));

        $mail->Body = $body;
        $mail->AltBody = $altBody;
        return $mail->send();
    }
}
