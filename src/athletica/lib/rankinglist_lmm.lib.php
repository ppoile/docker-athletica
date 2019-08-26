<?php

/**********
 *
 *	rankinglist LMM events
 *	
 */

if (!defined('AA_RANKINGLIST_LMM_LIB_INCLUDED'))
{
	define('AA_RANKINGLIST_LMM_LIB_INCLUDED', 1);

 
function AA_rankinglist_LMM($category, $formaction, $break, $cover, &$parser, $event, $heatSeparate, $type, $catFrom, $catTo)  
{   
require('./lib/cl_gui_page.lib.php');
require('./lib/cl_print_page.lib.php');
require('./lib/cl_print_page_pdf.lib.php');
require('./lib/cl_export_page.lib.php');  

require('./lib/common.lib.php');
require('./lib/results.lib.php');

if(AA_connectToDB() == FALSE)	{ // invalid DB connection
	return;		// abort
}

if(AA_checkMeetingID() == FALSE) {		// no meeting selected
	return;		// abort
}

global $rFrom, $rTo, $limitRank; // limits rank if limitRank set to true
$rFrom = 0; $rTo = 0;
$limitRank = false;
if($_GET['limitRank'] == "yes" && substr($formaction,0,6) == "export"){ // check if ranks are limited
	if(!empty($_GET['limitRankFrom']) && !empty($_GET['limitRankTo'])){
		$limitRank = true;
		$rFrom = $_GET['limitRankFrom'];
		$rTo = $_GET['limitRankTo'];
	}
}

// start a new HTML display page
if($formaction == 'view') {
	$GLOBALS[$list] = new GUI_LMMRankingList($_COOKIE['meeting']);
	$GLOBALS[$list]->printPageTitle("$strRankingLists " . $_COOKIE['meeting']);
}
// catch output and do nothing exept for theam rankings
// these will be added to the xml result file
elseif($formaction == "xml"){
	$GLOBALS['xmladdon'] = true;
	$GLOBALS[$list] = new XML_TeamRankingList($parser);
}
// start a new HTML print page
elseif($formaction == "print") {                  
	$GLOBALS[$list] = new PRINT_LMMRankingList_pdf($_COOKIE['meeting']);
	if($cover == true) {		// print cover page 
		$GLOBALS[$list]->printCover($GLOBALS['strResults']);
	}
}
// export ranking
elseif($formaction == "exportpress"){
	$GLOBALS[$list] = new EXPORT_TeamRankingListPress($_COOKIE['meeting'], 'txt');
}elseif($formaction == "exportdiplom"){
	$GLOBALS[$list] = new EXPORT_TeamRankingListDiplom($_COOKIE['meeting'], 'csv');
}

$selection = ''; 
if($formaction != "xml"){
	if ($event!='')  {
    	$mergedCat=AA_mergedCatEvent($category, $event);  
		}
	else {
    	$mergedCat=AA_mergedCat($category); 
	}
}
  
if(!empty($category)) {        // show every category  
    if ($mergedCat=='') {
        $selection = " AND k.xKategorie = $category";
    }
    else  {
        if ($heatSeparate){ 
            $selection = " AND k.xKategorie = $category"; 
        }
        else { 
            $selection = " AND k.xKategorie IN $mergedCat"; 
        }
    }
} 
   // show category from .... to
if($catFrom > 0) {        
     $getSortCat=AA_getSortCat($catFrom,$catTo);
	 if ($getSortCat[0]) {
	 	if ($catTo > 0){
			$selection = " AND k.Anzeige >=" . $getSortCat[$catFrom] . " AND k.Anzeige <=" . $getSortCat[$catTo] . " "; 
		}	 
		else {
			$selection = "AND k.Anzeige =" . $getSortCat[$catFrom] . " ";
		}
	 }
}  

// evaluation per category
global $cfgEventType, $cfgLMM, $cfgLMMMixed;
	 
$results = mysql_query("
	SELECT Distinct
	  	k.xKategorie
	  	, k.Name
		, w.Mehrkampfcode
        , d.Name
  	FROM
	  	wettkampf AS w
	  	LEFT JOIN kategorie AS k ON (k.xKategorie = w.xKategorie)
        LEFT JOIN kategorie_svm AS ks ON (ks.xKategorie_svm = w.xKategorie_svm)
        LEFT JOIN disziplin_" . $_COOKIE['language'] . " as d ON (w.Mehrkampfcode = d.Code)
  	WHERE 
        w.xMeeting = " . $_COOKIE['meeting_id'] ."
	    " . $selection . "   
        AND w.Mehrkampfcode IN(".implode(',',$cfgLMM).")     	
	ORDER BY
		k.Anzeige, ks.Code
");
 
if(mysql_errno() > 0) {		// DB error
	AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
}
else
{              
    mysql_query("DROP TABLE IF EXISTS tmp_lmm");    // temporary table     
          
    mysql_query("CREATE TEMPORARY TABLE tmp_lmm(              
                              xKategorie int(11)
                              , xDisziplin int(11)  
                              , Punkte float
                              , xTeam int(11)  
                              )
                              ENGINE=HEAP");       
     if(mysql_errno() > 0) {        // DB error
        AA_printErrorMsg(mysql_errno() . ": " . mysql_error()); 
     }
    
	// process all teams per category
	while($row = mysql_fetch_row($results))
	{
        $mixed = false;
        if (in_array($row[2], $cfgLMMMixed)) {
            $mixed = true;
        }
		processLMM($row[0], $row[1], $type, $mixed, $row[3]);
	} 
    
    mysql_query("DROP TABLE IF EXISTS tmp_lmm");    // temporary table          
         
	mysql_free_result($results);
}	// ET DB error categories 

$GLOBALS[$list]->endPage();	// end HTML page for printing

}	// end function AA_rankinglist_Team

//
//	process LMM events
//

function processLMM($xCategory, $category, $type, $mixed, $lmm_name)
{  
    global $cfgLMM;
    
	//global $rFrom, $rTo, $limitRank;
    $GLOBALS[$rFrom];
    $GLOBALS[$rTo];
    $GLOBALS[$limitRank];  
    
	require('./config.inc.php');
    
    
    $n = 0;
    
	// get athlete info per category and team   	
    $sql = "
        SELECT
            DISTINCT(a.xAnmeldung)
            , at.Name
            , at.Vorname
            , at.Jahrgang
            , t.xTeam
            , t.Name
            , v.Name 
            , IF(at.xRegion = 0, at.Land, re.Anzeige) AS Land
            , v.xVerein
            , at.Geschlecht
            
        FROM
            anmeldung AS a
            LEFT JOIN athlet AS at ON (at.xAthlet = a.xAthlet)
            INNER JOIN team AS t ON (t.xTeam = a.xTeam)
            LEFT JOIN verein AS v ON (v.xVerein = t.xVerein)
            LEFT JOIN start as st ON (st.xAnmeldung = a.xAnmeldung)
            LEFT JOIN wettkampf as w ON (w.xWettkampf = st.xWettkampf)
            LEFT JOIN region AS re ON (at.xRegion = re.xRegion) 
        WHERE 
            a.xMeeting = " . $_COOKIE['meeting_id'] ." 
            AND w.xKategorie = $xCategory
            AND w.Mehrkampfcode IN(".implode(',',$cfgLMM).")    
        ORDER BY
            t.xTeam
    ";                        
                
    $results = mysql_query($sql);    
   
	if(mysql_errno() > 0) {		// DB error
		AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
	}
	else
	{  
        $evaluationPt = 4;              //  nbr of athletes for calcualte points  
        $evaluationPt_m = 2;              //  nbr of male athletes for calcualte points in mixed teams 
        $evaluationPt_w = 2;              //  nbr of female athletes for calcualte points in mixed teams 
       
		$a = 0;
		$club = '';
		$info = '';
		$name = '';
		$points = 0;
		$team = '';
        $xTeam = 0;   
		$sep = '';
		$tm = '';
		$year = '';
        $country = '';  
        $sex = '';
	
		while($row = mysql_fetch_row($results))
		{    
			// store previous athlete before processing new athlete
			if(($a != $row[0])		// new athlete
				&& ($a > 0))			// first athlete processed
			{
				$athleteList[] = array(
					"points"=>$points
					, "name"=>$name
					, "year"=>$year
					, "info"=>$info
                    , "country"=>$country
                    , "club"=>$club 
                    , "sex"=>$sex 
				);
                 
				$points = 0;
				$info = '';
				$sep = '';
                
			}

			// store previous team before processing new team
			if(($tm != $row[4])		// new athlete
				&& ($tm > 0))			// first athlete processed
			{
				usort($athleteList, "cmp_lmm");	// sort athletes by points

				// nbr of athletes to include in team result
				$total = 0;
                $ath_m = 0;
                $ath_w = 0;
                
                if(!$mixed) {
				    for($i=0; $i < $evaluationPt; $i++) {
                        $total = $total + $athleteList[$i]['points'];
                    }  
                } else {
                    for($i=0; $i < count($athleteList); $i++) {
                        if($athleteList[$i]['sex'] == 'm') {
                            if($ath_m < $evaluationPt_m) {
                                $total = $total + $athleteList[$i]['points'];    
                            }
                            $ath_m++;
                        } elseif ($athleteList[$i]['sex'] == 'w') {
                            if($ath_w < $evaluationPt_w) {
                                $total = $total + $athleteList[$i]['points'];    
                            }
                            $ath_w++;  
                        }
                    }
                }  
                
				$teamList[] = array(
					"points"=>$total
                    , "rank"=>$n  
					, "team"=>$team
                    , "teamNr"=>$xTeam 
					, "club"=>$club
					, "athletes"=>$athleteList
					, "id"=>$tm                      
				);

				$team = '';
                $xTeam = 0;   
				$club = '';
				unset($athleteList);
				$sep = '';
			}

     
	        $tm = $row[4];		// keep current team

			// events       
            $sql = "
                SELECT
                    d.Kurzname
                    , d.Typ
                    , r.Leistung
                    , r.Info
                    , r.Punkte AS pts
                    , s.Wind
                    , w.Windmessung
                    , st.xAnmeldung
                    , d.xDisziplin
                    , w.Typ 
                FROM
                    start AS st USE INDEX (Anmeldung)
                    LEFT JOIN serienstart AS ss ON (ss.xStart = st.xStart   )
                    LEFT JOIN resultat AS r ON (r.xSerienstart = ss.xSerienstart) 
                    LEFT JOIN serie AS s ON (s.xSerie = ss.xSerie)
                    LEFT JOIN runde AS ru ON (ru.xRunde = s.xRunde) 
                    LEFT JOIN wettkampf AS w ON (w.xWettkampf = st.xWettkampf)
                    LEFT JOIN disziplin_" . $_COOKIE['language'] . " AS d ON (d.xDisziplin = w.xDisziplin) 
                    INNER JOIN
                        (SELECT
                          xSerienstart,
                          MAX(Punkte) AS pts_max
                        FROM
                          resultat
                        GROUP BY xSerienstart) maxt
                        ON (
                          r.xSerienstart = maxt.xSerienstart
                          AND r.Punkte = maxt.pts_max
                        )
                WHERE 
                    st.xAnmeldung = $row[0]                    
                    AND w.Mehrkampfcode IN(".implode(',',$cfgLMM).")                  
                    AND 
                    ((d.Typ = 6 && (r.Info !=  '" . $cfgResultsHighOut . "' && r.Info !=  '" . $cfgResultsHighOut1 . "' 
                                                 && r.Info !=  '" . $cfgResultsHighOut2 . "'  && r.Info !=  '" . $cfgResultsHighOut3 . "'  && r.Info !=  '" . $cfgResultsHighOut4 . "'
                                                 && r.Info !=  '" . $cfgResultsHighOut5 . "' && r.Info !=  '" . $cfgResultsHighOut6 . "' )
                      OR (d.Typ != 6 ) ))
                    
                    AND ru.Status = " . $cfgRoundStatus['results_done'] . "    
                GROUP BY
                    st.xStart
                ORDER BY
                    ru.Datum
                    , ru.Startzeit
            ";    
             
            //r.Info != '" . $cfgResultsHighOut . "'        
          
            $res = mysql_query($sql);      
           
			if(mysql_errno() > 0) {		// DB error
				AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
			}
			else
			{   
				while($pt_row = mysql_fetch_row($res))
				{
                    if ($row[8] != NULL && $pt_row[4] != NULL) {
                       
                            $sql = "INSERT INTO tmp_lmm
                                            VALUES (
                                                 $xCategory   
                                                , $pt_row[8]
                                                , $pt_row[4] 
                                                , $row[4]
                                                
                                            )";
                            mysql_query($sql);
                           
                            if(mysql_errno() > 0) {        // DB error                                 
                                AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
                            }   
                    }
					// set wind, if required
					if($pt_row[6] == 1)
					{
						if($pt_row[1] == $cfgDisciplineType[$strDiscTypeTrack]) {
							$wind = " / " . $pt_row[5];
						}
						else if($pt_row[1] == $cfgDisciplineType[$strDiscTypeJump]) {
							$wind = " / " . $pt_row[3];
						}
					}
					else {
						$wind = '';
					}
                    
                    $perf = $pt_row[2];
                    if($perf>=0) {
					    // format output
					    if(($pt_row[1] == $cfgDisciplineType[$strDiscTypeJump])
						    || ($pt_row[1] == $cfgDisciplineType[$strDiscTypeJumpNoWind])
						    || ($pt_row[1] == $cfgDisciplineType[$strDiscTypeThrow])
						    || ($pt_row[1] == $cfgDisciplineType[$strDiscTypeHigh])) {
                            
                            
						    $perf = AA_formatResultMeter($perf);
					    }
					    else {  
                            $perf = $perf/1000;
                            list($sec, $mili) = explode(".", $perf);
                            list($hour, $rest) = explode(".", ($sec/3600));
                            list($min, $rest) = explode(".", (($sec-($hour*3600))/60));
                            list($sec, $rest) = explode(".", ($sec-($hour*3600)-($min*60)));
                           
                            // round up to hundredth  (examples: 651 --> 660 and 650 --> 650)
                            $mili=ceil(sprintf ("%-03s",$mili)/10);              
                            list($a,$mili)=explode(".",($mili/100));         
                            $sec+=$a;   
                            
                            // display milli (two decimal after point without 0 in front)
                            $time = '';
                            if ($hour > 0) {
                                $time = sprintf("%02d", $hour).":".sprintf("%02d", $min).":".sprintf("%02d", $sec).".".sprintf("%-02s", $mili);  
                            }
                            elseif ($min > 0) { 
                                    $time = sprintf("%02d", $min).":".sprintf("%02d", $sec).".".sprintf("%-02s", $mili);  
                            }
                            else {
                                 $time =  $sec .".".sprintf("%-02s", $mili); 
                            }    
                            $perf = $time; 
					    }
                    }
                    
                   
					// calculate points
					$points = $points + $pt_row[4];	// accumulate points
                    
                    if ($perf != "" && $perf != 0){ 
					    if ($perf < 0){ 
                            if ($perf == $GLOBALS['cfgMissedAttempt']['db']){
                                        $perf = $GLOBALS['cfgMissedAttempt']['code'];
                                    }
                                    elseif  ($perf == $GLOBALS['cfgMissedAttempt']['dbx']){ 
                                             $perf = $GLOBALS['cfgMissedAttempt']['codeX'];  
                                    }
                            foreach($cfgInvalidResult as $value)    // translate value
                                {if($value['code'] == $perf) {
                                        $perf = $value['short'];
                                    }
                                } 
                        } 
					    $info = $info . $sep . $pt_row[0] . "&nbsp;(" . $perf . $wind . ", " .$pt_row[4] .")";
					    $sep = ", ";
                    }    
				}	// END WHILE combined events
				mysql_free_result($res);
			}

			$a = $row[0];
			$name = $row[1] . " " . $row[2];
			$year = $row[3];
			$team = $row[5];
            $xTeam = $row[4];   
			$club = $row[6];
            $country = $row[7];
            $sex = $row[9];
		}	// END WHILE athlete per category

		mysql_free_result($results);

		
        if (!empty($tm))        // add last team if any  or team independent 
		{
			// last athlete
			$athleteList[] = array(
				"points"=>$points
				, "name"=>$name
				, "year"=>$year
				, "info"=>$info
                , "country"=>$country  
                , "club"=>$club     
                , "sex"=>$sex     
			);
                
			// last team
			usort($athleteList, "cmp_lmm");	// sort athletes by points

			$total = 0;
            $ath_m = 0;
            $ath_w = 0;    
			if(!$mixed) {
                for($i=0; $i < $evaluationPt; $i++) {
                    $total = $total + $athleteList[$i]['points'];
                }  
            } else {
                for($i=0; $i < count($athleteList); $i++) {
                    if($athleteList[$i]['sex'] == 'm') {
                        if($ath_m < $evaluationPt_m) {
                            $total = $total + $athleteList[$i]['points'];    
                        }
                        $ath_m++;
                    } elseif ($athleteList[$i]['sex'] == 'w') {
                        if($ath_w < $evaluationPt_w) {
                            $total = $total + $athleteList[$i]['points'];    
                        }
                        $ath_w++;  
                    }
                }
            }

			$teamList[] = array(
				"points"=>$total
                 , "rank"=>$n     
				, "team"=>$team
                , "teamNr"=>$xTeam  
				, "club"=>$club
				, "athletes"=>$athleteList
				, "id"=>$tm                     
			);
		}
       
		$GLOBALS[$list]->printSubTitle("$lmm_name", "", "");
		$GLOBALS[$list]->startList();
		$GLOBALS[$list]->printHeaderLine();
        
		usort($teamList, "cmp_lmm");
		$rank = 1;									// initialize rank
		$r = 0;										// start value for ranking
		$p = 0;
		$tp = 0;
        
         $t = 0;

        foreach($teamList as $team)
            {
               
                
                if($limitRank && ($r < $rFrom || $r > $rTo)){ // limit ranks if set (export)
                    continue;
                }
                
                if($p == $team['points']) {    // not same points as previous team
                    $rank = $t;        // next rank
                    $arr_same[] = $t;
                }
               
                 $p = $team['points'];            // keep current points     
                 $t++;                            
                 $team['rank'] = $t;
            }
        $key_1 = array();   
        $key_2 = array();  
        $p = 0;     
        $i = 0;  
        
        foreach($teamList as $team)
            {
                $r++;
                
                if($limitRank && ($r < $rFrom || $r > $rTo)){ // limit ranks if set (export)
                    continue;
                }
                
                if($p != $team['points']) {    // not same points as previous team
                    $rank = $r;        // next rank
                    $teamList[$i]['rank'] = $rank;   
                }
                else {
                       $teamList[$i]['rank'] = $r;   
                      $arr_team = check_samePoints_lmm($xCategory);                         
                     
                      if ($arr_team[$teamNr_keep] < $arr_team[$team['teamNr']] ) {          //change ranking position
                           $key_1[] = $i -1;
                           $key_2[] = $i;  
                      }                         
                }
             $i++;
             $p = $team['points'];              // keep current points  
             $teamNr_keep = $team['teamNr'];    // keep current xTeam
            }
       if (count($key_1) > 0){
            foreach ($key_1 as $k) {
                    $teamList[$key_1[$k]][rank] = $r;   
                    $teamList[$key_2[$k]][rank] = $r - 1;       
            }
          
       }       
        
        usort($teamList, "cmp_rank_lmm");      

         
	    foreach($teamList as $team)
	    {
		    $r++;
		    
		    if($limitRank && ($r < $rFrom || $r > $rTo)){ // limit ranks if set (export)
			    continue;
		    }      
           
		    if($GLOBALS['xmladdon']){
			    $GLOBALS[$list]->printLine($team['rank'], $team['team'], $team['club'], $team['points'], $team['id']);
		    }else{
			    $GLOBALS[$list]->printLine($team['rank'], $team['team'], $team['club'], $team['points']);
		    }
            
		    $p = $team['points'];			// keep current points

		    $i = 0;
		    $xmlinfo = "";
          
		    foreach($team['athletes'] as $athlete)
			    {   
				    $i++;   
                    
				    $GLOBALS[$list]->printAthleteLine($athlete['name'], $athlete['year'], $athlete['points'], $athlete['country'],0,'lmm');
				    if($GLOBALS['xmladdon']){
					    $xmlinfo .= $athlete['name']." (".$athlete['points'].") / ";
				    }else{
					    $GLOBALS[$list]->printInfo($athlete['info']);
				    }
		    }    
		    
		    if($GLOBALS['xmladdon']){
			    $GLOBALS[$list]->printInfo(substr($xmlinfo,0,strlen($xmlinfo)-2));
		    } 
                                    
            $club = $team['club'];  
	    }

		$GLOBALS[$list]->endList();
	}	// ET DB error all teams

        

    
}	// end function processCombined()


//
// compare function to sort teamList
// 
function cmp_lmm ($a, $b) {
    if ($a["points"]== $b["points"]) return 0;
    return ($a["points"] > $b["points"]) ? -1 : 1;
}

function cmp_rank_lmm ($a, $b) {
    if ($a["rank"]== $b["rank"]) return 0;
    return ($a["rank"] > $b["rank"]) ? 1 : -1;
}

function check_samePoints_lmm($xCategory) {
    
    $sql = "SELECT 
                Punkte,
                xDisziplin,
                xTeam
            FROM tmp_lmm 
            WHERE xKategorie = " .$xCategory ."
            ORDER BY xDisziplin, Punkte";
    
    $res = mysql_query($sql); 
    if (mysql_errno() > 0) {
       AA_printErrorMsg(mysql_errno() . ": " . mysql_error());  
    }
    else {
        $disc = 0;
        $arr_team = array();
        while ($row = mysql_fetch_row($res)) {
            
             if ($disc != $row[1]){
                  $arr_team[$row[2]] ++;
                 
             }
            
             $disc = $row[1];
            
        }
        
    }
    
  return  $arr_team; 
}



}	// AA_RANKINGLIST_TEAM_LIB_INCLUDED
?>
