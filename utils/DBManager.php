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
 
class DBManager {
    private $mysqli;

    /**
     Create a new DB connection and return its pointer.
    
     @param array $dbConnection Array containing the datas for DB connection
     */
    public function __construct($mysqli = null) {
        if ($mysqli) {
            $this->mysqli = $mysqli;
        }
        else {
            $confArray = parse_ini_file('properties.ini.php', true);
            $dbConnection = $confArray['db_connection'];

            if (array_key_exists('db_sock', $dbConnection) && !empty($dbConnection['db_sock'])) {
                $mysqli = new mysqli(null, $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], null, $dbConnection['db_sock']);
            }
            else {
                $mysqli = new mysqli($dbConnection['db_host'], $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], $dbConnection['db_port']);
            }
    
            $mysqli->set_charset('utf8');
            if ($mysqli->connect_errno) {
                throw new Exception("Failed to connect to MySQL: ($mysqli->connect_errno) $mysqli->connect_error");
            }
    
            $this->mysqli = $mysqli;
        }
    }

    private function refValues($arr){
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        return $arr;
    }
    
    /**
     Execute a prepared satement on the DB and returns resultset
    
     @param array $dbConnection Array containing the datas for DB connection
     @return new mysqli($dbConnection),
     */
    public function executeStatement($r, $query) {
        $sql = $query->getQuerySql();
        $params = $query->getQueryParams();

        $stmt = $this->mysqli->prepare($sql);
        if ($params != NULL && count($params) > 1 && !call_user_func_array(array($stmt, "bind_param"), $this->refValues($params))) {
            throw new Exception(ERROR . mysqli_error($this->mysqli));
        }
        if (!$stmt->execute()) {
            throw new Exception(ERROR . mysqli_error($this->mysqli));
        }
    
        if ($r === true) {
            return $stmt->get_result();
        }
    
        return $query->getNumRows($stmt);
    }

    public function escapeStringChars($string) {
        return mysqli_real_escape_string($this->mysqli, $string);
    }
}
