<?php

/********************
 *
 *	admin.php
 *	---------
 *	
 *******************/

require('./lib/cl_gui_menulist.lib.php');
require('./lib/cl_gui_page.lib.php');
include('./lib/cl_gui_select.lib.php');
include('./lib/cl_gui_dropdown.lib.php');

require('./lib/backup.lib.php');

if(AA_connectToDB() == FALSE)	{		// invalid DB connection
	return;
}
if(AA_checkMeetingID() == FALSE) {		// no meeting selected
	return;		// abort
}

//
// save changes for configuration
//
if($_POST['arg'] == 'save'){
	if(empty($_POST['path']) || empty($_POST['time'])){
		AA_printErrorMsg($strErrEmptyFields);
	}else{
		$conf = AA_backup_setConfiguration();
	}
}

$conf = AA_backup_getConfiguration();    

if($_GET['arg3'] == 'start'){
    $start = true;
    $conf = AA_backup_start();
    $conf = AA_backup_getConfiguration(); 
    ?>

    <script type="text/javascript">
    <!-- 
         activ = window.setTimeout("updatePage()", <?php echo $conf->time * 60 * 1000; ?>);
                    
        function updatePage()
        {   
            window.open("index.php?arg=admin_backup_automatic&arg3=start", "_top");
        }
        
        function timeout_stop()
        {  
            clearTimeout(activ);
           
        }

      //-->
    </script>              
    <?php           
}   
                

//
//	Display options for time measurement
//

$page = new GUI_Page('meeting_backup');
$page->startPage();
$page->printPageTitle($strBackupAutomatic);

$menu = new GUI_Menulist();
$menu->printMenu();

if(!empty($GLOBALS['ERROR'])){
	
	AA_printErrorMsg($GLOBALS['ERROR']);
	
}

echo $strBackupText;
?>  
<p/>

<form name="automatic" action="admin_backup_automatic.php" method="post">
<input type="hidden" name="arg" value="save">
<table class='dialog'>

<tr>
	<th class='dialog'><?php echo $strBackupLast ." " . $conf->last; ?></th>
</tr>
<tr>
	<td>
		<table class='admin'>
            <tr>
                <td><?php echo $strBackupTime1?></td>
                <td>
                    <input type="text" name="time" value="<?php echo $conf->time ?>" size="1"> 
                    <?php echo $strBackupTime2?>
                </td>
            </tr>
			<tr>
				<td><?php echo $strOmegaPath ?></td>
				<td>
					<input type="text" name="path" value="<?php echo $conf->path ?>" size="70">
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class='even'>
	<td>
		<input type="submit" name="save" value="<?php echo $strSave ?>">  
	</td>
</tr>    

</table>
</form>   
<?php
     if (!$start && $conf->path != "" && $conf->time > 0) {   
         ?> 
        </br>                                                
        </br>
        <form name="automatic" action="admin_backup_automatic.php" method="get">
        <input type="hidden" name="arg3" value="start">
        <input type="submit" name="start" value="<?php echo $strStart ?>">
        </br>
        </br>
        </form>     
	<?php
    }   elseif($start) {
        ?>
        </br>
        </br>
        <form name="automatic" action="admin_backup_automatic.php" method="post">
        <input type="submit" name="stop" value="<?php echo $strStopp ?>">
        </br>
        </br>
        </form>  
    <?php
    }
    echo $strBackupText2;
$page->endPage();
?>
