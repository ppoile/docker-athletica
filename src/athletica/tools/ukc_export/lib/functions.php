<?php
function csv_output($content, $filename = NULL){
    $filename = (is_null($filename)) ? 'csv_export_'.date('Y-m-d_H-i-s', time()) : $filename;
    $filename = $filename.((strlen($filename)<=4 || substr($filename, -4)!=='.csv') ? '.csv' : '');

    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    echo $content;
    exit();
}

/**
* prepares a string for the use in a csv file
*
* @param string $string the string to be prepared
* @return string the prepared string
*/
function csv_prepare($string){
    $return = $string;

    $return = str_replace('"', '""', $return);
    //$return = utf8_decode($return);

    return $return;
}

function send2ir($user,$password){
$delimiter = '----WebKitFormBoundary'.uniqid();

$raw_login = array(
  "auth_service"=>"irewind",
  "auth_user"=>$user,
);
$raw_vars = array(
  "create_user"=>"true",
  "create_user_info"=>"true",
  "delete_user_info"=>"false",
  "duplicate_removal"=>"",
  "force_sync_user_info"=>"false",
  "force_sync_user_log"=>"false",
  "location_id"=>142,
  "max_time_diff"=>"",
  "update_user"=>"true",
  "update_user_info"=>"true"
);
$raw_files = array(
    'user_file' => array(
        'filename' => 'import_users.csv',
        'type' => 'application/octet-stream',
        'content' => '@import_users.csv',
        //'content' => fopen('data.txt', 'rb'),
        //'content' => 'raw contents',
    ),
    'timing_file' => array(
        'filename' => 'import_timing.csv',
        'type' => 'application/octet-stream',
        'content' => '@import_timing.csv',
        //'content' => fopen('data.txt', 'rb'),
        //'content' => 'raw contents',
    )
);

$data = create_post($delimiter, $raw_vars,$raw_files,$password);


//$handle = curl_init('https://vps.nvncompany.ro/video-processor-secured/services/user/importUserInfo');
curl_setopt($handle, CURLOPT_POST, true);
$xsig = "x-signature: ".getSignature(array_merge($raw_vars,$raw_login),$password);
curl_setopt($handle, CURLOPT_HTTPHEADER , array(
  "x-auth-service: irewind",
  "x-auth-user: nicolescu.mihai@gmail.com",
  "x-requested-with: XMLHttpRequest",
  $xsig,
    'Content-Type: multipart/form-data; boundary=' . $delimiter,
    'Content-Length: ' . strlen($data)));
curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
return curl_exec($handle);
}
/**
 * @param array $postFields The KVPs you want as post data
 * @param array $fileFields The objects of each file: name => array(type=>'mime/type',content=>'raw data|resource',filename=>'file.csv')
 */
function create_post($delimiter, $postFields, $fileFields = array()){
    // form field separator
    $eol = "\r\n";
    $data = '';
    // populate normal fields first (simpler)
    foreach ($postFields as $name => $content) {
        $data .= "--$delimiter" . $eol;
        $data .= 'Content-Disposition: form-data; name="' . $name . '"';
        $data .= $eol.$eol; // note: double endline
        $data .= $content;
        $data .= $eol;
    }
    // populate file fields
    foreach ($fileFields as $name => $file) {
        $data .= "--$delimiter" . $eol;
        // fallback on var name for filename
        if (!array_key_exists('filename', $file))
        {
            $file['filename'] = $name;
        }
        // "filename" attribute is not essential; server-side scripts may use it
        $data .= 'Content-Disposition: form-data; name="' . $name . '";' .
            ' filename="' . $file['filename'] . '"' . $eol;
        // this is, again, informative only; good practice to include though
        $data .= 'Content-Type: ' . $file['type'] . $eol;
        // this endline must be here to indicate end of headers
        $data .= $eol;
        // the file itself (note: there's no encoding of any kind)
        if (is_resource($file['content'])){
            // rewind pointer
            rewind($file['content']);
            // read all data from pointer
            while(!feof($file['content'])) {
                $data .= fgets($file['content']);
            }
            $data .= $eol;
        }else {
            // check if we are loading a file from full path
            if (strpos($file['content'], '@') === 0){
                $file_path = substr($file['content'], 1);
                $fh = fopen(realpath($file_path), 'rb');
                if ($fh) {
                    while (!feof($fh)) {
                        $data .= fgets($fh);
                    }
                    $data .= $eol;
                    fclose($fh);
                }
            }else {
                // use data as provided
                $data .= $file['content'] . $eol;
            }
        }
    }
    // last delimiter
    $data .= "--" . $delimiter . "--$eol";
    return $data;
}


function getSignature($params,$pass){
  ksort($params);
  $param_string="";
  foreach ($params as $key => $value) {
    if($value===true){$value="true";}elseif($value===false){$value="false";}
    str_replace(" ","+",$value);
    $param_string=$param_string.$key.$value;
  }
  return hash_hmac("sha256",$param_string,$pass);
}



?>
