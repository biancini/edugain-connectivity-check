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

function getParameter($key, $defaultValue, $array=false) {
    $value = (array_key_exists($key, $_REQUEST) ? htmlspecialchars($_REQUEST[$key]) : $defaultValue);

    if (!$value || trim($value) == '') {
        $value = $defaultValue;
    }

    if ($array) {
        $value = explode(",", $value);
    }

    return $value;
}

function getAllParameters($list) {
    foreach ($list as $par) {
        $params[$par[0]] = getParameter($par[0], $par[1], $par[2]);
    }
    return $params;
}

function addParameterToQuery($params, $name, $excludeParam) {
    if (!in_array($name, $excludeParam) && array_key_exists($name, $params)) {
        if (is_array($params[$name])) {
            return "&" . $name . "=" . implode(",", $params[$name]);
        }
        return "&" . $name . "=" . $params[$name];
    }
    return "";
}

function getUrlDirection($params, $field) {
    $ret  = getCurrentUrl($params, ["f_order", "f_order_direction"]);
    $ret .= "&f_order=$field&f_order_direction=";
    if ($params["f_order"] == $field && $params["f_order_direction"] == "ASC") {
        $ret .= "DESC";
    }
    else {
        $ret .= "ASC";
    }
    return $ret;
}

function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) {
        $refs = array();
        foreach($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    return $arr;
}

function concatenateWhere($sqlConditions) {
    if (!strstr($sqlConditions, "WHERE")) {
        return " WHERE";
    }
    else {
        return " AND";
    }
}

function addSqlCondition(&$sqlConditions, &$queryParams, $params, $paramName, $sqlName, $like=False, $map=NULL) {
    if (!$params[$paramName]) {
        return;
    }

    if (is_array($params[$paramName])) {
        if (in_array("NULL", $params[$paramName])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " $sqlName IS NULL";
        }
        if (!in_array("All", $params[$paramName])) {
            $sqlConditions .= concatenateWhere($sqlConditions);
            $sqlConditions .= " $sqlName in (";
            foreach ($params[$paramName] as $val) {
                $sqlConditions .= (substr($sqlConditions, -1) != "(") ? ", ": "";
                $sqlConditions .= "?";
                array_push($queryParams, $val);
            }
            $sqlConditions .= ")";
        }
    }
    elseif ($params[$paramName] != "All") {
        $sqlConditions .= concatenateWhere($sqlConditions);
        if ($like) {
            $sqlConditions .= " $sqlName LIKE ?";
            array_push($queryParams, "%" . $params[$paramName] . "%");
        }
        elseif ($map !== NULL) {
            $sqlConditions .= " $sqlName = ?";
            array_push($queryParams, $map[$params[$paramName]]);
        }
        else{ 
            $sqlConditions .= " $sqlName = ?";
            array_push($queryParams, $params[$paramName]);
        }
    }
    else {
        // Do nothing
    }
}

function addAllSqlConditions(&$sqlConditions, &$queryParams, $params, $list) {
    foreach ($list as $par) {
        addSqlCondition($sqlConditions, $queryParams, $params, $par[0], $par[1], $par[2], $par[3]);
    }
}

/**
 Create a new DB connection and return its pointer.

 @param array $dbConnection Array containing the datas for DB connection
 @return new mysqli($dbConnection),
 */
function getDbConnection($dbConnection) {
    if (array_key_exists("db_sock", $dbConnection) && !empty($dbConnection['db_sock'])) {
        $mysqli = new mysqli(null, $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], null, $dbConnection['db_sock']);
    }
    else {
        $mysqli = new mysqli($dbConnection['db_host'], $dbConnection['db_user'], $dbConnection['db_password'], $dbConnection['db_name'], $dbConnection['db_port']);
    }

    $mysqli->set_charset("utf8");
    if ($mysqli->connect_errno) {
        throw new Exception("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    }

    return $mysqli;
}

/**
 Execute a prepared satement on the DB and returns resultset

 @param array $dbConnection Array containing the datas for DB connection
 @return new mysqli($dbConnection),
 */
function executeStatement($mysqli, $r, $sql, $params) {
    $stmt = $mysqli->prepare($sql);

    if ($params != NULL && count($params) > 1 && !call_user_func_array(array($stmt, "bind_param"), refValues($params))) {
        throw new Exception(ERROR . mysqli_error($mysqli));
    }
    if (!$stmt->execute()) {
        throw new Exception(ERROR . mysqli_error($mysqli));
    }

    if ($r === true) {
        return $stmt->get_result();
    }

    return true;
}

function createCheckUrl($spACSurl, $httpRedirectServiceLocation, $spEntityID) {
    date_default_timezone_set('UTC');
    $date = date('Y-m-d\TH:i:s\Z');
    $id = md5($date.rand(1, 1000000));

    $samlRequest = '
          <samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
             AssertionConsumerServiceURL="'.$spACSurl.'"
             Destination="'.$httpRedirectServiceLocation.'"
             ID="_'.$id.'"
             IssueInstant="'.$date.'"
             ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Version="2.0">
             <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">
                '.$spEntityID.'
             </saml:Issuer>
             <samlp:NameIDPolicy AllowCreate="1"/>
          </samlp:AuthnRequest>';

    $samlRequest = preg_replace('/[\s]+/',' ',$samlRequest);
    $samlRequest = urlencode( base64_encode( gzdeflate( $samlRequest ) ) );
    $url = $httpRedirectServiceLocation."?SAMLRequest=".$samlRequest;
    return $url;
}

?>
