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

$directory = $config['cache'] . '/' . id_to_dir($id);

$filename = $directory . '/' . $id . '.json';

$output = $directory . '/' . $id . '.nt';


echo $id . "\n";

$json = file_get_contents($filename);

//echo $json;

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

$quads = JsonLD::toRdf($json);
$serialized = $nquads->serialize($quads);

//echo $serialized;

$serialized = fix_triples($serialized);

echo $serialized;

echo "\n$filename\n";

file_put_contents($output, $serialized);


?>


