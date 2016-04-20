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

require_once 'JsonAPI.php';
    
$handler = new JsonAPI();
try {
    $results = $handler->handle();
    if (array_key_exists('page', $results) && array_key_exists('total_pages', $results)) {
        parse_str($_SERVER['QUERY_STRING'], $query_string);
        $base_url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

	$link = "";
	if ($result['page'] < $result['total_pages']) {
	    $query_string['page'] = $result['page'] + 1;
	    $next_querystring = http_build_query($query_string);
	    if ($link !== "") $link .= ", ";
	    $link .= "<{$base_url}{$next_querystring}>; rel=\"next\"";

	    $query_string['page'] = $result['total_pages'];
	    $last_querystring = http_build_query($query_string);
	    if ($link !== "") $link .= ", ";
	    $link .= "<{$base_url}{$last_querystring}>; rel=\"last\"";
	}

	if ($result['page'] > 1) {
	    $query_string['page'] = $result['page'] - 1;
	    $prev_querystring = http_build_query($query_string);
	    if ($link !== "") $link .= ", ";
	    $link .= "<{$base_url}{$prev_querystring}>; rel=\"prev\"";

	    $query_string['page'] = 1;
	    $first_querystring = http_build_query($query_string);
	    if ($link !== "") $link .= ", ";
	    $link .= "<{$base_url}{$first_querystring}>; rel=\"first\"";
	}

        header("Link: <{$link}");
    }	    
    print json_encode($results); 
}
catch (Exception $e) {
    print $e->getMessage();
}
