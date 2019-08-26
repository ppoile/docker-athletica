<?php

/********************
 *
 *	admin_importBLV.php
 *	----------------------
 *	diese version ist nur für den Swiss-athletic sprint final gedacht, wo man die Region und die Startnummer noch importieren muss!!!
 *
 *************************/

 
require('./lib/common.lib.php');
require('./lib/cl_performance.lib.php');
 
if(AA_connectToDB() == FALSE)	{		// invalid DB connection
	return;
}

if(AA_checkMeetingID() == FALSE){		// no meeting selected
	return;		// abort
}

// **********
// begin config
// **********


$delimeter=";";
 
$millegruyere = array(
"name" => 1,
"forename" => 2,
"birthdate" => 6,
"club" => 9,
"sex" => 7,
"license" => 0,
"cat" => 10,
"perf" => 11,
"region" => 12,
"number" => 13,
"len" => 13,
"man" => "Herr",
"wom" => "Frau",
"Frau" => "w",
"Herr" => "m"
);
	
$SAsprint = array(
"name" => 1,
"forename" => 2,
"birthdate" => 6,
"club" => 9,
"sex" => 7,
"license" => 0,
"cat" => 10,
"perf" => 11,
"region" => 12,
"number" => 13,
"len" => 13,
"man" => "Herr",
"wom" => "Frau",
"Frau" => "w",
"Herr" => "m"
);

$cat = array(
"M15" => 4,
"M14" => 4,
"M13" => 5,
"M12" => 5,
"M11" => 6,
"M10" => 6,
"M09" => 16,
"M08" => 16,
"M07" => 16,
"W15" => 10,
"W14" => 10,
"W13" => 11,
"W12" => 11,
"W11" => 12,
"W10" => 12,
"W09" => 17,
"W08" => 17,
"W07" => 17
);

$disc = array(
"M15" => 41,
"M14" => 41,
"M13" => 40,
"M12" => 40,
"M11" => 40,
"M10" => 40,
"M09" => 38,
"M08" => 38,
"M07" => 38,
"W15" => 41,
"W14" => 41,
"W13" => 40,
"W12" => 40,
"W11" => 40,
"W10" => 40,
"W09" => 38,
"W08" => 38,
"W07" => 38,
"1000" => 49
);

// base_performance uses different disipline_codes than the xDisziplin are; match from xDisziplin to Code
$disc_code = array(
41 => 35,
40 => 30,
38 => 10,
49 => 100	
);

// **********
// end config
// **********

// first get the year of the competition, for the calculation of the ages of athletes...
$res = mysql_query("select DatumVon from meeting where xMeeting='".$_COOKIE['meeting_id']."'") or die(mysql_error());
$row = mysql_fetch_row($res);
$arr = explode("-",$row[0]);
$meetingYear = $arr[0];

if (!empty($_POST['project']) and !empty($_FILES['csvfile']['name'])) {

	$wettk = array();
	$cnt_athlete = 0;
	$cnt_anmeldung = 0;
	$cnt_start = 0;
	$cnt_license = 0;
	$cnt_club = 0;
	$cnt_perf_update = 0;
	
	// choose correct config-array, defined above
	if ($_POST['project']=='Mille Gruyere') {
		$conf = $millegruyere;
		$project = "MilleGruyere";
	} else {
		$conf = $SAsprint;
		$project = "SASprint";
	}
	
	// test if wettkampf exists and create if not
	foreach ($cat as $catname => $c) {
		// $z contains the id of the discipline for this category
		if ($project=="MilleGruyere") {
			$z=$disc["1000"]; 
		}else{
			$z=$disc[$catname];
		}        
                
        // calculate the birthyear of this category, as it is used in the new way of Info-Definition.
        $catBirthyear = $meetingYear - (int)substr($catname,1);
        
        // category and info together should be unique
        $res = mysql_query("select xWettkampf from wettkampf where xKategorie='".$c."' and xDisziplin='".$z."' and Info in ('".$catname."', '".$catBirthyear."') and xMeeting='".$_COOKIE['meeting_id']."'") or die (mysql_error());
        if (mysql_num_rows($res)>1) {
			// only one wettkampf per categorie and discipline is allowed
			exit("In der Kategorie ".$catname." ist die Disziplin mehrmals erfasst. Der Import wird abgebrochen. ");
		} elseif (mysql_num_rows($res)==0) {
			// create wettkampf
			// old:
            //mysql_query("insert into wettkampf(xDisziplin, xMeeting, xKategorie, Info) values('".$z."','".$_COOKIE['meeting_id']."','".$c."','".$catname."')") or die(mysql_error());
            // new: uses catBirthYear as info
            mysql_query("insert into wettkampf(xDisziplin, xMeeting, xKategorie, Info) values('".$z."','".$_COOKIE['meeting_id']."','".$c."','".$catBirthyear."')") or die(mysql_error());
            $wettk[$catname] = mysql_insert_id();
		} else {
			// wettkampf exists --> get id
			$row = mysql_fetch_array($res);
			$wettk[$catname] = $row[0];
		}
	}

	// open file only read
	$handle = fopen($_FILES['csvfile']['tmp_name'], 'r') or exit("Datei konnte nicht geöffnet werden.");
	
	// process each row
    $i = 0;
	while (($data = fgetcsv($handle, 0, $delimeter)) !== FALSE) {
	    if($i>0) {
		    // save the data to named variables
		    $name = mysql_real_escape_string($data[$conf["name"]]);
		    $forename = mysql_real_escape_string($data[$conf["forename"]]);
		    $birthdate = $data[$conf["birthdate"]];
		    $sex = $conf[$data[$conf["sex"]]];
		    $club = mysql_real_escape_string($data[$conf["club"]]);
		    $license = str_replace(" ","",$data[$conf["license"]]); // may have spaces, which must be deleted for casting to int
		    $cat = $data[$conf["cat"]]; // unused --> cat beeing calculated automatically with age and sex
		    $nationality = "";
		    $club_id = 0;
		    $perf_import = $data[$conf["perf"]]; 
		    $region = mysql_real_escape_string($data[$conf["region"]]);
		    $comp_number = $data[$conf["number"]];
		    
		    // format birthdate and get year of birth
		    // input: dd.mm.yyyy, output: yyyy-mm-dd
		    $arr = explode(".",$birthdate);
		    $year = $arr[2];
		    $day = str_pad($arr[0],2,"0",STR_PAD_LEFT); // leading 0 
		    $month = str_pad($arr[1],2,"0",STR_PAD_LEFT); // leading 0 
		    $birthdate = $year."-".$month."-".$day; //format for MySQL
		    
		    // check if data valid (forename, name, birthdate and sex not empty)
		    if (empty($name) or empty($forename) or empty($birthdate) or empty($sex)) {
			    echo("Diesem Datensatz fehlen Angaben (Name, Vorname, Geschlecht und/oder Geburtsdatum.");
			    var_dump($data);
			    echo("<br />");
			    continue;
		    }
		    // data is valid => proceed
		    
		    // TODO: seems like verein contains all entries of base_account --> check
		    
		    // if license exists in database and belongs to correct athlete (full name is the same): get license and the correct club
	    
		    $res = mysql_query("select 
		    ba.license_cat, 
		    ba.lastname,  
		    ba.firstname, 
		    ba.sex, 
		    ba.nationality, 
		    v.xVerein,
		    ba.birth_date, 
		    ba.id_athlete
		    from base_athlete as ba, 
		    verein as v where 
		    ba.account_code=v.xCode and 
		    ba.license='$license' and 
		    ba.lastname='$name' and 
		    ba.firstname='$forename'") or die(mysql_error());
		    if (mysql_num_rows($res)>0) {
			    // license exists in database --> club is defined too and incorrect birthdate in import is overridden by data from base
			    $row = mysql_fetch_row($res);
			    $nationality = $row[4];
			    $club_id = $row[5];
			    $birthdate = $row[6];
			    $cnt_license = $cnt_license + 1;
		    } else {
			    // no valid license
			    $license = "";
			    
			    // without license we dont know the club id
			    // check if club exists already
			    $res = mysql_query("select xVerein from verein where Name='$club'") or die(mysql_error());
			    if (mysql_num_rows($res)>0) {
				    $row = mysql_fetch_row($res);
				    $club_id = $row[0];
			    } else {
				    // add club
				    $res = mysql_query("insert into verein(Name, Sortierwert) values ('$club','$club')") or die ("Verein '$club' konnte nicht hinzugefügt werden.");
				    $club_id = mysql_insert_id();
				    $cnt_club = $cnt_club + 1;
			    }
		    }
			    
		    // get the region
		    $res = mysql_query("select xRegion from region where Name='$region' ") or die(mysql_error());
		    if (mysql_num_rows($res)>0) {
			    $row = mysql_fetch_row($res);
			    $xRegion = $row[0];
		    } else {
			    $xRegion = 0;
		    }
		    
		    // if not athlet is in table 'athlet', add record
		    $sql=" select
		    xAthlet
		    from athlet
		    where xVerein='$club_id' and
		    Name='$name' and
		    Vorname='$forename' and 
		    Geburtstag='$birthdate'
		    ";  // returns max one entry (must be unique according to table-definition)
	    
		    if ($license=="") {
			    $license_type = 3;
		    } else {
			    $license_type = 1;
		    } 
		    $res = mysql_query($sql) or die(mysql_error());
		    if (mysql_num_rows($res)>0) {
			    $row = mysql_fetch_row($res);
			    $xAthlet = $row[0];
			    if ($license<>"") { // count was already raised, so reset it!
				    $cnt_license = $cnt_license - 1;
			    }
		    } else {
			    // add athlet			
			    $sql = "insert into athlet(Name, Vorname, Jahrgang, xVerein, Lizenznummer, Geschlecht, Land, Geburtstag, Lizenztyp, xRegion) 
			    values('$name', '$forename', '$year', '$club_id', '$license', '$sex', '$nationality', '$birthdate', '$license_type', $xRegion)";
			    $res = mysql_query($sql) or die("Athlet $name $forename konnte nicht eingefügt werden.".mysql_error());
			    $xAthlet = mysql_insert_id();
			    $cnt_athlete = $cnt_athlete + 1;
		    }
		    
		    // Category
		    // Inscription: Official category like U14W, U16W...
		    
		    // if not athlet has already inscription, make inscription for meeting
		    // calculate "Category-Name" by finding out the age
		    $age_i = $meetingYear-$year;
		    
		    // if younger than 7 years -> Category is M07/W07
		    if ($age_i<7) {
			    $age_i = 7;
		    }
		    $age = str_pad($age_i,2,"0",STR_PAD_LEFT); // leading 0 if only one digit
		    $cat_str = strtoupper($sex).$age;
		    
		    /*
		    // get Categorie-ID for Wettkampf
		    $res = mysql_query("select xKategorie from kategorie where Kurzname='$cat_str'") or die (mysql_error());
		    if (mysql_num_rows($res)==0) {
			    echo("Die Wettkampfkategorie $cat_str von $name $forename existiert nicht. Athlet/in wird übersprungen. <br />");
			    continue;
		    } else {
			    $row = mysql_fetch_row($res);
			    $xKategorie = $row[0];
		    }*/
		    
		    // get Category-ID for Inscription
		    // only Official Category => xKategorie<20, order by: first row is correct category
		    $res = mysql_query("select xKategorie from kategorie where xKategorie<20 and Geschlecht='$sex' and Alterslimite>=$age_i order by Alterslimite ASC") or die(mysql_error());
		    $row = mysql_fetch_row($res);
		    $xCat = $row[0];
		    
		    // check if anmeldung exists already
		    $res = mysql_query("select xAnmeldung from anmeldung where xAthlet='$xAthlet' and xMeeting='".$_COOKIE['meeting_id']."'") or die(mysql_error());
		    if (mysql_num_rows($res)>0) {
			    $row = mysql_fetch_row($res);
			    $xAnmeldung = $row[0];
		    } else {
			    //insert anmeldung
			    mysql_query("insert into anmeldung(xAthlet,xMeeting,xKategorie,Bezahlt, Startnummer) values('$xAthlet','".$_COOKIE['meeting_id']."','$xCat','n','$comp_number')") or die("Anmeldung von $name $forename fehlgeschlagen".mysql_error());
			    $xAnmeldung = mysql_insert_id();
			    $cnt_anmeldung = $cnt_anmeldung + 1;
		    }		
		    
		    // check if 'start' (=inscription for discipline) already exists	
		    
		    // get performance of import, then overwrite if athlete has base-performance
		    // sprint: integer with last 3 digits milliseconds
		    // 1000m: integer with last 3 digits milliseconds, all in seconds, no minutes
		    
		    $p_arr = explode(".", $perf_import);
		    if (count($p_arr)==2) {
			    // sprint
			    // millisekunden mit nullen füllen rechts!!
			    $perf = 1000 * trim($p_arr[0]) + str_pad(trim($p_arr[1]),3,"0");
		    } elseif (count($p_arr)==3) {
			    // 1000m
			    $perf = 60000 * trim($p_arr[0]) + 1000 * trim($p_arr[1]) + str_pad(trim($p_arr[2]),3,"0");
		    } else {
			    $perf = 0;
		    }
		    
		    // if no base-performance is available then take importet perfomance for personal best and season best
		    $perf_Vorjahr = $perf;
		    
		    // gibt an, ob die Bestleistungsdaten aus den Stammdaten kommt 
		    $perf_avail = "n";
		    
		    if ( ! empty($license)) {
			    // $z contains the code (not id!!) of the discipline for this category
			    if ($project=="MilleGruyere") {
				    $z=$disc_code[$disc["1000"]]; 
			    }else{
				    $z=$disc_code[$disc[$cat_str]];
			    }
			    
			    $sql = "SELECT season_effort, notification_effort 
					      FROM base_performance 
				     LEFT JOIN base_athlete USING(id_athlete) 
					     WHERE base_athlete.license = '$license'
					       AND base_performance.discipline = '$z' 
					       AND season = 'O'"; //get outdoor records only
			    $res = mysql_query($sql) or die (mysql_error());
			    if (mysql_num_rows($res)>0 ) {
				    $row = mysql_fetch_row($res);
				    $pt = new PerformanceTime(trim($row[1]));
				    $pN = $pt->getPerformance();
				    
				    $ps = new PerformanceTime(trim($row[0]));
                    $pV = $ps->getPerformance();
				    
				    // it is possible, that query returns entries with efforts "is null": dont overwrite imported time then
				    // if only one (Vorjahr or actual) is available, use it for both
				    if (! empty($pV)) {
					    $perf_Vorjahr = $pV;
					    $perf_avail = "y";
					    if (empty($pN)) {
						    $perf = $pV;
					    }
				    }
				    if (! empty($pN)) {
					    $perf = $pN;
					    $perf_avail = "y";
					    if (empty($pV)) {
						    $perf_Vorjahr = $pN;
					    }
				    }
			    }
		    }
		    
		    $c = $wettk[$cat_str];
		    if (empty($c)) {
			    echo("Bei Athlet $name $forename konnte die Disziplin nicht angemeldet werden weil sie nicht existiert (in der entsprechenden Kategorie.) <br />");
			    continue;
		    }
		    $res = mysql_query("select xStart, Bestleistung, BaseEffort, VorjahrLeistung from start where xAnmeldung='$xAnmeldung' and xWettkampf='$c'");
		    if (mysql_num_rows($res)==0) {
			    // athlete has no start-entry yet
			    mysql_query("insert into start(xWettkampf,xAnmeldung,Bestleistung,BaseEffort,VorjahrLeistung) values('$c','$xAnmeldung','$perf','$perf_avail','$perf_Vorjahr')") or die("Der Start von $name $forename ($xAnmeldung) konnte nicht eingetragen werden: ".mysql_error());
			    $cnt_start = $cnt_start + 1;
		    } elseif (mysql_num_rows($res)==1) {
			    // athlete has start-entry, update performance
			    $row = mysql_fetch_row($res);
			    $xStart = $row[0];
			    if ($row[1]<>$perf || $row[3]<>$perf_Vorjahr) {
				    mysql_query("update start set Bestleistung='$perf', BaseEffort='$perf_avail', VorjahrLeistung='$perf_Vorjahr' where xStart='$xStart'") or die("Die Bestzeit von $name $forename ($xAnmeldung) konnte nicht aktualisiert werden: ".mysql_error());
				    $cnt_perf_update = $cnt_perf_update+1;
			    }
		    }
        }
        $i++;
		
	} //end while (main-loop for each row)
	
	// write stats
	echo("Es wurden $cnt_athlete Athleten importiert (davon $cnt_license mit Lizenz). <br />Es wurden $cnt_club neue Vereine eingefügt. <br />Es wurden $cnt_anmeldung Anmeldungen eingetragen. <br />Es wurden $cnt_start Starts eingetragen. <br />Es wurden $cnt_perf_update Bestleistungen aktualisiert. ");

} else {
	exit("Keine Datei oder kein Nachwuchsprojekt ausgewählt.");
}

?>