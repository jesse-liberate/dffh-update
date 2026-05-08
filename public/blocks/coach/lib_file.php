<?php
function readfile_allow_large_resources($path, $filesize = -1) {
    // Automatically get size if not specified.
    if ($filesize === -1) {
        $filesize = filesize($path);
    }
    // var_dump(headers_list());
    // echo $filesize;
    //      die();
    if ($filesize <= 2147483647) {
        // If the file is up to 2^31 - 1, send it normally using readfile.
        // echo $path;
        return readfile($path);
    } else {
        // For large files, read and output in 64KB chunks.
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return false;
        }
        $left = $filesize;
        while ($left > 0) {
            $size = min($left, 65536);
            $buffer = fread($handle, $size);
            if ($buffer === false) {
                return false;
            }
            echo $buffer;
            $left -= $size;
        }
        return $filesize;
    }
}

function readfile_accel_resources($file, $mimetype) {
    global $CFG;

    if ($mimetype === 'text/plain') {
        // there is no encoding specified in text files, we need something consistent
        header('Content-Type: text/plain; charset=utf-8');
    } else {
        header('Content-Type: '.$mimetype);
    }
     // var_dump($file);
     // die();
    // $lastmodified = is_object($file) ? $file->get_timemodified() : filemtime($file);
    $lastmodified = $file->lastmodified;
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');

    header('Etag: "' . $file->etag. '"');
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) and trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $file->etag) {
        header('HTTP/1.1 304 Not Modified');
        return;
    }

    $filesize = $file->filesize;

    header('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');

    if ($mimetype !== 'text/plain') {
        header('Accept-Ranges: bytes');
        // echo "TEST";
        // die();
        if (!empty($_SERVER['HTTP_RANGE']) and strpos($_SERVER['HTTP_RANGE'],'bytes=') !== FALSE) {
            // byteserving stuff - for acrobat reader and download accelerators
            // see: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35
            // inspired by: http://www.coneural.org/florian/papers/04_byteserving.php
            $ranges = false;
            if (preg_match_all('/(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $ranges, PREG_SET_ORDER)) {
                foreach ($ranges as $key=>$value) {
                    if ($ranges[$key][1] == '') {
                        //suffix case
                        $ranges[$key][1] = $filesize - $ranges[$key][2];
                        $ranges[$key][2] = $filesize - 1;
                    } else if ($ranges[$key][2] == '' || $ranges[$key][2] > $filesize - 1) {
                        //fix range length
                        $ranges[$key][2] = $filesize - 1;
                    }
                    if ($ranges[$key][2] != '' && $ranges[$key][2] < $ranges[$key][1]) {
                        //invalid byte-range ==> ignore header
                        $ranges = false;
                        break;
                    }
                    //prepare multipart header
                    $ranges[$key][0] =  "\r\n--".BYTESERVING_BOUNDARY."\r\nContent-Type: $mimetype\r\n";
                    $ranges[$key][0] .= "Content-Range: bytes {$ranges[$key][1]}-{$ranges[$key][2]}/$filesize\r\n\r\n";
                }
            } else {
                $ranges = false;
            }

            if ($ranges) {
                $handle = fopen($path, 'rb');
                byteserving_send_file_resources($handle, $mimetype, $ranges, $filesize);
            }
        }
    } else {
        // Do not byteserve
        header('Accept-Ranges: none');
    }

    header('Content-Length: '.$filesize);

    if ($filesize > 10000000) {
        // for large files try to flush and close all buffers to conserve memory
        while(@ob_get_level()) {
            if (!@ob_end_flush()) {
                break;
            }
        }
    }
    $path = $file->path;
    if(file_exists($path)){
    	// $path = "C:\moodledata/filedir/17/73/17735b4721faa7d59e1fd8a2300135a3f04c9ea6";
    	readfile_allow_large_resources($path, $filesize);
	}
}

function byteserving_send_file_resources($handle, $mimetype, $ranges, $filesize) {
    // better turn off any kind of compression and buffering
    ini_set('zlib.output_compression', 'Off');

    $chunksize = 1*(1024*1024); // 1MB chunks - must be less than 2MB!
    if ($handle === false) {
        die;
    }
    if (count($ranges) == 1) { //only one range requested
        $length = $ranges[0][2] - $ranges[0][1] + 1;
        header('HTTP/1.1 206 Partial content');
        header('Content-Length: '.$length);
        header('Content-Range: bytes '.$ranges[0][1].'-'.$ranges[0][2].'/'.$filesize);
        header('Content-Type: '.$mimetype);

        while(@ob_get_level()) {
            if (!@ob_end_flush()) {
                break;
            }
        }

        fseek($handle, $ranges[0][1]);
        while (!feof($handle) && $length > 0) {
            core_php_time_limit::raise(60*60); //reset time limit to 60 min - should be enough for 1 MB chunk
            $buffer = fread($handle, ($chunksize < $length ? $chunksize : $length));
            echo $buffer;
            flush();
            $length -= strlen($buffer);
        }
        fclose($handle);
        die;
    } else { // multiple ranges requested - not tested much
        $totallength = 0;
        foreach($ranges as $range) {
            $totallength += strlen($range[0]) + $range[2] - $range[1] + 1;
        }
        $totallength += strlen("\r\n--".BYTESERVING_BOUNDARY."--\r\n");
        header('HTTP/1.1 206 Partial content');
        header('Content-Length: '.$totallength);
        header('Content-Type: multipart/byteranges; boundary='.BYTESERVING_BOUNDARY);

        while(@ob_get_level()) {
            if (!@ob_end_flush()) {
                break;
            }
        }

        foreach($ranges as $range) {
            $length = $range[2] - $range[1] + 1;
            echo $range[0];
            fseek($handle, $range[1]);
            while (!feof($handle) && $length > 0) {
                core_php_time_limit::raise(60*60); //reset time limit to 60 min - should be enough for 1 MB chunk
                $buffer = fread($handle, ($chunksize < $length ? $chunksize : $length));
                echo $buffer;
                flush();
                $length -= strlen($buffer);
            }
        }
        echo "\r\n--".BYTESERVING_BOUNDARY."--\r\n";
        fclose($handle);
        die;
    }
}


function send_stored_file_resources($stored_file, $lifetime=null, $forcedownload=false) {
    global $CFG;

    if ($lifetime === 'default' or is_null($lifetime)) {
        $lifetime = $CFG->filelifetime;
    }

    $mimetype = $stored_file->mimetype;
    $filename = $stored_file->filename;
    // Otherwise guess it.
    if (!$mimetype || $mimetype === 'document/unknown') {
        $mimetype = get_mimetype_for_sending($filename);
    }

    // if user is using IE, urlencode the filename so that multibyte file name will show up correctly on popup
    if (core_useragent::is_ie()) {
        $filename = rawurlencode($filename);
    }

    if ($forcedownload) {
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    } else if ($mimetype !== 'application/x-shockwave-flash') {
        // If this is an swf don't pass content-disposition with filename as this makes the flash player treat the file
        // as an upload and enforces security that may prevent the file from being loaded.

        header('Content-Disposition: inline; filename="'.$filename.'"');
    }

    if ($lifetime > 0) {
        $cacheability = ' private,';
        header('Cache-Control:'.$cacheability.' max-age='.$lifetime.', no-transform');
        header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
        header('Pragma: ');

    } else { // Do not cache files in proxies and browsers
        if (is_https()) { // HTTPS sites - watch out for IE! KB812935 and KB316431.
            header('Cache-Control: private, max-age=10, no-transform');
            header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0, no-transform');
            header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            header('Pragma: no-cache');
        }
    }
    // Allow cross-origin requests only for Web Services.
    // This allow to receive requests done by Web Workers or webapps in different domains.
    if (WS_SERVER) {
        header('Access-Control-Allow-Origin: *');
    }
        // send the contents
        readfile_accel_resources($stored_file, $mimetype);
    die;
}

?>