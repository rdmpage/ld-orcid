<?php

// get ORCIDs for specific journals

error_reporting(E_ALL);

//----------------------------------------------------------------------------------------
function get($url, $user_agent='', $content_type = '')
{	
	$data = null;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  
		CURLOPT_SSL_VERIFYHOST=> FALSE,
		CURLOPT_SSL_VERIFYPEER=> FALSE,
	  
	);

	if ($content_type != '')
	{
		
		$opts[CURLOPT_HTTPHEADER] = array(
			"Accept: " . $content_type, 
			"User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405" 
		);
		
	}
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
		
	curl_close($ch);
	
	return $data;
}


$journals = array(
//'Phytotaxa',
//'PhytoKeys',
//'ZooKeys',
//'Zootaxa',
'MycoKeys',
'Mycological Progress',
'Journal of Fungi',
'Mycologia',
'European Journal of Taxonomy',
'Fungal Diversity',

);

$orcids = array();

foreach ($journals as $journal)
{
	$url = 'http://127.0.0.1:5984/crossref-cache/_design/author/_view/orchid-by-container'
		. '?key=' . urlencode('"' . $journal . '"');
	
	$resp = get($url);

	$obj = json_decode($resp);

	foreach ($obj->rows as $row)
	{
		$orcids[] = $row->value;
	}
}


$orcids = array_unique($orcids);

echo join("\n", $orcids) . "\n";



?>
