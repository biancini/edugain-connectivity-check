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

require_once dirname(__FILE__) . '/../utils/QueryBuilder.php';
require_once dirname(__FILE__) . '/../utils/DBManager.php';
    
class StoreResultsDB {
    protected $dbManager;

    public function __construct($dbManager = null) {
        if ($dbManager) {
            $this->dbManager = $dbManager;
        }
        else {
            $this->resetDbConnection();
        }
    }

    public function resetDbConnection() {
        $this->dbManager = new DBManager();
    }

    function cleanOldEntityChecks() {
        $query = new QueryBuilder();
        $query->setSql("DELETE FROM EntityChecks WHERE checkExec = 0");
        $this->dbManager->executeStatement(false, $query);

        $query = new QueryBuilder();
        $query->setSql("UPDATE EntityChecks SET checkExec = checkExec - 1");
        $this->dbManager->executeStatement(false, $query);
    }

    function updateIgnoredEntities() {
        $query = new QueryBuilder();
        $query->setSql("UPDATE EntityDescriptors SET updated = 0 WHERE ignoreEntity = 0");
        $this->dbManager->executeStatement(false, $query);
    }

    function deleteOldEntities() {
        $query = new QueryBuilder();
        $query->setSql("DELETE FROM EntityDescriptors WHERE updated = 0");
        $this->dbManager->executeStatement(false, $query);
    }

    function storeFederationStats() {
        print "Executing update of federation statistics";

        $query = new QueryBuilder();
        $query->setSql("INSERT INTO FederationStats (SELECT CURDATE() AS checkDate, registrationAuthority, currentResult, numIdPs FROM FederationStatsView)");
        $this->dbManager->executeStatement(false, $query);
    }

    function storeFedsIntoDb($fedsList) {
        $query = new QueryBuilder();
        $query->setSql("UPDATE Federations SET updated = 0");
        $this->dbManager->executeStatement(false, $query);

        foreach ($fedsList as $fed) { 
            //If I find a registrationAuthority value for the federation
            if ($fed['reg_auth'] === null || $fed['reg_auth'] === '') {
                continue;
            }
            $query = new QueryBuilder();
            $query->setSql("SELECT * FROM Federations WHERE registrationAuthority = ?");
            $query->addQueryParam($fed['reg_auth'], 's');
            $result = $this->dbManager->executeStatement(true, $query);

            if ($result->num_rows <= 0) {
                $query = new QueryBuilder();
                $query->setSql("INSERT INTO Federations (federationName, emailAddress, registrationAuthority, updated) VALUES (?, ?, ?, 1)");
                $query->addQueryParam($fed['name'], 's');
                $query->addQueryParam($fed['email'], 's');
                $query->addQueryParam($fed['reg_auth'], 's');
                $this->dbManager->executeStatement(false, $query);

                continue;
            }

            while ($row = $result->fetch_assoc()) {
                $query = new QueryBuilder();
                $query->setSql("UPDATE Federations SET updated = 1 WHERE registrationAuthority = ?");
                $query->addQueryParam($fed['reg_auth'], 's');
                $this->dbManager->executeStatement(false, $query);
                    
                if ($fed['name'] !== $row['federationName']) {
                    $query = new QueryBuilder();
                    $query->setSql("UPDATE Federations SET federationName = ? WHERE registrationAuthority = ?");
                    $query->addQueryParam($fed['name'], 's');
                    $query->addQueryParam($fed['reg_auth'], 's');
                    $this->dbManager->executeStatement(false, $query);
                }
                          
                if ($fed['email'] !== $row['emailAddress']) {
                    $query = new QueryBuilder();
                    $query->setSql("UPDATE Federations SET emailAddress = ? WHERE registrationAuthority = ?");
                    $query->addQueryParam($fed['email'], 's');
                    $query->addQueryParam($fed['reg_auth'], 's');
                    $this->dbManager->executeStatement(false, $query);
                }
            }
        }

        $query = new QueryBuilder();
        $query->setSql("DELETE FROM Federations WHERE updated = 0");
        $this->dbManager->executeStatement(false, $query);
    }

    function updateDisabledEntities($entityID) {
        $query = new QueryBuilder();
        $query->setSql('UPDATE EntityDescriptors SET updated = 1, currentResult = NULL, previousResult = NULL WHERE entityID = ?');
        $query->addQueryParam($entityID, 's');
        $this->dbManager->executeStatement(false, $query);
    }

    function insertCheck($entityID, $spEntityID, $ssoService, $acsUrl, $html, $httpCode, $reason, $lastCheckHistory) {
        $query = new QueryBuilder();
        $query->setSql('INSERT INTO EntityChecks (entityID, spEntityID, serviceLocation, acsUrls, checkHtml, httpStatusCode, checkResult, checkExec) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $query->addQueryParam($entityID, 's');
        $query->addQueryParam($spEntityID, 's');
        $query->addQueryParam($ssoService, 's');
        $query->addQueryParam($acsUrl, 's');
        $query->addQueryParam($html, 's');
        $query->addQueryParam($httpCode ? $httpCode : 0, 'i');
        $query->addQueryParam($reason, 's');
        $query->addQueryParam($lastCheckHistory, 'i');
        $this->dbManager->executeStatement(false, $query);
    }

    function updateEntityLastCheckStatus($reason, $previousStatus, $entityID) {
        $query = new QueryBuilder();
        $query->setSql("UPDATE EntityDescriptors SET lastCheck = ?, currentResult = ?, previousResult = ?, updated = 1 WHERE entityID = ?");
        $query->addQueryParam(date('Y-m-d\TH:i:s\Z'), 's');
        $query->addQueryParam($reason, 's');
        $query->addQueryParam($previousStatus, 's');
        $query->addQueryParam($entityID, 's');
        $this->dbManager->executeStatement(false, $query);
    }

    function getEntityPreviousStatus($idp) {
        $query = new QueryBuilder();
        $query->setSql('SELECT * FROM EntityDescriptors WHERE entityID = ? ORDER BY lastCheck');
        $query->addQueryParam($idp['entityID']);
        $result = $this->dbManager->executeStatement(true, $query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $previousStatus = $row['currentResult'];
                $ignoreEntity = $row['ignoreEntity'];
            }
            return array($ignoreEntity, $previousStatus);
        } else {
            $query = new QueryBuilder();
            $query->setSql("INSERT INTO EntityDescriptors (entityID, registrationAuthority, displayName, technicalContacts, supportContacts, serviceLocation) VALUES (?, ?, ?, ?, ?, ?)");
            $query->addQueryParam($idp['entityID'], 's');
            $query->addQueryParam($idp['registrationAuthority'], 's');
            $query->addQueryParam($idp['displayName'], 's');
            $query->addQueryParam($idp['technicalContacts'], 's');
            $query->addQueryParam($idp['supportContacts'], 's');
            $query->addQueryParam($idp['SingleSignOnService'], 's');
            $result = $this->dbManager->executeStatement(false, $query);

            return array(false, NULL);
        }
    }
}
