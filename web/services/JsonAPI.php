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

require_once 'EccsService.php';
require_once '../../utils/QueryBuilder.php';
    
class JsonAPI extends EccsService {
    private function computeCssClass($inputStatus) {
        $color = '';
        if ($inputStatus == '1 - OK') {
            $color = 'green';
        }
        elseif ($inputStatus == '2 - FORM-Invalid') {
            $color = 'yellow';
        }
        elseif ($inputStatus == '3 - HTTP-Error' || $inputStatus == '3 - CURL-Error') {
            $color =  'red';
        }
        else {
            $color = 'white';
        }
        return $color;
    }
    
    private function getEntities() {
        $params = $this->getAllParameters(array(
            array('show', 'list_idps', false),
            array('f_order', 'entityID', false),
            array('f_entityID', 'All', false),
            array('f_registrationAuthority', 'All', false),
            array('f_displayName', 'All', false),
            array('f_ignore_entity', 'All', false),
            array('f_last_check', 'All', false),
            array('f_current_result', 'All', false),
            array('f_previous_result', 'All', false),
        ));

        $query = new QueryBuilder();
        $sqlCount = "SELECT COUNT(*) FROM EntityDescriptors";
        $sql = "SELECT * FROM EntityDescriptors";

        $query->addAllSqlConditions($params, array(
            array('f_entityID', 'entityID', true, NULL),
            array('f_registrationAuthority', 'registrationAuthority', true, NULL),
            array('f_displayName', 'displayName', true, NULL),
            array('f_ignore_entity', 'ignoreEntity', false, NULL),
            array('f_last_check', 'lastCheck', false, array('1' => 'DATE(lastCheck) = curdate()', '2' => 'DATE(lastCheck) = curdate() - interval 1 day')),
            array('f_current_result', 'currentResult', true, NULL),
            array('f_previous_result', 'previousResult', true, NULL),
        ));
        
        if ($params['f_order']) {
            $query->appendConditions(" ORDER BY " . $this->dbManager->escapeStringChars($params['f_order']));
        }
        
        // find out how many rows are in the table
        $query->setSql($sqlCount);
        $result = $this->dbManager->executeStatement(true, $query);
        $numrows = $result->fetch_row()[0];

        $rowsperpage = $this->getParameter('rpp', '30');
        if ($rowsperpage == 'All') {
            $rowsperpage = $numrows;
        }
        $rowsperpage = is_numeric($rowsperpage) ? (int) $rowsperpage : 30;
        $totalpages = (int) ceil($numrows / $rowsperpage);
        $page = $this->getParameter('page', '1');
        $page = is_numeric($page) ? (int) $page : 1;
        if ($page > $totalpages) {
            $page = $totalpages;
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $rowsperpage;
    
        $query->appendConditions(" LIMIT " . $offset . " , " . $rowsperpage);
        $query->setSql($sql);
        $result = $this->dbManager->executeStatement(true, $query);
    
        $entities = array();
        while ($row = $result->fetch_assoc()) {
            $entity = array(
                'entityID' => $row['entityID'],
                'registrationAuthority' => $row['registrationAuthority'],
                'displayName' => $row['displayName'],
                'technicalContacts' => $row['technicalContacts'],
                'supportContacts' => $row['supportContacts'],
                'ignoreEntity' => ($row['ignoreEntity'] == 1),
                'lastCheck' => $row['lastCheck'],
                'currentResult' => substr($row['currentResult'], 4),
                'previousResult' => substr($row['previousResult'], 4),
                'css_class' => ($row['ignoreEntity'] == 1) ? 'silver' : $this->computeCssClass($row['currentResult']),
            );
    
            array_push($entities, $entity);
        }
        
        $return = array(
            'results' => $entities,
            'num_rows' => $numrows,
            'page' => $page,
            'total_pages' => $totalpages,
        );
        return $return;
    }
    
    private function getChecks() {
        $params = $this->getAllParameters(array(
            array('show', 'list_idps', false),
            array('f_order', 'entityID', false),
            array('f_id_status', 'All', true),
            array('f_entityID', 'All', false),
            array('f_spEntityID', 'All', false),
            array('f_check_time', 'All', false),
            array('f_http_status_code', 'All', false),
            array('f_check_result', 'All', false),
        ));
    
        $query = new QueryBuilder();
        $sqlCount = "SELECT COUNT(*) FROM EntityChecks";
        $sql = "SELECT * FROM EntityChecks";
    
        $query->addAllSqlConditions($params, array(
            array('f_id_status', 'checkResult', false, NULL),
            array('f_entityID', 'entityID', true, NULL),
            array('f_spEntityID', 'spEntityID', true, NULL),
            array('f_check_time', 'checkTime', false, array('1' => 'DATE(lastCheck) = curdate()', '2' => 'DATE(lastCheck) = curdate() - interval 1 day')),
            array('f_http_status_code', 'httpStatusCode', false, NULL),
            array('f_check_result', 'checkResult', true, NULL),
        ));
    
        if ($params['f_order']) {
            $query->appendConditions(" ORDER BY " . $this->dbManager->escapeStringChars($params['f_order']));
        }
    
        // find out how many rows are in the table
        $query->setSql($sqlCount);
        $result = $this->dbManager->executeStatement(true, $query);
        $numrows = $result->fetch_row()[0];
    
        $rowsperpage = $this->getParameter('rpp', '30');
        if ($rowsperpage == 'All') {
            $rowsperpage = $numrows;
        }
        $totalpages = (int) ceil($numrows / $rowsperpage);
        $page = $this->getParameter('page', '1');
        $page = is_numeric($page) ? (int) $page : 1;
        if ($page > $totalpages) {
            $page = $totalpages;
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $rowsperpage;
        
        $query->appendConditions(" LIMIT " . $offset . " , " . $rowsperpage);
        $query->setSql($sql);
        $result = $this->dbManager->executeStatement(true, $query);
    
        $entities = array();
        while ($row = $result->fetch_assoc()) {
            $entity = array(
                'checkID' => $row['id'],
                'entityID' => $row['entityID'],
                'spEntityID' => $row['spEntityID'],
                'checkTime' => $row['checkTime'],
                'httpStatusCode' => $row['httpStatusCode'],
                'checkResult' => substr($row['checkResult'], 4),
                'acsUrl' => $row['acsUrls'], 
                'serviceLocation' => $row['serviceLocation'],
                'css_class' => $this->computeCssClass($row['checkResult']),
            );
            array_push($entities, $entity);
        }
    
        return array(
            'results' => $entities,
            'num_rows' => $numrows,
            'page' => $page,
            'total_pages' => $totalpages,
        );
    }
    
    private function getCheckHtml() {
        $id = $this->getParameter('checkid', null);
        if (!$id) {
            throw new Exception("You must specify the checkid parameter.");
        }
    
        $query = new QueryBuilder();
        $sql = "SELECT acsUrls, serviceLocation, entityID, spEntityID, DATE_FORMAT(checkTime, '%d/%m/%Y at %H:%m:%s') as checkTime, checkHtml FROM EntityChecks";
        $query->setSql($sql);

        $query->addAllSqlConditions(array('id' => $id), array(
            array('id', 'id', false, NULL),
        ));

        $result = $this->dbManager->executeStatement(true, $query);
    
        while ($row = $result->fetch_assoc()) {
            $entity = array(
                'entityID' => $row['entityID'],
                'spEntityID' => $row['spEntityID'],
                'acsUrl' => $row['acsUrls'],
                'serviceLocation' => $row['serviceLocation'],
                'checkTime' => $row['checkTime'],
                'checkHtml' => $row['checkHtml'],
            );
        }
    
        return array(
            'result' => $entity
        );
    }

    public function handle() {
        $action = $this->getParameter('action', '');

        if ($action == 'entities') {
            return $this->getEntities();
        }
        elseif ($action == 'checks') {
            return $this->getChecks();
        }
        elseif ($action == 'checkhtml') {
            return $this->getCheckHtml();
        }
        else {
            $message = "Wrong action, valid actions are entities, checks or checkhtml.";
            throw new Exception($message);
        }
    }
}
