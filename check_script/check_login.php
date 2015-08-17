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

if (count($argv) < 2) {
    throw new Exception("Please specify IdP entityID as parameter to this script.");
}

include ("utils.php");

$conf_array = parse_ini_file(dirname(__FILE__) . 'properties.ini', true);
$map_url = $conf_array['check_script']['map_url'];

global $verbose;
$verbose = $conf_array['check_script']['verbose'];

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
        throw new Exception("Configuration error. Please check properties.ini.");
}

$edugain_idps_url = $conf_array['edugain_db_json']['json_idps_url'];

$arrContextOptions=array(
        "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
        ),
);

if (($json_edugain_idps = file_get_contents($edugain_idps_url, false, stream_context_create($arrContextOptions)))===false){
        throw new Exception("Error fetching JSON eduGAIN IdPs");
}

$idpList = extractIdPfromJSON($json_edugain_idps);
if (!$idpList) {
    throw new Exception("Error loading eduGAIN JSON IdPs.");
}

foreach ($idpList as $curIdP) {
    if ($curIdP['entityID'] == $argv[1]) {
        print "Executing check for " . $curIdP['entityID'] . "\n";
        executeIdPchecks($curIdP, $spEntityIDs, $spACSurls, NULL);
    }
}


?>
