<?php

if (!defined('AA_CL_HTTP_DATA_LIB_INCLUDED'))
{
	define('AA_CL_HTTP_DATA_LIB_INCLUDED', 1);
}else{
	return;
}


//error_reporting(-1); // off
//error_reporting(E_ALL & ~E_NOTICE); // All on
//ini_set('display_errors', 1);


/************************************
 *
 * HTTP_data
 *
 * Can send post and get request for a http server.
 * We can also download files in background and show progress
 *
/************************************/

//require("common.lib.php");

/*if(AA_connectToDB() == FALSE){ // invalid db connection
	return;
}*/

if(AA_checkMeetingID() == FALSE){		// no meeting selected
	return;		// abort
}


class HTTP_data{
	
	
	/**
	* encrypted:
	*/
	
	// sends a get request for (usually) downloading a file
	function send_get_TLS($host, $uri, $parseType, $username="", $pw="", $fileName="", $showProcess=false){
		
		// auth-statement for base auth
		$auth = base64_encode("$username:$pw");
		
		// (maybe) it is necessary to use tls:// instead of https:// 
		$host2 = substr($host, strpos($host,"://")+3);
		$host3 = "tls://" . $host2;
		
		$sock = fsockopen($host3, 443, $errno, $errstr, 3600); // important: timeout is 1h, else the download could cancel
		if(!$sock){
			AA_printErrorMsg("Error 1: $strErrHttpSock");
			return false;
		}
		fwrite($sock, "GET $uri HTTP/1.1\r\n");
		fwrite($sock, "Host: $host2\r\n");
		fwrite($sock, "Connection: Close\r\n");
		fwrite($sock, "Authorization:Basic $auth\r\n");
		fwrite($sock, "\r\n");
		
		$chunked = false;
		$headers = "";
		$totalbytes = 0;
		while ($str = trim(fgets($sock, 4096))){
			$headers .= "$str\n";
			$a = explode(":", $str); // get total lenght of file
			if($a[0] == "Content-Length"){
				$totalbytes = trim($a[1]);
			}elseif($a[0] == "Transfer-Encoding"){
				if (strtolower(trim($a[1])) == "chunked"){
					$chunked = true;
					//echo("!!! is recognized as chunked !!!");
				}
			}
			
		}
		
		$body = "";
		$currentbytes = 0;
		$count = 5;
		while (!feof($sock)){
			$str = fread($sock, 8192);
			$body .= $str;
			$currentbytes += strlen($str); // calculate state of download progress
			if($totalbytes != 0){ $percentage = (($currentbytes / $totalbytes)*100); }
			if($percentage >= $count){
				$width = $count*1.5; // width of progress bar (max 150px)
				if($showProcess){ // if progress bar is available
					?>
<script type="text/javascript">
document.getElementById("progress").width="<?php echo $width ?>";
</script>
					<?php
				}
				ob_flush();
				flush();
				$count += 5;
			}
		}
		fclose($sock);
		
		if($showProcess){
			?>
<script type="text/javascript">
document.getElementById("progress").width="150";
</script>
			<?php
		}
		
		// get temp directory
		$temppath = dirname($_SERVER['SCRIPT_FILENAME'])."/tmp/";
		
		if ($chunked){
			// the body is in chunked format --> redo stuff together, remove all header and footer of each chunk
			// data is transferred as chunk (http 1.1), where the chunk itself is preceded by its size on a separate line --> split it
			
			// copy the body to chunkedData, so that body can be resetted
			$chunkedData = $body;
			$body = "";
			
			// start at the beginning
			$position = 0;
			
			while ($position < strlen($chunkedData)){
				// until which position the length of the chunk is given
				$lenTo = strpos($chunkedData,"\r\n", $position); // until the first occurence is the total chunk length given
				
				// should not happen, but to make sure not to create an infinite loop:
				if ($lenTo===false){
					var_dump("This loop should not came here. Something is wrong...");
					break;
				}
				
				// the total length of the chunk, including the ending with \r\n (0D 0A) as hex
				$lenHex = substr($chunkedData,$position, $lenTo-$position);
				
				// if this string is 0, the this is the last chunk (per http RFC2616 definition)
				if ($lenHex == "0"){
					//var_dump('regular end of while');
					break;
				}
				
				// as normal number
				$chunkLen = hexdec($lenHex);
				
				// add data of first chunk to body:
				$body .= substr($chunkedData, $lenTo+2, $chunkLen); //minus 2, because the chunk ends with \r\n (0D 0A)
				
				$position = $position + $chunkLen+4+strlen($lenHex); // 4 bytes for twice CRLF=\r\n=0D 0A
			}
		}
		
		// TODO: for debug only; problematic/difficult with long bodies like Stammdaten
		//var_dump($body);
		
		if(preg_match('#.*unauthorized.*#i', $body)){
			return 'unauthorized';
		}elseif($parseType == "ini"){
			// parse content as ini and return parameters
			// first we have to save it temporary
			$filePath .= $temppath."athletica.ini";
			$fp = fopen($filePath, 'w');
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return parse_ini_file($filePath);
			}
		}elseif($parseType == "file" && !empty($fileName)){			
			// save data into file
			
			$filePath .= $temppath.$fileName;
			$fp = fopen($filePath, 'wb');
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return $filePath;
			}
		} elseif(strtolower($parseType) == "autojson"){
			// some messages by alabus are in json format, this is an example:
			//{"severity":"ERROR","errorCode":"PARAMEXC","debugMessage":"Import-File 'results.xml.gz' already exists! This Import is already done!","userFriendlyMessage":"Ein Fehler ist aufgetreten.","lic":"Err.Sys.Global.Error"}
			// this option return eiter the result string if it is a string or alternatively the JSON's' userFriendyMessage and debugMessage
			if (preg_match('#\{.*\}#', $body)==1){
				
				// is assumed to be json
				$arr = json_decode($body, true);
				
				$answer = "";
				if (array_key_exists('userFriendlyMessage', $arr)){
					$answer .= $arr['userFriendlyMessage']."  ";
				}
				if (array_key_exists('debugMessage', $arr)){
					$answer .= $arr['debugMessage'];
				}
				
				if ($answer==""){
					// both keys did not exist, return body.
					$answer = $body;
				}
				return $answer;
				
			} else{
				// no json --> return string
				return $body;
			} 
		} else {
			return $body;
		}
		
	}
	
	// function for sending a post request... usually for sending form data
	//  - host should return ini data
	function send_post_TLS($host, $uri, $post, $username, $pw, $parseType="ini", $fileName="", $multipart=false){
		
		$contentType = "application/x-www-form-urlencoded";
		if ($multipart){
			$contentType = "multipart/form-data; boundary=AthleticA"; // TODO: probably with , instead of ; (is different in RFC 1867 and RFC 1341)
		}
		
		// auth-statement for base auth
		$auth = base64_encode("$username:$pw");
		
		// (maybe) it is necessary to use tls:// instead of https:// 
		$host2 = substr($host, strpos($host,"://")+3);
		$host3 = "tls://" . $host2;
		
		$sock = fsockopen($host3, 443, $errno, $errstr, 30);
		if(!$sock){
			AA_printErrorMsg($strErrHttpSock);
			return false;
		}
		$data = "";
		$data .= $post;
		
		fwrite($sock, "POST $uri HTTP/1.0\r\n");
		fwrite($sock, "Host: $host2\r\n");
		fwrite($sock, "Content-type: $contentType\r\n");
		fwrite($sock, "Content-length: " . strlen($data) . "\r\n");
		fwrite($sock, "Authorization:Basic $auth\r\n");
		fwrite($sock, "Accept: */*\r\n");
		fwrite($sock, "\r\n");
		fwrite($sock, "$data\r\n\r\n");  
		
		$headers = "";
		$totalbytes = 0;
		while ($str = trim(fgets($sock, 4096))){
			$headers .= "$str\n";
			if(strpos($str, " 400 ") !== false || strpos($str, " 404 ") !== false || strpos($str, " 408 ") !== false || strpos($str, " 503 ") !== false){           echo $data;
				fclose($sock); // server return error (not found / bad request)
				AA_printErrorMsg($GLOBALS['strErrHttpBad'].": $str");
				return false;
			}
		}     
		
		$body = "";
		while (!feof($sock)){
			$body .= fread($sock, 8192); 
		}      
		fclose($sock);
		
       
		// get temp directory
		$temppath = dirname($_SERVER['SCRIPT_FILENAME'])."/tmp/";
		if(!is_dir($temppath)){
			@mkdir($temppath, 0777);
		}
		
		if(preg_match('#.*unauthorized.*#i', $body)){
			return 'unauthorized';
		}elseif($parseType == "ini"){
			// parse content as ini and return parameters
			// first we have to save it temporary
			$filePath .= $temppath."athletica.ini";
			$fp = fopen($filePath, 'w');
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return parse_ini_file($filePath);
			}
		}elseif($parseType == "file" && !empty($fileName)){
			// save data into file 	
            
            if (strpos($body, "\r\n\r\n") != false){      // separate body from header because of Fredy's PC   
                $body = substr($body, strpos($body, "\r\n\r\n") + 4);   
            }  
            
            $filePath .= $temppath.$fileName;
			$fp = fopen($filePath, 'wb');   
            
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return $filePath;
			}
		} elseif(strtolower($parseType) == "autojson"){
			// some messages by alabus are in json format, this is an example:
			//{"severity":"ERROR","errorCode":"PARAMEXC","debugMessage":"Import-File 'results.xml.gz' already exists! This Import is already done!","userFriendlyMessage":"Ein Fehler ist aufgetreten.","lic":"Err.Sys.Global.Error"}
			// this option return eiter the result string if it is a string or alternatively the JSON's' userFriendyMessage and debugMessage
			if (preg_match('#\{.*\}#', $body)==1){
				// is assumed to be json
				$arr = json_decode($body, true);
				
				$answer = "";
				if (array_key_exists('userFriendlyMessage', $arr)){
					$answer .= $arr['userFriendlyMessage']."  ";
				}
				if (array_key_exists('debugMessage', $arr)){
					$answer .= $arr['debugMessage'];
				}
				
				if ($answer==""){
					// both keys did not exist, return body.
					$answer = $body;
				}
				return $answer;
			} else{
				// no json --> return string
				return $body;
			}
		} else {
			return $body;
		}
	}
	
	
	/**
	* unencrypted:
	*/
	
	// sends a get request for (usually) downloading a file
	function send_get($host, $uri, $parseType, $fileName="", $showProcess=false){
		$sock = fsockopen($host, 80, $errno, $errstr, 3600); // important: timeout is 1h, else the download could cancel
		if(!$sock){
			AA_printErrorMsg($strErrHttpSock);
			return false;
		}
		fwrite($sock, "GET $uri HTTP/1.0\r\n");
		fwrite($sock, "Host: $host\r\n");
		fwrite($sock, "Connection: Close\r\n");
		fwrite($sock, "\r\n");
		
		$headers = "";
		$totalbytes = 0;
		while ($str = trim(fgets($sock, 4096))){
			$headers .= "$str\n";
			/*if(strpos($str, "400") !== false || strpos($str, "404") !== false || strpos($str, "408") !== false){
				fclose($sock); // server return error (not found / bad request)
				AA_printErrorMsg($strErrHttpBad);
				return false;
			}*/
			$a = explode(":", $str); // get total lenght of file
			if($a[0] == "Content-Length"){
				$totalbytes = trim($a[1]);
			}
		}
		
		$body = "";
		$currentbytes = 0;
		$count = 5;
		while (!feof($sock)){
			$str = fread($sock, 8192);
			$body .= $str;
			$currentbytes += strlen($str); // calculate state of download progress
			if($totalbytes != 0){ $percentage = (($currentbytes / $totalbytes)*100); }
			if($percentage >= $count){
				$width = $count*1.5; // width of progress bar (max 150px)
				if($showProcess){ // if progress bar is available
					?>
<script type="text/javascript">
document.getElementById("progress").width="<?php echo $width ?>";
</script>
					<?php
				}
				ob_flush();
				flush();
				$count += 5;
			}
		}
		fclose($sock);
		
		if($showProcess){
			?>
<script type="text/javascript">
document.getElementById("progress").width="150";
</script>
			<?php
		}
		
		// get temp directory
		$temppath = dirname($_SERVER['SCRIPT_FILENAME'])."/tmp/";
		
		if($parseType == "ini"){
			// parse content as ini and return parameters
			// first we have to save it temporary
			$filePath .= $temppath."athletica.ini";
			$fp = fopen($filePath, 'w');
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return parse_ini_file($filePath);
			}
		}elseif($parseType == "file" && !empty($fileName)){
			// save data into file
			$filePath .= $temppath.$fileName;
			$fp = fopen($filePath, 'wb');
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return $filePath;
			}
		}
		
	}
	          
	// function for sending a post request... usually for sending form data
	//  - host should return ini data
	function send_post($host, $uri, $post, $parseType="ini", $fileName=""){
		$sock = fsockopen($host, 80, $errno, $errstr, 30);
		if(!$sock){
			AA_printErrorMsg($strErrHttpSock);
			return false;
		}
		$data = $post;
		fwrite($sock, "POST $uri HTTP/1.0\r\n");
		fwrite($sock, "Host: $host\r\n");
		fwrite($sock, "Content-type: application/x-www-form-urlencoded\r\n");
		fwrite($sock, "Content-length: " . strlen($data) . "\r\n");
		fwrite($sock, "Accept: */*\r\n");
		fwrite($sock, "\r\n");
		fwrite($sock, "$data\r\n\r\n");  
		
		$headers = "";
		$totalbytes = 0;
		while ($str = trim(fgets($sock, 4096))){
			$headers .= "$str\n";
			if(strpos($str, " 400 ") !== false || strpos($str, " 404 ") !== false || strpos($str, " 408 ") !== false || strpos($str, " 503 ") !== false){           echo $data;
				fclose($sock); // server return error (not found / bad request)
				AA_printErrorMsg($GLOBALS['strErrHttpBad']);
				return false;
			}
		}     
		
		$body = "";
		while (!feof($sock)){
			$body .= fread($sock, 8192); 
		}      
		fclose($sock);
		
       
		// get temp directory
		$temppath = dirname($_SERVER['SCRIPT_FILENAME'])."/tmp/";
		if(!is_dir($temppath)){
			@mkdir($temppath, 0777);
		}
		
		if($parseType == "ini"){
			// parse content as ini and return parameters
			// first we have to save it temporary
			$filePath .= $temppath."athletica.ini";
			$fp = fopen($filePath, 'w');
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return parse_ini_file($filePath);
			}
		}elseif($parseType == "file" && !empty($fileName)){
			// save data into file 	
            
            if (strpos($body, "\r\n\r\n") != false){      // separate body from header because of Fredy's PC   
                $body = substr($body, strpos($body, "\r\n\r\n") + 4);   
            }  
            		
            $filePath .= $temppath.$fileName;
			$fp = fopen($filePath, 'wb');   
            
			if (@fwrite($fp, $body) === FALSE) {
				fclose($fp);
				AA_printErrorMsg($strErrHttpWrite);
				return false;
			}else{
				fclose($fp);
				return $filePath;
			}
		}
	}
}

?>
