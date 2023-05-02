<?php

// For each source file generate corresponding triples file

// php -d memory_limit=-1 triples.php

ini_set('memory_limit', '-1');

error_reporting(E_ALL);


require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/core.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$cuid = new EndyJasmi\Cuid;


$force = false;

$since = 0;

// yesterday
$since = strtotime('-1 day');
//$since = strtotime('-1 month');
//$since = strtotime('-2 month');

$files1 = scandir($config['cache']);

// if we are restaring from a broken harvest,
// ignore every directory up to this point.
if (0)
{
	$id = '0000-0003-4843-680X';
	$from = id_to_dir($id);

	$key = array_search($from, $files1);
	$files1 = array_slice($files1, $key);
}

$nquads = new NQuads();

$count = 1;

foreach ($files1 as $directory)
{
	if (preg_match('/^\d+$/', $directory))
	{	
		echo $directory . "\n";
	
		$files2 = scandir($config['cache'] . '/' . $directory);
		
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.json$/', $filename))
			{
				$id = str_replace('.json', '', $filename);	
				
				$go = false;
				
				if (1)
				{
					// update if source modified
					// use this if we just want to process newly added JSON,
					// thus is the most likely option
					$json_filename = id_to_path($id);			
					$modified = filemtime($json_filename);
					$go = $modified > $since;
				}
				else
				{
					// update if triples modified
					// use this if we are fixing bugs in triples introduced
					// after a certain point
					$nt_filename = str_replace('.json', '.nt', id_to_path($id));
					
					if (!file_exists())
					{
						$go = false;
					}
					else
					{
						$modified = filemtime($nt_filename);	
						$go = $modified > $since;
					}									
				}
				
				
						
				if ($go || $force)
				{				
							
					$json = get_one($id);
				
					// fix JSON
				
					$bad_json = false;
				
					// "@id" : "grid.1214.6",
					if (preg_match('/"@id" : "(grid..*)"/', $json))
					{			
						$json = preg_replace('/"@id" : "(grid..*)"/', '"@id" : "https://www.grid.ac/institutes/$1"', $json);				
						$bad_json = true;
					}
				
					if (preg_match('/"url" : "(?<url>[^"]+)"/', $json, $m))
					{
						$original = $m[0];
						if (!preg_match('/^https?:\/\//', $m['url']))
						{
							$new = '"url" : "http://' . $m['url'] . '"';
		
							$json = str_replace($original, $new, $json);
							$bad_json = true;
						}
					}				
				
					if ($bad_json)
					{					
						// store fixed JSON
						$json_filename = $config['cache'] . '/' . $directory . '/' . $id . '.json';
						$json_filename_bkp = $json_filename . '.old';
					
						rename($json_filename, $json_filename_bkp); 
						file_put_contents($json_filename, $json);
					
						echo "Needs fix\n";
						$force = true;
					}
				
					// Decode JSON, find sameAs and ensure that all are URIs,
					// then recreate JSON
					$obj = json_decode($json);

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
				
					$output = $config['cache'] . '/' . $directory . '/' . $id . '.nt';
				
					//if (!file_exists($output) || $force)
					{				
						echo $id . "\n";
						$quads = JsonLD::toRdf($json);
						$serialized = $nquads->serialize($quads);
						$serialized = fix_triples($serialized);
						file_put_contents($output, $serialized);
					
						if (0)
						{
							// Give server a break every 10 items
							// Need this because code keeps hitting schema.org!!
							if (($count++ % 10) == 0)
							{
								$rand = rand(1000000, 3000000);
								echo "\n-- ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
								usleep($rand);
							}
						}					
					}
				}
				else
				{
					echo "$id done\n";
				}
				
			}
		}
	}
}

?>


