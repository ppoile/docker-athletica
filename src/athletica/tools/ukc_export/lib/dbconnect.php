<?php
$hostname = $cfgDBhost;
$username = $cfgDBuser;
$password = $cfgDBpass;
$database = $cfgDBname;


$conn = mysql_connect("$hostname","$username","$password") or die(mysql_error());
mysql_select_db("$database", $conn) or die(mysql_error());

if(!$conn)
{
  exit("Verbindungsfehler: ".mysqli_connect_error());
}




?>
