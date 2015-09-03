<?php
# Copyright 2015 GÃ©ant Association
#
# Licensed under the GÃ‰ANT Standard Open Source (the "License")
# you may not use this file except in compliance with the License.
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# This software was developed by Consortium GARR. The research leading to
# these results has received funding from the European CommunityÂ¹s Seventh
# Framework Programme (FP7/2007-2013) under grant agreement nÂº 238875
# (GÃ‰ANT).

require_once 'EccsService.php';
require_once 'QueryBuilder.php';

class CheckHtml extends EccsService {
    public function handle() {
        $id = $this->getParameter('checkid', null);
        if (!$id) {
            $message = "Wrong checkid passed as argument.";
            throw new Exception($message);
        }

        $query = new QueryBuilder();
        $sql = "SELECT checkHtml FROM EntityChecks";
        $query->setSql($sql);
        $query->addAllSqlConditions(array('id' => $id), array(
            array('id', 'id', false, NULL),
        ));

        $result = $this->dbManager->executeStatement(true, $query);

        $return = '';
        while ($row = $result->fetch_assoc()) {
            $return .= $row['checkHtml'] . '\n';
        }

        return $return;
    }
}

$handler = new CheckHtml();
try {
    print $handler->handle();
}
catch (Exception $e) {
    print $e->getMessage();
}
