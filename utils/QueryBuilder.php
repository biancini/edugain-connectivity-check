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
 
class QueryBuilder {
    private $sql;
    private $sqlConditions;
    private $queryParams;
    private $queryParamTypes;

    /**
     Create a new DB connection and return its pointer.
    
     @param array $dbConnection Array containing the datas for DB connection
     */
    public function __construct() {
        $this->sql = "";
        $this->sqlConditions = "";
        $this->queryParams = array();
        $this->queryParamTypes = "";
    }

    private function concatenateWhere() {
        if (!strstr($this->sqlConditions, "WHERE")) {
            $this->sqlConditions .= " WHERE";
        }
        else {
            $this->sqlConditions .= " AND";
        }
    }

    private function addSqlCondition($params, $paramName, $sqlName, $like=False, $map=NULL) {
        if (!$params[$paramName]) {
            return;
        }
    
        if (is_array($params[$paramName])) {
            if (in_array("NULL", $params[$paramName])) {
                $this->concatenateWhere($this->sqlConditions);
                $this->sqlConditions .= " $sqlName IS NULL";
            }
            if (!in_array("All", $params[$paramName])) {
                $this->concatenateWhere($this->sqlConditions);
                $this->sqlConditions .= " $sqlName in (";
                foreach ($params[$paramName] as $val) {
                    $this->sqlConditions .= (substr($this->sqlConditions, -1) != "(") ? ", ": "";
                    $this->sqlConditions .= "?";
                    $this->addQueryParam($val, 's');
                }
                $this->sqlConditions .= ")";
            }
        }
        elseif ($params[$paramName] != "All") {
            $this->concatenateWhere();
            if ($like) {
                $this->sqlConditions .= " $sqlName LIKE ?";
                $this->addQueryParam("%" . $params[$paramName] . "%", 's');
            }
            elseif ($map !== NULL) {
                $this->sqlConditions .= " $sqlName = ?";
                $this->addQueryParam($map[$params[$paramName]], 's');
            }
            else { 
                $this->sqlConditions .= " $sqlName = ?";
                $this->addQueryParam($params[$paramName], 's');
            }
        }
        else {
            // Do nothing
        }
    }

    public function addAllSqlConditions($params, $list) {
        foreach ($list as $par) {
            $this->addSqlCondition($params, $par[0], $par[1], $par[2], $par[3]);
        }
    }

    public function appendConditions($conditions) {
            $this->sqlConditions .= $conditions;
    }
    
    public function setSql($sql) {
        $this->sql = $sql;
    }

    public function getQuerySql() {
        return $this->sql . $this->sqlConditions;
    }

    public function getQueryParams() {
        return array_merge(array($this->queryParamTypes), $this->queryParams);
    }

    public function addQueryParam($paramValue, $paramType = 's') {
        $this->queryParamTypes .= $paramType;
        array_push($this->queryParams, $paramValue);
    }
}
