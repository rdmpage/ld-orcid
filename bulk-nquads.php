<?php

// Generate n-quads from triples files

// By default we do this only on the most recently added files

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/core.php');

$since = 0;

// yesterday
$since = strtotime('-1 day');

$files1 = scandir($config['cache']);

$count = 1;

foreach ($files1 as $directory)
{
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($config['cache'] . '/' . $directory);
		
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.nt$/', $filename))
			{	
				$id = str_replace('.nt', '', $filename);
				$ntfile = $config['cache'] . '/' . $directory . '/' . $filename;
				
				$modified = filemtime($ntfile);
						
				if ($modified > $since)
				{
					$graph_uri = 'https://orcid.org/' . $id;

					$triples = file_get_contents($ntfile);
					$rows = explode("\n", trim($triples));
					
					foreach ($rows as &$row)
					{
						$row = preg_replace('/\.\s*$/', ' <' . $graph_uri . '> .', $row);
					}
					
					$quads = join("\n", $rows);
					echo $quads . "\n\n";
				}				
				
			}
		}
	}
}

?>
