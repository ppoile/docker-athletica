<?php

/********************
 *
 *	admin_registration.php
 *	---------
 *	get registrations from the online slv system
 *
 *******************/


require('./lib/cl_gui_menulist.lib.php');
require('./lib/cl_gui_page.lib.php');
 
require('./lib/common.lib.php');
                    
require('./lib/cl_xml_data.lib.php');         
require('./lib/cl_http_data.lib.php');         
	   
if(AA_connectToDB() == FALSE)	{		// invalid DB connection
	return;
}

if(AA_checkMeetingID() == FALSE){		// no meeting selected
	return;		// abort
}

if (!empty($_POST['mode'])){
	$mode =  $_POST['mode'];
}
     
//
//	Display enrolement list
//

$page = new GUI_Page('admin_registration');
$page->startPage();
/*$page->printPageTitle($strBaseUpdate);*/
$page->printPageTitle($strMeetingSync);

$menu = new GUI_Menulist();
$menu->addButton($cfgURLDocumentation . 'help/administration/base.html', $strHelp, '_blank');
$menu->printMenu();
?>
<p/>

<?php
           
$http = new HTTP_data();
$webserverDomain = $cfgSLVhost; // domain of swiss-athletics webserver

// handle arguments
$login = false;
$mcontrol = "";
$mname = "";
$mdate = "";
$slvsid = "";
$list = false;
$reg = false;
$noEntry = false;

if($_POST['arg'] == "login"){
	
	// reto 31.8.2017: as far as I understand, athletica trys to connect for the StammData download in order to check the password, but then get the meeting-list elsewhere...
	// --> removed that stupid part
	
		// get meetinglist
		
		//$result = $http->send_get('slv.exigo.ch', '/downloads/verbandstagung_201104.ppt' , 'file', 'test.ppt', true);
		$post = ""; //sid=".$_POST['slvsid'];
		
		// was actually POST, but shall now be GET ...?
		$result = $http->send_get_TLS($webserverDomain, $cfgSLVuriMeetingList, 'ini', $_POST['clubnr'], $_POST['pass']);
		if($result===false){ // is really of type false
			echo('no result');
			AA_printErrorMsg($strErrLogin);
			$login = false; // should show login form again
		}elseif(!$result){ // can be an emtpy string --> no meetings for this login
			$noEntry = true;
		}elseif($result == 'unauthorized'){
			echo "<p>$strLoginFalse</p>";
		}elseif ($result != 'unauthorized'){
			
			$_POST['arg'] = 'list';
			
			$login = true;
			
			$mcontrol = explode(":",$result['Control']);
			$mname = explode(":",$result['Name']);
			$mdate = explode(":",$result['Startdate']);
			
			$list = true; // make it show the list instead of the login page
			
		}
		
	}elseif($_POST['arg'] == "reg"){
		// get xml for registrations
		
		$post = "&meetingid=".$_POST['control'];
		$result = $http->send_post_TLS($webserverDomain, $cfgSLVuriMeetingData, $post, $_POST['clubnr'], $_POST['pass'], 'file', 'reg.xml');      
     
		if(!$result){
			AA_printErrorMsg($strErrLogin);
		}elseif ($result == 'unauthorized'){
			AA_printErrorMsg($strLoginFalse);
		}else{
			$login = true;
			$reg = true;
			$xml = new XML_data();
			$arr = $xml->load_xml($result, 'reg', $_POST['mode']);
			
			// save eventnr
			mysql_query("update meeting set xControl = ".$_POST['control']." where xMeeting = ".$_COOKIE['meeting_id']);
			if(mysql_errno() > 0){
				AA_printErrorMsg(mysql_errno().": ".mysql_error());
			}
            
            $save = false;
            if (!empty($arr)){   
            
                    if (count($arr) == 1) {
                         $val = $strEinz;
                    }
                    else {
                           $val = $strMehrz;
                    }
                    $mess = str_replace('%ARTIKEL%', $val, $strAthleteTeam);      
                
                
               foreach ($arr as $key => $val) { 
                   
                    if (count($key) == 1) {
                         continue;
                    }
                     
                    $save = true;                  
                    ?> 
                    <br><br><strong><?php echo $mess; ?></strong> 
                    
                    <form method="post" action="admin_registration.php" target="_self"> 
                    <table class='dialog'>    
                   <?php 
                   
                    $sql = "SELECT Name, Vorname FROM athlet WHERE xAthlet = " .$key;
                    $res = mysql_query($sql);
                    if(mysql_errno() > 0){
                        AA_printErrorMsg(mysql_errno().": ".mysql_error());
                    }                            
                    $row = mysql_fetch_row($res);   
                    ?>          
                    <tr class="odd">
                            <td><?php echo $row[1] ." " .$row[0]; ?> 
                            </td>                                        
                            <td>
                                <select class="" name="team_<?php echo $key; ?>" size="1" id="team_<?php echo $key; ?>"> 
                                   <?php                                     
                                   foreach ($val as $k => $v) {
                                   ?> 
                                    <option value="<?php echo $arr[$key][$k]; ?>"><?php echo $arr[$key][$k]; ?></option>
                                   
                                    <?php 
                                    }
                                    ?> 
                                </select> 
                            </td>
                    </tr>   
                    <input type="hidden" value="<?php echo $key; ?>" name="athlet[]">   
                    <?php 
               }  
               if ($save){                
                   ?>
                    
                    <input type="hidden" value="save" name="arg"> 
                    
                    <tr>
                    <td></td>
                    <td><button type="submit"><?php echo $strSave; ?></button>     
                    </td></tr>

                    </table>   
                   </form>   
                   <?php 
               }
            }
           
            echo "<p>$strBaseRegOk</p>";   
            
		}
	}
   
       
//}

//
// show meeting list
//
if($list){
	
?>

<table class='dialog'>
<form method="post" action="admin_registration.php" target="_self">
<input type="hidden" value="reg" name="arg">
<input type="hidden" value="<?php echo $mode; ?>" name="mode">
<input type="hidden" value="<?php echo $_POST['clubnr']; ?>" name="clubnr">
<input type="hidden" value="<?php echo $_POST['pass']; ?>" name="pass">
<tr>
	<th><?php echo $strBaseMeeting; ?></th>
</tr>
<tr>
	<td>
	<select name="control" size="10">
	
	<?php
	$i = 0;
	foreach($mcontrol as $control){
		echo "<option value='$control'>".utf8_decode($mname[$i]).", ".$mdate[$i]."</option>"; // TODO: this might fail when date is not available (empty array)
		$i++;
	}
	?>
	
	</select>
	</td>
</tr>
<tr>
	<td>
	<br/>
	<?php echo$strBaseMeetingAct; ?><br/>
	<b><?php echo $_SESSION['meeting_infos']['Name']; ?></b><br/><br/>
	
	<input type="submit" value="<?php echo $strNext ?>">
	</td>
</tr>
</form>
</table>

<?php
	
} elseif ($noEntry){
	echo $strErrNoCompetition;
}

//
// show succes on reg xml
//
  // athlet with duplicate teams --> user has to choose and this is to save 
 if($_POST['arg'] == "save"){ 
        
     
             foreach ($_POST['athlet'] as $key => $val){
                    
                     $team_p = "team_" . $val; 
                     $sql = "Update anmeldung SET xTEam = " . $_POST[$team_p]  . " WHERE xAthlet = " .$val;
                     $res = mysql_query($sql);
                     if(mysql_errno() > 0){
                            AA_printErrorMsg(mysql_errno().": ".mysql_error());
                     }
             }
            
              //show succes on reg xml                                                     
                 echo "<p>$strBaseRegOk</p>";  
        
    }
elseif(!$login){
	
	// show login form

?>
<form action='admin_registration.php' name='base' method='post' target='_self'>  
<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tbody><tr>
			<td style="vertical-align: top;" width="260">
				<table class="dialog" width="260">
					<tbody><tr>

						<th><?php echo $strConfiguration; ?></th>
					</tr>
					<tr>
						<td>
						  <p><?php echo$strEffortsUpdateInfo4; ?></p>
						  <p>
							<label>
							  <input type="radio" name="mode" value="overwrite" id="mode_0" checked="checked" />
							  <?php echo $strOverwrite; ?></label>
							<br />
							<label>
							  <input type="radio" name="mode" value="skip" id="mode_1" />
							  <?php echo $strLeaveBehind ;?></label>
							<br />
						  </p></td>
					</tr>
				</tbody>
		</table>
		<br />
<table class='dialog'>
<tr>
	<th><?php echo $strLoginSlv; ?></th>
</tr>
<tr>
	<td>
		<table class='admin'>
		
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
} // end if login

$page->endPage();
?>
