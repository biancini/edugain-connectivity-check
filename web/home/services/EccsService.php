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

require 'DBManager.php';

class EccsService {
    protected $dbManager;

    public function __construct() {
        $this->dbManager = new DBManager();
    }

    protected function getParameter($key, $defaultValue, $array=false) {
        $value = (array_key_exists($key, $_REQUEST) ? htmlspecialchars($_REQUEST[$key]) : $defaultValue);
    
        if (!$value || trim($value) == '') {
            $value = $defaultValue;
        }
    
        if ($array) {
            $value = explode(",", $value);
        }
    
        return $value;
    }
    
    protected function getAllParameters($list) {
        foreach ($list as $par) {
            $params[$par[0]] = $this->getParameter($par[0], $par[1], $par[2]);
        }
        return $params;
    }
}
