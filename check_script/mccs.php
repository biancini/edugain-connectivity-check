<html>
   <head>
      <title>Test SAML Request</title>
   </head>
   
   <body>
      <p>
         <?php header('Content-Type: text/html; charset=utf-8'); 
            
            include ("utils.php");
            
            $arrContextOptions=array(
            		"ssl"=>array(
            				"verify_peer"=>false,
            				"verify_peer_name"=>false,
            		),
            );
            
            //$map_url = "http://mds.edugain.org";
            $map_url = "https://spemb.lab.unimo.it/edugain-md.xml";
            
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
            			$results = checkIdp($idp['SingleSignOnService']);
            			foreach ($results as $key => $value) {
            				if ($key == "messages"){
            					$cnt = 0;
            					foreach ($value as $v){
            						$cnt++;
            						echo "Messages $cnt: $v";
            						echo "<br>";
            					}
            				} else echo "$key => $value\n";
            				echo "<br>";
            			}
            		}
            	}
            }

         ?>
      </p>
   </body>
</html>
