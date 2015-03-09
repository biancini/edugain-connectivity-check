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

if (count($argv) < 2) {
	die("Please specify IdP entityID as parameter to this script.\n");
}

include ("utils.php");

$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);
$map_url = $conf_array['check_script']['map_url'];

$spEntityIDs = array();
$spACSurls = array();

$regexp = "/^sp_\d/";

$conf_array_keys = array_keys($conf_array);
$sps_keys[] = preg_grep ($regexp, $conf_array_keys);
foreach ($sps_keys as $key => $value) {
        foreach($value as $sp => $val) {
                $spEntityIDs[] = $conf_array[$val]['entityID'];
                $spACSurls[] = $conf_array[$val]['acs_url'];
        }
}

if (count($spEntityIDs) != count($spACSurls)) {
        die("Configuration error. Please check properties.ini.\n");
}

$edugain_idps_url = $conf_array['edugain_db_json']['json_idps_url'];

$arrContextOptions=array(
        "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
        ),
);

if (($json_edugain_idps = file_get_contents($edugain_idps_url, false, stream_context_create($arrContextOptions)))===false){
        print "Error fetching JSON eduGAIN IdPs\n";
} else {
        $idpList = extractIdPfromJSON($json_edugain_idps);
        if (!$idpList) {
                die("Error loading eduGAIN JSON IdPs.\n");
        } else {
		foreach ($idpList as $curIdP) {
			if ($curIdP['entityID'] == $argv[1]) {
				print "Executing check for " . $curIdP['entityID'] . "\n";
				executeIdPchecks($curIdP, $spEntityIDs, $spACSurls, NULL);
			}
		}
        }
}


?>
