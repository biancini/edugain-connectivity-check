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

include ("utils.php");

class IdpChecks {
    public function __construct() {
        $this->arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $this->spEntityIDs = array();
        $this->spACSurls = array();

        $regexp = "/^sp_\d/";

        $this->confArray = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);
        $this->dbConnection = $this->confArray['db_connection'];
        $this->confArrayKeys = array_keys($this->confArray);
        $this->spsKeys[] = preg_grep($regexp, $this->confArrayKeys);
        foreach ($this->spsKeys as $key => $value) {
            foreach($value as $sp => $val) {
                $this->spEntityIDs[] = $this->confArray[$val][ENTITY_ID];
                $this->spACSurls[] = $this->confArray[$val]['acs_url'];
            }
        }

        $this->parallel = intval($this->confArray['check_script']['parallel']);
        $this->checkHistory = intval($this->confArray['check_script']['check_history']);

        if (count($this->spEntityIDs) != count($this->spACSurls)) {
            throw new Exception("Configuration error. Please check properties.ini.");
        }
    }

    function cleanOldEntityChecks() {
        $mysqli = getDbConnection($this->dbConnection);
        executeStatement($mysqli, false, "DELETE FROM EntityChecks WHERE checkExec = 0", NULL);
        executeStatement($mysqli, false, "UPDATE EntityChecks SET checkExec = checkExec - 1", NULL);
        $mysqli->close();
    }

    function executeAllChecks() {
        $this->cleanOldEntityChecks();

        $edugainIdpsUrl = $this->confArray['edugain_db_json']['json_idps_url'];
        $idpList = false;
        $jsonEdugainIdps = file_get_contents($edugainIdpsUrl, false, stream_context_create($this->arrContextOptions));

        if ($jsonEdugainIdps !== false) {
            $idpList = extractIdPfromJSON($jsonEdugainIdps);
        }
    
        if ($idpList === false) {
            throw new Exception("Error loading eduGAIN JSON IdPs");
        }

        $mysqli = getDbConnection($this->dbConnection);
        executeStatement($mysqli, false, "UPDATE EntityDescriptors SET updated = 0", NULL);
        $mysqli->close();

        $count = 1;
        for ($i = 0; $i < $this->parallel; $i++) {
            $pid = pcntl_fork();
            if (!$pid) {
                //In child
                print "Executing check for " . $idpList[$count][ENTITY_ID] . "\n";
                executeIdPchecks($idpList[$count], $this->spEntityIDs, $this->spACSurls, $this->dbConnection, $this->checkHistory);
                return false;
            }
            $count++;
        }

        while (pcntl_waitpid(0, $status) != -1) { 
            $status = pcntl_wexitstatus($status);
            if ($count <= count($idpList)) {
                $pid = pcntl_fork();
                if (!$pid) {
                    //In child
                    print "Executing check for " . $idpList[$count][ENTITY_ID] . "\n";
                    executeIdPchecks($idpList[$count], $this->spEntityIDs, $this->spACSurls, $this->dbConnection, $this->checkHistory);
                    return false;
                }
                $count++;
            } 
        }

        $mysqli = getDbConnection($this->dbConnection);
        executeStatement($mysqli, false, "DELETE FROM EntityDescriptors WHERE updated = 0", NULL);
        $mysqli->close();

        return true;
    }

    function updateFederations() {
        $edugainFedsUrl = $this->confArray['edugain_db_json']['json_feds_url'];
        $jsonEdugainFeds = file_get_contents($edugainFedsUrl, false, stream_context_create($this->arrContextOptions));

        if ($jsonEdugainFeds === false){
            print "Error fetching JSON eduGAIN Federation members\n";
        } else {
            $mysqli = getDbConnection($this->dbConnection);
            executeStatement($mysqli, false, "UPDATE Federations SET updated = 0", NULL);
            $mysqli->close();

            storeFedsIntoDb($jsonEdugainFeds, $this->dbConnection);

            $mysqli = getDbConnection($this->dbConnection);
            executeStatement($mysqli, false, "DELETE FROM Federations WHERE updated = 0", NULL);
            $mysqli->close();
        }
    }
}

$micTime = microtime();
$micTime = explode(" ", $micTime);
$micTime = $micTime[1] + $micTime[0];
$startTime = $micTime;

$worker = new IdpChecks;
$worker->updateFederations();
$terminated = $worker->executeAllChecks();

if ($terminated) {
    $micTime = microtime();
    $micTime = explode(" ",$micTime);
    $micTime = $micTime[1] + $micTime[0];
    $endtime = $micTime;
    $totalExecutionTime = ($endtime - $startTime);
    print "\n\nTotal Executaion Time ".$totalExecutionTime." seconds.\n";
}

?>
