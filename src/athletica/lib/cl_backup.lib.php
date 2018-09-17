<?php

if (!defined('AA_CL_BACKUP_LIB_INCLUDED'))
{
	define('AA_CL_BACKUP_LIB_INCLUDED', 1);
}else{
	return;
}

/************************************
 *
 * automatic Backup
 *
 * Handles sql export for automatic backup
 *
/************************************/


class backup{
	
	var $connection;
    var $path;
	var $time;
    var $last;
	
	var $si; // silent, no errors
	
	function backup($noerror = false){
		$this->si = $noerror;
		$this->get_configuration();
	}
	

	function set_configuration(){
		global $strBackupPathWriteFailed, $strBackupNoPath;
		
		// test local path for writing
		$fp = fopen($_POST['path']."/test.txt",'w');
		if(!$fp){
			$GLOBALS['ERROR'] = $strBackupPathWriteFailed;
            return;
		}else{
			fclose($fp);
			unlink($_POST['path']."/test.txt");
		}
		
		mysql_query("LOCK TABLES backup WRITE");
	
		$res = mysql_query("SELECT * FROM backup");
		if(mysql_errno() > 0){
			AA_printErrorMsg(mysql_errno().": ".mysql_error());
		}else{
			if(!get_magic_quotes_gpc()){
				$_POST['path'] = addslashes($_POST['path']);
			}
			if(mysql_num_rows($res) == 0){
				mysql_query("
					INSERT INTO backup
                    SET    Backup_Pfad = '".$_POST['path']."'
					, Backup_Intervall = '".$_POST['time']."'
					");
                    
			}else{
				mysql_query("
					UPDATE backup
                    SET    Backup_Pfad = '".$_POST['path']."'
					, Backup_Intervall = '".$_POST['time']."'
					");
			}
		}
		
		mysql_query("UNLOCK TABLES");
	}

	
	/**
	* outputs the category table
	* "Category"; "AbrevCat"
	*/
	function do_backup(){
        global $cfgDBname;
        global $cfgApplicationName;
        global $cfgApplicationVersion;
        
        if(AA_connectToDB() == FALSE)    {        // invalid DB connection
            return;
        }
		
        set_time_limit(3600); // the script will break if this is not set
        $idstring = "# $cfgApplicationName $cfgApplicationVersion\n";
        
        $result = mysql_list_tables($cfgDBname);
        $filename = 'athletica_'. date('Y-m-d H.i') .'.sql';
        
        if(mysql_errno() > 0)
        {
            AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
        }
        else
        {
            if(mysql_num_rows($result) > 0)    // any table
            {
                $tmp = "$idstring";
                $tmp .= "# Database Dump:\n";
                $tmp .= "# Date/time: " . date("d.M.Y, H:i:s") . "\n";
                $tmp .= "# ----------------------------------------------------------\n";
            }
        while ($row = mysql_fetch_row($result))
            {
                //ignore base-tables, sys-tables and other tables with non user customizing possibilities
                if (substr($row[0],0,5)== "base_" ||
                    substr($row[0],0,4)== "sys_" ||
                    $row[0] == "kategorie_svm" ||
                    $row[0] == "faq" ||
                    $row[0] == "land") 
                {
                    continue;
                }

                $res = mysql_query("SELECT * FROM $row[0]");
                
                // truncate in each case!
                $tmp .= "\n#\n";
                $tmp .= "# Table '$row[0]'\n";
                $tmp .= "#\n\n";
                $tmp .= "TRUNCATE TABLE $row[0];\n";
                
                
                $fieldArray = array();
                if(mysql_num_rows($res) > 0)    // any content
                {  
                    $sqlInsert = "INSERT INTO $row[0] \n";
                    
                    $fields = mysql_query("SHOW COLUMNS FROM $row[0]");
                    $tmpf = "(";
                    while($f = mysql_fetch_assoc($fields)){
                        $tmpf .= "`".$f['Field']."`, ";
                        $fieldArray[] = $f;
                    }
                    $sqlInsert .= substr($tmpf,0,-2).") VALUES\n";
                    $tmp .= $sqlInsert;
                    
                }

                unset($values);
                $n = 0;
                while($tabrow = mysql_fetch_assoc($res))
                {
                    if(!empty($values) && !$skip_nextline) {    // print previous row
                        $tmp .= "$values),\n";
                    }
                    
                    // dds
                    // skip row if all vales are empty
                    $allEmpty = true;
                    foreach($fieldArray as $f){
                        if($tabrow[$f['Field']]!=''){
                            $allEmpty = false;
                            break;
                        }
                    }
                    
                    if(!$allEmpty){
                        $n++;
                        
                        $values = "(";
                        $cma = "";
                        
                        foreach($fieldArray as $f){
                            if(substr($f['Type'],0,3) == 'int') {    
                                $values = $values . $cma . $tabrow[$f['Field']];
                            } else {
                                $values = $values . $cma . "'" . addslashes($tabrow[$f['Field']]) . "'";
                            }
                            $cma = ", ";
                        }
                        
                        if ($n==1000){
                            $n=0;
                            $tmp .= "$values);#*\n $sqlInsert";
                            $skip_nextline = true;
                        } else {
                            $skip_nextline = false;
                        }
                    }
                    
                }        // End while every table row

                if(mysql_num_rows($res) > 0)    // any content
                {
                    $tmp .= "$values);#*\n";        // print last row
                                    // the '#*' is needed for finding the end of the insert statement
                                    // (if there are semicolons in a field value)
                }
                
                mysql_free_result($res);

                $tmp .= "\n# ----------------------------------------------------------\n";
            }        // End while every table

            if(mysql_num_rows($result) > 0) {    // any table
                $tmp .= "\n#*ENDLINE"; // termination for validating
                            // has to be on the last 9 characters
                flush();
            }

            mysql_free_result($result);
        }
			
	        
        $this->send_file($tmp, $filename);
		
        $time = date('Y-m-d H:i:s');
        mysql_query("UPDATE backup
                    SET Backup_Zuletzt = '".$time."'
                    ");
		

	}
	
	
	function get_configuration(){
        global $strBackupNoConf;
        global $cfgDBdateFormat;
		global $cfgDBtimeFormat;
		
		mysql_query("LOCK TABLES backup READ");
		
		$res = mysql_query("SELECT *
                            , DATE_FORMAT(Backup_Zuletzt, '".$cfgDBdateFormat."') AS backup_last_date
                            , DATE_FORMAT(Backup_Zuletzt, '".$cfgDBtimeFormat."') AS backup_last_time
                            FROM backup");
		if(mysql_errno() > 0){
			AA_printErrorMsg(mysql_errno().": ".mysql_error());
		}else{
			
			if(mysql_num_rows($res) == 0 && $_POST['arg'] != 'change'){
				$this->connection = "noconf";
				//if($this->si==false){ AA_printErrorMsg($strBackupNoConf); }
			}else{
			
				$row = mysql_fetch_assoc($res);
                
				$this->connection = $row['Backup_Pfad'];
                $this->path = $row['Backup_Pfad'];
                $this->time = $row['Backup_Intervall'];
				$this->last = $row['backup_last_date'] . " - " . $row['backup_last_time'];
			}
		}
		
		mysql_query("UNLOCK TABLES");
	}
	
	
	function send_file($content, $filename){
		global $strErrFileOpenFailed;

		// copy file on a local disk or network share
		
		$fp = @fopen($this->path."/".$filename, 'w');
		if(!$fp){
			//AA_printErrorMsg($strErrFileOpenFailed.": ".$this->path."/".$filename);
			return false;
		}else{
			fputs($fp, $content);
			
			fclose($fp);
		}
	}
	
		
	function is_configured(){
		if($this->connection == "noconf"){
			return false;
		}else{
			return true;
		}
	}
}

?>