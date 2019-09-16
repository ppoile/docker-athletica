<?php

/********************
 *
 *	admin_base.php
 *	---------
 *	login form for getting the base data from the slv web system
 *
 *******************/

require('./lib/cl_gui_menulist.lib.php');
require('./lib/cl_gui_page.lib.php');
require('./lib/cl_xml_pageconstruct.lib.php');
require('./lib/rankinglist_team.lib.php');
require('./lib/rankinglist_lmm.lib.php');

require('./lib/common.lib.php');

require('./lib/cl_xml_data.lib.php');
require('./lib/cl_ftp_data.lib.php');
require('./lib/cl_http_data.lib.php');

if(AA_connectToDB() == FALSE)	{		// invalid DB connection
	return;
}

if(AA_checkMeetingID() == FALSE){		// no meeting selected
	return;		// abort
}

//
//	Display enrolement list
//

$page = new GUI_Page('admin_base');
$page->startPage();
$page->printPageTitle($strResultUpload);

$menu = new GUI_Menulist();
$menu->addButton($cfgURLDocumentation . 'help/administration/results.html', $strHelp, '_blank');
$menu->printMenu();


//$login = false;

if($_POST['arg'] == "login"){
	
	$http = new HTTP_data();
	
	set_time_limit(300);
	
	$xml = new XML_data();
	$ftp = new FTP_data();
	
	//
	// set file names and generade result xml
	//
	$res = mysql_query("select xControl from meeting where xMeeting = ".$_COOKIE['meeting_id']);
	$row = mysql_fetch_Array($res);
	$eventnr = $row[0];
	$local = "/tmp/results.xml.gz";
	$remote = date("Ymd")."_".$eventnr.".gz";
	
	$nbr_effort = $xml->gen_result_xml($local);          
	
	// upload result file
	if($nbr_effort>0){ //upload only if file contains at least one results
		$handle = fopen($local,'r');
		$data = fread($handle, 200000000); // max 200 MB
		fclose($handle);
		
		// must be 'multipart' data: has always a beginning and an end, which must be defined in the header and is defined here as 'AthleticA'
		$post = "--AthleticA\r\n";
		$post .= "content-disposition: form-data; name=\"file\"; filename=\"$remote\" \r\n"; // Probably not existing in http 1.1 !!! (RFC 7231, RFC 6266, RFC 2183, RFC 1806)
		$post .= "content-type: application/x-gzip \r\n\r\n";
		$post .= "$data\r\n"; 
		$post .= "--AthleticA--";
		
		$result = $http->send_post_TLS($cfgSLVhost, $cfgSLVuriResults, $post, $_POST['clubnr'], $_POST['pass'], 'autojson', "", true);
		if ($result=='unauthorized'){
			echo($strLoginFalse);
			$success = false;
		} elseif (preg_match('#.*import complete.*#i',$result)){
			echo($result);
			$success = true;
		} else {
			echo($result);
			$success = false;
		}
		
	} else {
		$success=true;
	} 
     
      
	if($success){
		// output message and set round status to results_sent
		
		foreach($GLOBALS['rounds'] as $xRunde){
			mysql_query("UPDATE runde SET StatusUpload = 1 WHERE xRunde = $xRunde");
			if(mysql_errno() > 0 ){
				AA_printErrorMsg(mysql_errno().": ".mysql_error());
			}
		}
		if($nbr_effort>0){
			echo "<p><b>$strResultsUploaded</b></p>";
			echo $strNumberEfforts .": " .$nbr_effort;
		} else {
			echo "<p><b>$strResultsUploadedNoResults</b></p>";
		}
		
		
		
	}else{
		
		echo "<p>$strErrResultUpload</p>";
		
	}
}else{
	$cControl = AA_checkControl();
	if($cControl == 0){
		echo "<p>$strErrNoControl</p>";
		return;
	}elseif($cControl == 2){
		?>
		<p><?php echo $strErrNoControl3; ?></p><br/>
		<!--<img src="img/nosync_<?php echo $_COOKIE['language']; ?>.gif" alt="" style="border: solid 1px #000000;"/>  -->
		<?php
		return;
	}
    
    // check if there are results in progress 
    $query = "SELECT 
                    xRunde                         
              FROM
                    runde as ru 
                    LEFT JOIN wettkampf as w ON (w.xWettkampf = ru.xWettkampf)                             
              WHERE ru.Status = ".$cfgRoundStatus['results_in_progress']."
                    AND ru.StatusUpload = 0 AND w.xMeeting = " . $_COOKIE['meeting_id'];     
               
              $res_results = mysql_query($query);
              if(mysql_errno() > 0){
                    echo mysql_Error();
              }else{
                    if(mysql_num_rows($res_results) > 0){
                        ?>
                        <p class="st_res_work" ><?php echo $strErrResultsInProgress; ?></p><br/> 
                       <?php  
                    return; 
                    } 
              }  
	
?>
<table class='dialog'>
<tr>
	<th><?php echo $strLoginSlv; ?></th>
</tr>
<tr>
	<td>
		<table class='admin'>
		<form action='admin_results.php' name='base' method='post' target='_self' >
		<input type="hidden" name="arg" value="login">
		<tr>
			<td>
				<?php echo $strSANr ?>
			</td>
			<td>
				<input type="text" name="clubnr" value="">
			</td>
		</tr>
		<tr>
			<td>
				<?php echo $strPassword ?>
			</td>
			<td>
				<input type="password" name="pass" value="">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" name="submit" value="<?php echo $strLogin ?>">
			</td>
		</tr>
		</form>	
		</table>
	</td>
</tr>
</table>
<?php

}
$page->endPage();
?>
