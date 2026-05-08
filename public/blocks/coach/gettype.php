<?php
require_once('../../config.php');
require_once('../../lib/filelib.php');
require_once('lib_file.php');
require_login(0, false);
if(isset($_GET['id'])){
	$id=$_GET['id'];
	$row= $DB->get_record('coach_resource',array('id'=>$id));
	$folder = substr($row->contenthash,0,3);
	$file = $CFG->dataroot."/resources/".$folder."/".$row->contenthash;
	$etag = $row->contenthash;
	// IF FILE NAME IS NOT IN THE LIST OF VIEWER, IT WILL REQUIRE TO DOWNLOAD IT
	$temp = explode(".", $row->filename);
	$type = strtolower(end($temp));	// mp4 or pdf or doc
	$filetype = $row->filetype; //application/pdf or video/mp4
	$filename=$row->filename;

	switch ($type) {
		case 'pdf':
			header("Content-Type: application/pdf");
			header('Content-Disposition: inline; filename="' .$filename. '"');
			header('Content-Description: File transfer');
			$content = file_get_contents($file);
			echo $content;
			echo "<head><title> </title></head>";
			exit();
		//Can implement later if we have internal plugin for viewing DOC, DOCX	
		case 'docx':
		case 'doc':
			header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
			header('Content-Disposition: attachment;filename="'.$filename.'"');
			$content = file_get_contents($file);
			echo $content;
			exit();
		case 'mp3':
			$size = filesize($file);
			 header("Pragma: public");
			 header("Expires: 0"); 
			 header("Content-Type: audio/mpeg");
			 header('Content-Length: ' . $size);
			 header('Content-Disposition: inline; filename="' .$filename. '"');
			 header( 'Content-Range: bytes 0-'.($size-1).'/'.$size);
			 header( 'Accept-Ranges: bytes');
			 header('X-Pad: avoid browser bug');
			 header('Cache-Control: no-cache');
			$content = file_get_contents($file);
			echo $content;
			exit();
		// case "flv":
		// case 'mov':
		case 'mp4':
		// $file = "C:\moodledata/filedir/17/73/17735b4721faa7d59e1fd8a2300135a3f04c9ea6";
			// define('NO_DEBUG_DISPLAY', true);
			// $new_file = new stdClass();
			// $new_file->path = $file;
			// $new_file->filesize = filesize($file);
			// $new_file->mimetype = $filetype;
			// $new_file->filename = $filename;
			// $new_file->lastmodified = time()-3;
			// $new_file->etag = $etag;
			// send_stored_file_resources($new_file);
			// die();

			if (file_exists($file)) {
				// Clears the cache and prevent unwanted output
				
				$mime = "video/mp4"; // The MIME type of the file, this should be replaced with your own.
				$size = filesize($file); // The size of the file
				// Send the content type header

					// $flag=isset($_SERVER['HTTP_RANGE']);

					 // var_dump($_SERVER);
					 //  die();
				 // ob_clean();

						header('Content-Disposition: inline; filename="'.$filename.'"');
				        header('Cache-Control:private, max-age=2138, no-transform');
				        header('Expires: '. gmdate('D, d M Y H:i:s', time() + 2138) .' GMT');
				        header('Pragma: ');

						header('Content-type: ' . $mime);
						header('Etag: "'.$etag.'"');
						if (isset($_SERVER['HTTP_IF_NONE_MATCH']) and trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) { header('HTTP/1.1 304 Not Modified');
						            return;}
						 header('Accept-Ranges: bytes');
						 header('Content-Length: 0');
					    if ($size > 10000000) {
					        // for large files try to flush and close all buffers to conserve memory
					        while(@ob_get_level()) {
					            if (!@ob_end_flush()) {
					                break;
					            }
					        }
					    }
				if (!empty($_SERVER['HTTP_RANGE']) and strpos($_SERVER['HTTP_RANGE'],'bytes=') !== FALSE) {
					if(ini_get('zlib.output_compression')) {
						ini_set('zlib.output_compression', 'Off'); 
					}
					// Parse the range header to get the byte offset\
					$ranges = array_map(
						'intval', // Parse the parts into integer
						explode(
							'-', // The range separator
							substr($_SERVER['HTTP_RANGE'], 6) // Skip the `bytes=` part of the header
						)
					);
					// If the last range param is empty, it means the EOF (End of File)
					if(!$ranges[1]){
						$ranges[1] = $size - 1;
					}
					// Send the appropriate headers
					header('HTTP/1.1 206 Partial Content');
					header('Content-type: ' . $mime);
					header('Accept-Ranges: bytes');
					header('Content-Length: ' . ($ranges[1] - $ranges[0])); // The size of the range

					// Send the ranges we offered
					header(
						sprintf(
							'Content-Range: bytes %d-%d/%d', // The header format
							$ranges[0], // The start range
							$ranges[1], // The end range
							$size // Total size of the file
						)
					);
					// It's time to output the file
					$f = fopen($file, 'rb'); // Open the file in binary mode
					$chunkSize = 8192; // The size of each chunk to output

					// Seek to the requested start range
					fseek($f, $ranges[0]);

					// Start outputting the data
					while(true){
						// Check if we have outputted all the data requested
						if(ftell($f) >= $ranges[1]){
							break;
						}
						// Output the data
						echo fread($f, $chunkSize);
						// Flush the buffer immediately
						@ob_flush();
						flush();
					}

				}
				header('Connection: close');
				readfile_allow_large($file);
			}
		default:
		  	header("Content-disposition: attachment; filename=".$filename);
		  	header('Content-Disposition: inline; filename="' .$filename. '"');
		  	header("Content-Type: ".$filetype);
		  	readfile($file);
			exit();
	}
}
?>