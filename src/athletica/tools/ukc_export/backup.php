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

function sendToIr(){

  $settings = array("auth_service"=>"irewind",
  "auth_user"=>"nicolescu.mihai@gmail.com",
  "create_user"=>"true",
  "create_user_info"=>"true",
  "delete_user_info"=>"false",
  "duplicate_removal"=>"",
  "force_sync_user_info"=>"false",
  "force_sync_user_log"=>"false",
  "location_id"=>142,
  "max_time_diff"=>"",
  "update_user"=>"true",
  "update_user_info"=>"true");

  $headers[] = "X-Auth-Service: ".$settings['auth_service'];
  $headers[] = "X-Auth-User: ".$settings['auth_user'];
  $headers[] = "X-Requested-With: XMLHttpRequest";
  $headers[] = "X-Signature: ".getSignature($settings,"irw-user");
  $headers[] = "Content-Type: multipart/form-data;";

  print_r($headers);

  $post_data = array_merge($settings,array("user_file"=>"@".realpath("import_users.csv").";type=text/csv;","timing_file"=>"@".realpath("import_timing.csv").";type=text/csv;"));
  $uri="https://vps.nvncompany.ro";
  $path="/video-processor-secured/services/user/importUserInfo";

      $state_ch = curl_init();
      curl_setopt($state_ch, CURLOPT_URL,$uri.$path);
      curl_setopt($state_ch, CURLOPT_POST,true);
      curl_setopt($state_ch, CURLOPT_POSTFIELDS,$post_data);
      curl_setopt($state_ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($state_ch, CURLOPT_HTTPHEADER, $headers);
      $state_result = curl_exec ($state_ch);

      return($state_result);
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
