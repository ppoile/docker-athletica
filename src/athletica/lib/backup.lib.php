<?php

/**********
 *
 *	timing handling functions
 *	-------------------------
 *	
 */

if (defined('AA_BACKUP_LIB_INCLUDED'))
{
	return;
}
define('AA_BACKUP_LIB_INCLUDED', 1);

require("./lib/common.lib.php");
require("./lib/cl_backup.lib.php");
require('./config.inc.php');  


function AA_backup_getConfiguration(){
	
	$obj = null;
	$obj = new backup();

	
	return $obj;
}


function AA_backup_setConfiguration(){
    
    $obj = null;
    $obj = new backup();
    $obj->set_configuration();
            
    return $obj;
}

function AA_backup_start(){
	
	$obj = null;
	$obj = new backup();
	$obj->do_backup();
			
	return $obj;
}

?>
