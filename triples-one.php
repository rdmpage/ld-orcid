<?php

// Triples for one record (for debugging)

error_reporting(E_ALL);


require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/core.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$cuid = new EndyJasmi\Cuid;

$nquads = new NQuads();


$id = '0000-0001-5012-8422';
$id = '0000-0001-5028-0686';
$id = '0000-0001-5028-0686';
$id = '0000-0001-6717-8995';
$id = '0000-0002-0633-5974';
$id = '0000-0002-1564-1738';
$id = '0000-0002-2912-4870';
$id = '0000-0002-3359-1631';
$id = '0000-0002-6504-0551';
$id = '0000-0002-5443-8919';
$id = '0000-0002-6504-0551';
$id = '0000-0003-1802-2649';
$id = '0000-0003-1802-2649';
$id = '0000-0003-2573-1371';
$id = '0000-0003-3628-2567';

$id = '0000-0003-1426-8449';
//$id = '0000-0001-8332-5326';
//$id = '0000-0001-8746-3830';
//$id = '0000-0003-4078-6557';
//$id = '0000-0002-0472-7990';

$id = '0000-0002-7173-7160';

$id = '0000-0003-3509-293X';
//$id = '0000-0002-4082-7817';
//$id = '0000-0001-6583-8750';

$id = '0000-0003-0663-0153';
//$id = '0000-0003-3054-7325';

$id = '0000-0003-3808-3131';
$id = '0000-0003-3638-2824';
$id = '0000-0003-2885-5652';
$id = '0000-0001-5383-4058';
$id = '0000-0001-6212-9502';
$id = '0000-0002-7937-1474';

$id = '0000-0001-9070-0593';
$id = '0000-0002-0497-166X';
$id = '0000-0001-5929-1154';
//$id = '0000-0002-5203-5374';
//$id = '0000-0001-7632-9775';
//$id = '0000-0003-2896-1631';
//$id = '0000-0002-3837-8186';
//$id = '0000-0002-0805-7154';
//$id = '0000-0002-0151-114X';
//$id = '0000-0002-9807-4790';

$id = '0000-0003-3963-752X';

$id = '0000-0002-9444-8716';

$id = '0000-0003-2861-949X';



$directory = $config['cache'] . '/' . id_to_dir($id);

$filename = $directory . '/' . $id . '.json';

$output = $directory . '/' . $id . '.nt';


//echo $id . "\n";

$json = file_get_contents($filename);

echo $json;

// fix JSON
// "@id" : "grid.1214.6",
$json = preg_replace('/"@id" : "(grid..*)"/', '"@id" : "https://www.grid.ac/institutes/$1"', $json);

if (preg_match('/"url" : "(?<url>[^"]+)"/', $json, $m))
{
	$original = $m[0];
	if (!preg_match('/^https?:\/\//', $m['url']))
	{
		$new = '"url" : "http://' . $m['url'] . '"';
		
		$json = str_replace($original, $new, $json);
	}
}

if (preg_match('/"url" : \[ "(?<url>[^"]+)"/', $json, $m))
{
	$original = $m[0];
	if (!preg_match('/^https?:\/\//', $m['url']))
	{
		$new = '"url" : [ "http://' . $m['url'] . '"';
		
		$json = str_replace($original, $new, $json);
	}
}

$quads = JsonLD::toRdf($json);
$serialized = $nquads->serialize($quads);

//echo $serialized;

$serialized = fix_triples($serialized);

echo $serialized;

echo "\n$filename\n";

file_put_contents($output, $serialized);


?>


