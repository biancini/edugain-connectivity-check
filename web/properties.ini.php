;<?php
;die();
;/*

; This configuration file contains information to
; connect to the MySQL database used to store
; metadata and idp check information.

; Rename this file to properties.ini and check
; information before running the code.

[check_script]
map_url = http://mds.edugain.org
parallel = 30
check_history = 2
verbose = True

[sp_1]
name = SWITCH
entityID = https://attribute-viewer.aai.switch.ch/interfederation-test/shibboleth
acs_url = https://attribute-viewer.aai.switch.ch/Shibboleth.sso/SAML2/POST

[sp_2]
name = GARR
entityID = https://sp24-test.garr.it/shibboleth
acs_url = https://sp24-test.garr.it/Shibboleth.sso/SAML2/POST

[db_connection]
db_host = localhost
db_port = 3306
db_name = mccs_db
db_user = mccs_webuser
db_password = password_db_mccs_web
db_sock = /var/run/mysqld/mysqld.sock

[edugain_db_json]
json_feds_url = "http://technical.edugain.org/json_api.php?action=list_feds"
json_idps_url = "http://technical.edugain.org/json_api.php?action=list_idps"

[email]
host = cyrus.dir.garr.it
port = 0
tls = true
user = biancini
password = p4n3gh1n1
from = mccs@garr.it
baseurl = https://sp-test-1.mi.garr.it/mccs
test_recipient = andrea.biancini@garr.it

;*/
