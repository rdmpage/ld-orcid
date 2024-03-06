<?php

// Triples for one record (for debugging)

error_reporting(E_ALL);


require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/core.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$cuid = new EndyJasmi\Cuid;

$nquads = new NQuads();

//----------------------------------------------------------------------------------------


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

$id = '0000-0001-6419-2046';

$id ='0000-0001-9598-5583';

//$id ='0000-0002-2953-2815';

$id = '0000-0001-6419-2046';
$id = '0000-0001-9469-8857';
//$id = '0000-0003-0336-8305';

$id = '0000-0002-3210-7537';

$id = '0000-0002-6957-4673'; // fixed

//$id = '0000-0002-9237-1364';
//$id = '0000-0003-3808-3131';

$id = '0000-0001-5350-9984';
$id = '0000-0002-8084-2640';  // vert bad, @id and identifier value has '}' suffix FFS!



$directory = $config['cache'] . '/' . id_to_dir($id);

$filename = $directory . '/' . $id . '.json';

$output = $directory . '/' . $id . '.nt';


//echo $id . "\n";

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

if (preg_match('/"url" : \[ "(?<url>[^"]+)"/', $json, $m))
{
	$original = $m[0];
	if (!preg_match('/^https?:\/\//', $m['url']))
	{
		$new = '"url" : [ "http://' . $m['url'] . '"';
		
		$json = str_replace($original, $new, $json);
	}
}

// Decode JSON, find sameAs and ensure that all are URIs,
// then recreate JSON
$obj = json_decode($json);

print_r($obj);



foreach ($obj->{'@reverse'} as $key => &$item)
{
	if ($key == 'creator')
	{	
		if (!is_array($item))
		{
			if (isset($item->sameAs))
			{
				$item->sameAs = fix_urls($item->sameAs);
			}
		}
		else
		{	
			foreach ($item as &$work)
			{
				foreach ($work as $k => $v)
				{
					if ($k == 'sameAs')
					{
						$work->sameAs = fix_urls($work->sameAs);
					}
					
					/*
					// need to also fix identifier, see 0000-0002-8084-2640
					if ($k == '@id')
					{
						$work->{'@id'} = preg_replace('/\}$/', '', $work->{'@id'});
					}
					*/
				}
			}
		}
	}
}

// url
if (isset($obj->url))
{
	$obj->url = fix_urls($obj->url);
}

if (0)
{
	// check that our fix has worked
	foreach ($obj->{'@reverse'} as &$creator)
	{
		foreach ($creator as &$work)
		{
			foreach ($work as $k => $v)
			{
				if ($k == 'sameAs')
				{
					print_r($v);
				}
			}
		}
	}
}

// fix context so we don't try and resolve it
if (1)
{
	$sameAs = new stdclass;
	$sameAs->{'@id'} = "sameAs";
	$sameAs->{'@type'} = "@id";
	$sameAs->{'@container'} = "@set";

	$mainEntityOfPage = new stdclass;
	$mainEntityOfPage->{'@id'} = "mainEntityOfPage";
	$mainEntityOfPage->{'@type'} = "@id";

	$url = new stdclass;
	$url->{'@id'} = "url";
	$url->{'@type'} = "@id";
	
	$obj->{'@context'} = (object)array(
		'@vocab' 			=> 'http://schema.org/',
		'sameAs' 			=> $sameAs,
		'mainEntityOfPage' 	=> $mainEntityOfPage,
		'url'				=> $url,
		);
	
}

$json = json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


$quads = JsonLD::toRdf($json);
$serialized = $nquads->serialize($quads);

//echo $serialized;

$serialized = fix_triples($serialized);

echo $serialized;

echo "\n$filename\n";

file_put_contents($output, $serialized);


?>


