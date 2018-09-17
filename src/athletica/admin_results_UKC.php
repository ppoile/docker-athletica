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

if (isset($_GET['ukc_meeting'])) {
     $ukc_meeting = $_GET['ukc_meeting'];   
}
else if  (isset($_POST['ukc_meeting'])) {
     $ukc_meeting = $_POST['ukc_meeting'];   
}

$meeting_nr = '';
if (isset($_GET['meeting_nr'])){
   $meeting_nr= $_GET['meeting_nr'];
}
else if (isset($_POST['meeting_nr'])){
   $meeting_nr= $_POST['meeting_nr'];
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


	$cControl = AA_checkControl_UKC($meeting_nr);      
	if($cControl == 0){
		echo "<p>$strErrNoControl4</p>";
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
    set_time_limit(300);
    
    $xml = new XML_data();
    $ftp = new FTP_data();
    
    //
    // set file names and generade result xml
    //
    if (empty($meeting_nr)){    
        $res = mysql_query("select Nummer from meeting where xMeeting = ".$_COOKIE['meeting_id']);
        $row = mysql_fetch_Array($res);             
        $eventnr = $row[0];
    } 
    else {
        $eventnr = $meeting_nr;   
    } 
    
    $local = dirname($_SERVER['SCRIPT_FILENAME'])."/tmp/results_ukc.xml.gz";
    $remote = date("Ymd")."_".$eventnr.".gz";          
    
    if ($ukc_meeting == 'n'){
         $nbr_effort_ukc = $xml->gen_result_xml_UKC_CM($local, $meeting_nr);
    }
    else {
          $nbr_effort = $xml->gen_result_xml_UKC($local);             
    }
   

    // upload result file
    if($nbr_effort>0 || $nbr_effort_ukc>0){ //upload only if file contains at least one results
        $ftp->open_connection($cfgSLVhostUKC, $cfgSLVuser, $cfgSLVpass);
        $success = $ftp->put_file($local, $remote);
        $ftp->close_connection();
    } else {
        $success=true;
    }     
      
    if($success){
        // output message and set round status to results_sent
        if ($nbr_effort>0){
            foreach($GLOBALS['rounds'] as $xRunde){
                mysql_query("UPDATE runde SET StatusUpload = 1 WHERE xRunde = $xRunde");
                if(mysql_errno() > 0 ){
                    AA_printErrorMsg(mysql_errno().": ".mysql_error());
                }
            }
        }
        else {
             foreach($GLOBALS['rounds'] as $xRunde){
                mysql_query("UPDATE runde SET StatusUploadUKC = 1 WHERE xRunde = $xRunde");
                if(mysql_errno() > 0 ){
                    AA_printErrorMsg(mysql_errno().": ".mysql_error());
                }
            }
        }  
        if ($ukc_meeting == 'y'){  
            if($nbr_effort>0){
                echo "<p><b>$strResultsUploaded</b></p>";
                echo $strNumberEfforts .": " .$nbr_effort;
            } 
            else {
                echo "<p><b>$strResultsUploadedNoResults</b></p>";
            }
        }
        else {
               if($nbr_effort_ukc>0){
                echo "<p><b>$strResultsUploaded</b></p>";
                echo $strNumberEfforts .": " .$nbr_effort_ukc;
            } 
            else {
                echo "<p><b>$strResultsUploadedNoResults</b></p>";
            }
        }
        
        
        
    }else{
        
        echo "<p>$strErrResultUpload</p>";
        
    } 
	



$page->endPage();
?>
