<?php


/*

Things you may need to edit:

id_to_dir: If id isn't an integer you will need to convert it to one
fetch_one: add URL to fetch data
fix_triples: do anything you need to make triples acceptable to a triple store

*/


$config['cache']	= dirname(__FILE__) . '/cache';
$config['fresh'] 	= 60; // time in seconds beyond which we think data needs to be refreshed
$config['url'] 		= 'https://orcid.org/<ID>';
$config['mime']		= 'application/ld+json';

$fetch_count 		= 1;

//----------------------------------------------------------------------------------------
function get($url, $format = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	if ($format != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	switch ($info['http_code'])
	{
		case 404:
			echo "$url Not found\n";
			exit();
			break;
			
		case 429:
			echo "Blocked\n";
			exit();
			break;
	
		default:
			break;
	}	
	
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
function id_to_filename($id)
{
	return $id . '.json';
}


//----------------------------------------------------------------------------------------
function id_to_path($id)
{
	global $config;

	$path = $config['cache'] . '/' . id_to_dir($id) . '/' . id_to_filename($id);
	
	return $path;
	
}

//----------------------------------------------------------------------------------------
function get_one($id, $force = false, $refresh = false)
{
	global $config;
	
	$data = null;
	
	$filename = id_to_path($id);
	
	$ok = false;
	
	if (file_exists($filename) && !$force)
	{
		$ok = true;
		
		// can just load from file, but check that it is fresh
		$modified = filemtime($filename);
		$since = (time() - $modified) / $config['fresh']; // seconds since file modified
		
		// echo "Last modified: $since\n";
				
		// Fetch if old ("refresh")
		if ($refresh && ($since > $config['fresh']))
		{
			$ok = false;		
			$force = true;	
			// echo "File is older than" . $config['fresh'] . " seconds so refresh\n";
		}
	}
	
	if (!$ok)
	{
		fetch_one($id, $force);
	}
	
	
	if (file_exists($filename))
	{
		$data = file_get_contents($filename);
	}
	
	return $data;

}



//----------------------------------------------------------------------------------------
// Convert id to a directory, requires that id is an integer, or can be converted to one
function id_to_dir($id)
{
	// ORCID-specific code
	$number = $id;
	$number = str_replace('-', '', $number);
	$number = preg_replace('/X$/', '0', $number);
	$number = preg_replace('/^0+/', '', $number);
	
	$dir = floor($number / 1000);	
	
	return $dir;
}


//----------------------------------------------------------------------------------------
// Fetch one RDF record, if forcing oe we don't have, then fect from source
function fetch_one($id, $force = false)
{
	global $config;
	
	global $fetch_count;

	$filename = id_to_path($id);
	
	if (!file_exists($filename) || $force)
	{
		$dir = $config['cache'] . '/' . id_to_dir($id);
		
		if (!file_exists($dir))
		{
			$oldumask = umask(0); 
			mkdir($dir, 0777);
			umask($oldumask);
		}
		
		$url = $config['url'];
		$url = str_replace('<ID>', $id, $url);
		$json = get($url, $config['mime']);
		
		file_put_contents($filename, $json);
		
		// Give server a break every 10 items
		if (($fetch_count++ % 10) == 0)
		{
			$rand = rand(1000000, 3000000);
			echo "\n...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
			usleep($rand);
		}

	}

}


//----------------------------------------------------------------------------------------
// Fix any issues we might have with triples
function fix_triples($triples)
{
	global $cuid;
	
	$lines = explode("\n", $triples);
	
	// print_r($lines);	
	
	// b-nodes
	$bnodes = array();
	
	// build list of b-nodes
	foreach ($lines as &$line)
	{
		if (preg_match('/^(?<id>_:b\d+)/', $line, $m))
		{
			if (!isset($bnodes[$m['id']]))
			{
				$bnodes[$m['id']] = '_:' . $cuid->cuid();
			}
		}
		if (preg_match('/(?<id>_:b\d+)\s+\.\s*$/', $line, $m))
		{
			//print_r($m);
			if (!isset($bnodes[$m['id']]))
			{
				$bnodes[$m['id']] = '_:' . $cuid->cuid();
			}
		}		
	}
	
	// print_r($bnodes);
	
	foreach ($lines as &$line)
	{
		if (preg_match('/^(?<id>_:b\d+)/', $line, $m))
		{
			$line = preg_replace('/^(_:b\d+)/', $bnodes[$m['id']], $line);
		}
		
		if (preg_match('/(?<id>_:b\d+)\s+\.\s*$/', $line , $m))
		{
			$line = preg_replace('/(_:b\d+)\s+\.\s*$/', $bnodes[$m['id']]. " . ", $line);
		}		
	}
	
	// fix bad URIs
	foreach ($lines as &$line)
	{
		//echo $line . "\n";
		if (preg_match_all('/\<(?<uri>(https?|URI:\s+).*)\>\s/iU', $line, $m))
		{
			foreach ($m['uri'] as $original_uri)
			{				
				//echo "|$original_uri|\n";
			
				$uri = $original_uri;
				
				$uri = str_replace('<', '%3C', $uri);
				$uri = str_replace('>', '%3E', $uri);

				$uri = str_replace('[', '%5B', $uri);
				$uri = str_replace(']', '%5D', $uri);
			
				$uri = str_replace(' ', '%20', $uri);	
				$uri = str_replace('"', '%22', $uri);	
							
				$uri = str_replace('{\_}', '', $uri);
				$uri = str_replace('\_', '', $uri);
				
				$uri = str_replace('}', '', $uri);	
				$uri = str_replace('{', '', $uri);					
				
				$uri = preg_replace('/URI:\s+/', '', $uri);	

				$uri = preg_replace('/%x/', '', $uri);	
				
				$uri = preg_replace('/\x91/', ' ', $uri);
				
				$uri = preg_replace('/\x{C296}/u', ' ', $uri);
				
				$uri = preg_replace('/%20$/', '', $uri);	
				
				$uri = str_replace('http://Http://', 'http://', $uri);
									
				$line = str_replace('<' . $original_uri . '>', '<' . $uri . '>', $line);
			}
		}
		
		// known bad examples
		// 0000-0002-9444-8716
		$line = str_replace('http://www.prtrg.org/pdf/proceedings-prtrg-12.pdf#page=33http://www.prtrg.org/pdf/proceedings-prtrg-12.pdf#page=33', 'http://www.prtrg.org/pdf/proceedings-prtrg-12.pdf#page=33', $line);
		
		// 0000-0001-9469-8857
		//$line = str_replace('http://Http://', 'http://', $line);
		//$line = preg_replace('/\s+"\s+\.$/', '" .', $line);
		
		// 0000-0003-2861-949X
		// manually fix
		
		
		
	}
	
	// fix bad characters
	foreach ($lines as &$line)
	{
		// CR
		$line = preg_replace('/\x0D/', ' ', $line);
		
		// escape \
		$line = preg_replace('/\\\(.)/', '\\$1', $line);
		
		// BibTeX crap
		// {'}
		$line = preg_replace("/\{'\}/", "'", $line);

		// {$1"O}	
		$line = str_replace('{$1"O}', "Ö", $line);		
		
		$line = preg_replace('/\x{96}/u', '-', $line);
	}		
	
	$new_triples = join("\n", $lines);
	$new_triples .= "\n"; 
	
	return $new_triples;

}

//----------------------------------------------------------------------------------------
// Ensure one or more urls are valid`
function fix_urls($input_sameas)
{
	$sameAs = array();
	if (!is_array($input_sameas))
	{
		$input_sameas = array($input_sameas);
	}
	foreach ($input_sameas as $value)
	{
		// clean
		$value = preg_replace('/\x{96}/u', '-', $value);
	
		if (preg_match('/^https?:\/\//', $value))
		{
			$sameAs[] = trim ($value);
		}
	}
	
	$sameAs = array_unique($sameAs);
	
	return $sameAs;	
}



?>
