<?php

// For each source file generate corresponding triples file

// php -d memory_limit=-1 triples.php

error_reporting(E_ALL);


require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/core.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$cuid = new EndyJasmi\Cuid;


$force = false;


$files1 = scandir($config['cache']);

$nquads = new NQuads();

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
				
				echo $id . "\n";
							
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
				
				$output = $config['cache'] . '/' . $directory . '/' . $id . '.nt';
				
				if (!file_exists($output) || $force)
				{				
					echo $id . "\n";
					$quads = JsonLD::toRdf($json);
					$serialized = $nquads->serialize($quads);
					$serialized = fix_triples($serialized);
					file_put_contents($output, $serialized);
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


