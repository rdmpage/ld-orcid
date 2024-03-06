<?php

//----------------------------------------------------------------------------------------
function chunk_nquads($nquads_filename, $chunks = 500000, $destination_dir = '')
{
	$handle   = null;
	$basename = basename($nquads_filename, '.nt');

	if ($destination_dir == '')
	{
		$destination_dir = sys_get_temp_dir();
	}
	
	echo "Chunks will be written to $destination_dir\n";	
	echo "Generating chunks...\n";

	$chunk_files = array();
	
	$total = 0;
	$count = 0;

	$file_handle = fopen($nquads_filename, "r");
	if (!$file_handle) { die ("Could not open file $nquads_filename line: " . __LINE__ . "\n"); }
	
	while (!feof($file_handle)) 
	{
		if ($count == 0)
		{
			$output_filename = $destination_dir . '/' . $basename . '-' . $total . '.nq';
			$chunk_files[] = $output_filename;
			$handle = fopen($output_filename, 'w');
		}

		$line = fgets($file_handle);
	
		fwrite($handle, $line);
	
		if (!(++$count < $chunks))
		{
			fclose($handle);
		
			$total += $count;
		
			echo $total . "\n";
			$count = 0;		
		}
	}

	fclose($handle);
	
	return $chunk_files;
}


$filename = 'nquads.nq';

$chunk_files = chunk_nquads($filename);

// write upload script

print_r($chunk_files);

$url = 'http://localhost:7878/store';

foreach ($chunk_files as $filename)
{
	$command = "curl -f --header Content-Type:application/n-quads --data-binary @" . $filename  . " '$url' --progress-bar\n";
	echo $command . "\n";
	
	$output 		= array();
	$result_code 	= 0;
	exec($command, $output, $result_code);	

	//echo "Result code=$result_code\n";
	if ($result_code != 0)
	{
		print_r($output);
		exit();
	}		

	if (count($output) > 0)
	{
		print_r($output);
		exit();
	}
	
	echo "Sleeping\n";	
    usleep(2);
}


?>
