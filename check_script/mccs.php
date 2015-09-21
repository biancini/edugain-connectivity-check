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

require_once 'IdPChecks.php';

$micTime = microtime();
$micTime = explode(" ", $micTime);
$micTime = $micTime[1] + $micTime[0];
$startTime = $micTime;

$worker = new IdpChecks;
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
