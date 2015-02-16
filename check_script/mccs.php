<?php
            
include ("utils.php");
            
$conf_array = parse_ini_file(dirname(__FILE__) . '/../properties.ini', true);
$map_url = $conf_array['check_script']['map_url'];
$spEntityID_1 = $conf_array['check_script']['spEntityID_1'];
$spEntityID_2 = $conf_array['check_script']['spEntityID_2'];
$spACSurl_1 = $conf_array['check_script']['spACSurl_1'];
$spACSurl_2 = $conf_array['check_script']['spACSurl_2'];
            
$arrContextOptions=array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

if (($metadataXML = file_get_contents($map_url, false, stream_context_create($arrContextOptions)))===false){
	echo "Error fetching eduGAIN metadata XML\n";
} else {
	libxml_use_internal_errors(true);
	$idpList = extractIdPfromXML($metadataXML);
	if (!$idpList) {
		echo "Error loading eduGAIN metadata XML\n";
		foreach(libxml_get_errors() as $error) {
			echo "\t", $error->message;
		}
	} else {
		$count = 0;
		foreach ($idpList as $idp){
			$count++;
					
			$result_1 = checkIdp($idp['SingleSignOnService'], $spEntityID_1, $spACSurl_1);
			$result_2 = checkIdp($idp['SingleSignOnService'], $spEntityID_2, $spACSurl_2);
			
			$keyToCheck = array("ok", "form_valid", "http_code", "curl_return", "messages");
			foreach ($keyToCheck as $key){
				if( $result_1[$key] !== $result_2[$key] ){
					echo "The IdP ".$idp['entityID']." NOT consumpt metadata correctly";
					echo "<br><br>";
				}
			}

// 			foreach ($result_1 as $key => $value) {
// 				if ($key == "messages"){
// 					$cnt = 0;
// 					foreach ($value as $v){
// 						$cnt++;
// 						echo "Messages $cnt: $v\n";
// 					}
// 				} else {
// 					echo "$key => $value\n";
// 				}
// 			}
// 			echo "\n";
		}
	}
}
?>
