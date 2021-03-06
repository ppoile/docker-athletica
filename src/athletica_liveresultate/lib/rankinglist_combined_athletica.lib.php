<?php

/**********
 *
 *	rankinglist combined events
 *	
 */
   
if (!defined('AA_RANKINGLIST_COMBINED_LIB_INCLUDED'))
{
	define('AA_RANKINGLIST_COMBINED_LIB_INCLUDED', 1);

function AA_rankinglist_Combined($comb_id, $cat_id, $meeting)
{     
require('./lib/cl_gui_page.lib.php');
require('./lib/cl_print_page.lib.php');
require('./lib/cl_export_page.lib.php');

require('./lib/common.lib.php');
require('./lib/results.lib.php');
require('./config.inc.php');   

if(AA_connectToDB() == FALSE)	{ // invalid DB connection
	return;		// abort
}

if(AA_checkMeetingID() == FALSE) {		// no meeting selected
	return;		// abort
}   
  

// get athlete info per contest category    
  $sql_comb="SELECT DISTINCT 
        a.xAnmeldung As ath_id
        , at.Name
        , at.Vorname
        , at.Jahrgang
        , k.Name
        , IF(a.Vereinsinfo = '', v.Name, a.Vereinsinfo)
        , IF(at.xRegion = 0, at.Land, re.Anzeige)
        , w.Mehrkampfcode        
        , d.Name As comb_name
        , w.xKategorie
        , ka.Code As cat_code
        , ka.Name As cat_name
        , ka.Alterslimite As cat_limit 
        , d.Code As dis_code 
        , at.xAthlet
        , at.Geschlecht               
    FROM
        anmeldung AS a
        LEFT JOIN athlet AS at ON (at.xAthlet = a.xAthlet )
        LEFT JOIN verein AS v  ON (v.xVerein = at.xVerein  )
        LEFT JOIN start as st ON (st.xAnmeldung = a.xAnmeldung ) 
        LEFT JOIN wettkampf as w  ON (w.xWettkampf = st.xWettkampf)
        LEFT JOIN disziplin_" . $_COOKIE['language'] . " as d ON (w.Mehrkampfcode = d.Code)
        LEFT JOIN kategorie AS k ON (k.xKategorie = w.xKategorie)
        LEFT JOIN kategorie AS ka ON (ka.xKategorie = a.xKategorie)     
        LEFT JOIN region as re ON (at.xRegion = re.xRegion) 
    WHERE w.xMeeting  = " . $meeting . "
        AND w.xKategorie = ".$cat_id." 
        AND w.Mehrkampfcode = ".$comb_id."
    AND st.anwesend = 0  
    ORDER BY 
        k.Anzeige
        , w.Mehrkampfcode 
        , ka.Alterslimite DESC
    ";      
              
$res_comb = mysql_query($sql_comb);     

if(mysql_errno() > 0) {		// DB error
	AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
}
else
{     
	$catEntry = '';
	$catEntryLimit = "";
	$u23name = "";
	$comb = 0; // hold combined type
	$combName = "";
	$lastTime = ""; // hold start time of last event for print list
	$a = 0;
	$info = '';
	$points = 0;
	$sep = '';
		
	// start a new HTML display page
	if($formaction == 'view') {	// display page for speaker 
		$list = new GUI_CombinedRankingList($_COOKIE['meeting']);
		$list->printPageTitle("$strRankingLists " . $_COOKIE['meeting']);
	}
	// start a new HTML print page
	elseif($formaction == "print") {
		$list = new PRINT_CombinedRankingList($_COOKIE['meeting']);
		if($cover == true) {		// print cover page 
			$list->printCover($GLOBALS['strResults'], $cover_timing);
		}
	}
	 
	
	while($row_comb = mysql_fetch_assoc($res_comb))
	{   $dCode = $row_comb['dis_code']; 
    
		// store previous before processing new athlete
		if(($a != $row_comb['ath_id'])		// new athlete
			&& ($a > 0))			// first athlete processed
		{              		
			
                $points_arr[] = $points;  

                $points_arr_more_disc_all[$cat_id][] = $points_disc;
                                     
			    $name_arr[] = $name; 
                               
			    $year_arr[] = $year;
			    $club_arr[] = $club;
			    $info_arr[] = $info;
			    $ioc_arr[] = $ioc;
			    $x_arr[] = $a;
                $rank_arr[] = $rank;   
                                
			    $info = '';
			    $points = 0;
			    $sep = '';
        
            
		}
         
        
		$catEntry = $row_comb['cat_code'];
		$catEntryLimit = $row_comb['cat_limit'];
		$combName = $row_comb['comb_name'];
        
        $order = " w.Mehrkampfreihenfolge ASC, ru.Datum, ru.Startzeit"; 
        $selectionMk = " AND w.Mehrkampfcode = " .$comb_id;               
        $selectionDisc = '';    
		
		// events  
        $sql_res="SELECT
                d.Kurzname
                , d.Typ
                , MAX(IF ((r.Info='-') && (d.Typ = 6) ,0,r.Leistung)) 
                , r.Info
                , MAX(IF ((r.Info='-') && (d.Typ = 6),0,r.Punkte)) AS pts    
                , s.Wind
                , w.Windmessung
                , st.xStart
                , CONCAT(DATE_FORMAT(ru.Datum,'$cfgDBdateFormat'), ' ', TIME_FORMAT(ru.Startzeit, '$cfgDBtimeFormat'))
                , w.Mehrkampfreihenfolge 
                , ss.Bemerkung
                , w.info
                , ss.xSerienstart 
                , d.Code
            FROM
                start AS st USE INDEX (Anmeldung)
                LEFT JOIN serienstart AS ss ON (ss.xStart = st.xStart )
                LEFT JOIN resultat AS r ON (r.xSerienstart = ss.xSerienstart) 
                LEFT JOIN serie AS s ON (s.xSerie = ss.xSerie)
                LEFT JOIN runde AS ru ON (ru.xRunde = s.xRunde)
                LEFT JOIN wettkampf AS w ON (w.xWettkampf = st.xWettkampf)
                LEFT JOIN disziplin_" . $_COOKIE['language'] . " AS d ON (d.xDisziplin = w.xDisziplin)
            WHERE st.xAnmeldung = $row[0]  
                $selectionDisc 
                AND ( (r.Info = '" . $cfgResultsHighOut . "' && d.Typ = 6 && r.Leistung < 0)  OR  (d.Typ = 6 && (r.Info !=  '" . $cfgResultsHighOut . "' && r.Info !=  '" . $cfgResultsHighOut1 . "' 
                                                 && r.Info !=  '" . $cfgResultsHighOut2 . "'  && r.Info !=  '" . $cfgResultsHighOut3 . "'  && r.Info !=  '" . $cfgResultsHighOut4 . "'
                                                 && r.Info !=  '" . $cfgResultsHighOut5 . "' && r.Info !=  '" . $cfgResultsHighOut6 . "' && r.Info !=  '" . $cfgResultsHighOut7 . "' && r.Info !=  '" . $cfgResultsHighOut7 . "'))
                      OR (d.Typ != 6 ) )   
                
               AND w.xKategorie = ".$row_comb['ath_id']."
                $selectionMk   
                AND ru.Status = " . $cfgRoundStatus['results_done'] . "   
            GROUP BY
                st.xStart
            ORDER BY
                $order";                     
               
        $res_res = mysql_query($sql_res);    
       
		if(mysql_errno() > 0) {		// DB error
			AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
		}
		else
		{   $count_disc=0;
            $remark='';
            $points_disc = array();
             
			while($pt_row = mysql_fetch_row($res))
			{                
                $remark=$pt_row[10];  
				$lastTime = $pt_row[8];
				
				if($pt_row[1] == $cfgDisciplineType[$strDiscTypeJump]){
					$res2 = mysql_query("SELECT r.Info FROM 
								resultat as r
								LEFT JOIN serienstart as ss USING(xSerienstart)
							WHERE
								ss.xStart = $pt_row[7]
							AND	r.Punkte = $pt_row[4]");
					$row2 = mysql_fetch_array($res2);
					$pt_row[3] = $row2[0];
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

				// format output
				if(($pt_row[1] == $cfgDisciplineType[$strDiscTypeJump])
					|| ($pt_row[1] == $cfgDisciplineType[$strDiscTypeJumpNoWind])
					|| ($pt_row[1] == $cfgDisciplineType[$strDiscTypeThrow])
					|| ($pt_row[1] == $cfgDisciplineType[$strDiscTypeHigh])) {
					$perf = AA_formatResultMeter($pt_row[2]);
				}
				else {
					$perf = AA_formatResultTime($pt_row[2], true);
				}
                 
				 // show only points for number of choosed disciplines if the diszipline is done	  
                $count_disc++;    
                   if ($count_disc<=$disc_nr)  {
                       
                       
                       if($pt_row[4] > 0 || $ukc) {       // any points for this event 
                     
                           if ($ukc){
                                 $pointsUKC = AA_utils_calcPointsUKC(0, $pt_row[2],0, $row[16], $pt_row[12], $row[14], $row[0], $pt_row[13]);    
                                 $points = $points + $pointsUKC;      // calculate points  
                                 
                                 mysql_query("UPDATE resultat SET
                                                    Punkte = $pointsUKC
                                              WHERE
                                                    xSerienstart = $pt_row[12]");
                                 if(mysql_errno() > 0) {
                                        AA_printErrorMsg(mysql_errno() . ": " . mysql_error());
                                 }
                                 AA_StatusChanged(0,0,$pt_row[12]);
                           }
                           else {
                                 $points = $points + $pt_row[4];      // calculate points  
                           }
                          
                           if ($dCode == 408) {                // UBS Kids Cup
                               switch ($pt_row[1]){
                                   case 1:
                                   case 2: $c=1;          // track
                                           break;
                                   case 4:
                                   case 5: 
                                   case 6: $c=2;          // jump and high
                                           break; 
                                   case 8: $c=3;          // throw
                                           break;  
                                   default: $c=0;
                                           break;
                               }
                                $points_disc[$c]=$pt_row[4];
                           } 
                           else {
                                if ($ukc){
                                    switch ($pt_row[1]){
                                       case 1:
                                       case 2: $c=1;          // track
                                               break;
                                       case 4:
                                       case 5: 
                                       case 6: $c=2;          // jump and high
                                               break; 
                                       case 8: $c=3;          // throw
                                               break;  
                                       default: $c=0;
                                               break;
                                    }
                                     $points_disc[$c]=$pointsUKC;                                        
                                }
                                else {
                                      $points_disc[$row[9]]=$pt_row[4];    
                                }
                               
                               
                           }
                           if ($ukc){
                               $info = $info . $sep . $pt_row[0] . " " . "&nbsp;(" . $perf . $wind . ", $pointsUKC)";                      
                           }
                           else {
                                 $info = $info . $sep . $pt_row[0] . " " . "&nbsp;(" . $perf . $wind . ", $pt_row[4])";                      
                           }
					       
					       $sep = ", ";     
                       }                         
                        elseif ($pt_row[4] == 0 && $pt_row[2] >= 0){          //  athlete with 0 points                                   
                                $info = $info . $sep . $pt_row[0] . " " . "&nbsp;(" . $perf . $wind . ", $pt_row[4])";                      
                                $sep = ", ";       
                        }  
                       else{ 
                         $count_disc--;   
                         $pointTxt="" ;   
                         foreach($cfgInvalidResult as $value)    // translate value
                                {
                                 if($value['code'] == $perf) {
                                    $pointTxt = $value['short'];
                                 }
                         }  
                         
                         $info = $info . $sep . $pt_row[0] . $pt_row[11] . "&nbsp;(" . $perf . $wind . ", $pointTxt)";                      
                         $sep = ", ";     
                       } 
                   }           
			}	// END WHILE combined events
           
			mysql_free_result($res);
		}     
       
		$a = $row[0];
		$name = $row[1] . " " . $row[2];
		$year = AA_formatYearOfBirth($row[3]);
        $birthDate = $row[3];
        $sex = $row[15];
		$club = $row[5];
		$ioc = $row[6];   	
        $remark_arr[] = $remark; 
        if ($ukc){
            $xKat = AA_getCatUkc($row[3], $row[15], true);          
        }
        else {
               $xKat = $row[9];     
        }
      
        $points_disc_keep = $points_disc;
        $dCode_keep = $dCode;
       
        
        
	}	// END WHILE athlete per category
  
	if(!empty($a))		// add last athlete if any
	{
		$points_arr[] = $points;    
        $points_arr_more_disc_all[$xKat][] = $points_disc; 
		$name_arr[] = $name;
		$year_arr[] = $year;
		$club_arr[] = $club;
		$info_arr[] = $info;
		$ioc_arr[] = $ioc;
		$x_arr[] = $a;
        $remark_arr[] = $remark;
        $rank_arr[] = $rank;
	}    
  
	if(!empty($cat))		//	add last category if any
	{
		$u23name = '';
		if(($comb == 410 || $comb == 400) && $catEntryLimit < 23 && $sepu23){
			$u23name = " (U 23)";
		}
		$list->endList();   
        if ($ukc){
              $list->printSubTitle($catUKC.", " .$cfgUKC_Name);  
        }  
        else {
              $list->printSubTitle($cat."$u23name, ".$combName, "", "");  
        }
		

		$list->startList();
		$list->printHeaderLine($lastTime);

		arsort($points_arr, SORT_NUMERIC);	// sort descending by points
        
		$rank = 1;									// initialize rank
		$r = 0;										// start value for ranking
		$p = 0;  
        $k = 0;  
        
        $no_rank=999999;
        $max_rank=$no_rank;       
		 	   
		foreach($points_arr as $key => $val)
		{    
			$r++;                           
			
			if($limitRank && ($r < $rFrom || $r > $rTo)){ // limit ranks if set (export)
				continue;
			}
			
			if($p != $val) {	// not same points as previous athlete
				$rank = $r;		// next rank
			}
           		   	    		 	 
		    // not set rank for invalid results 
		    if (preg_match("@\(-[1]{1}@", $info_arr[$key])){ 
                $rank=$max_rank; 
                $max_rank+=1;      
				$r--;  
		 	}     
                        
			$p = $val;			// keep current points
            $k = $key;            // keep current key
            $rank_arr[$key]  = $rank;   
        }   
              
        asort($rank_arr, SORT_NUMERIC);    // sort descending by rank       
        
         $rank_keep = 0; 
        
         foreach($rank_arr as $key => $v){
                $val=$points_arr[$key];  
                $rank=$v;   
               
                if ($rank == $rank_keep){                       
                        $c=0;
                        $keep_c=0;
                        // first rule 
                        for ($i=1; $i <= sizeof($points_disc); $i++){                                 
                             if  ($points_arr_more_disc_all[$xKat][$key_keep][$i] > $points_arr_more_disc_all[$xKat][$key][$i]){
                                  $keep_c ++;
                             }
                             else {
                                 $c++;
                             }
                        }
                        $more=ceil(sizeof($points_disc)/2);  
                        if (sizeof($points_disc) % 2 == 0){              // combined with even number discs
                             $more++;                                   
                        }
                        if     ($keep_c >= $more && $keep_c > $c){
                                $rank_arr[$key]++;
                        }
                        else {
                             if  ($c >= $more && $c > $keep_c){   
                                $rank_arr[$key_keep]++;     
                             }
                             else {
                                  // second rule 
                                  // check the best points of the highest points of discipline
                                  $k = AA_get_AthletBestPointDisc($points_arr_more_disc_all[$xKat][$key_keep], $points_arr_more_disc_all[$xKat][$key], $key_keep, $key);
                                  if ($k != 0){
                                       $rank_arr[$k]++;     
                                  }
                                  // if $k is 0, all points of diszipline are the same -->   athletes with same rank
                                 
                             }   
                        }                         
                }            
                $rank_keep = $rank;  
                $key_keep = $key; 
                }
         
                asort($rank_arr, SORT_NUMERIC);    // sort descending by rank          
                       
                foreach($rank_arr as $key => $v)
                    {   
                    $val=$points_arr[$key];  
                    $rank=$v;    
           
                    if ($rank>=$no_rank) {
                        $rank='';
                    }
           
                    $list->printLine($rank, $name_arr[$key], $year_arr[$key], $club_arr[$key], $val, $ioc_arr[$key]);  
                    $list->printInfo($info_arr[$key]);   

            
			        // insert points into combined top performance of entry
			        mysql_query("UPDATE anmeldung SET BestleistungMK = $val WHERE xAnmeldung = ".$x_arr[$key]);		
		        }   		
	}

	mysql_free_result($results);
	$list->endList();

	$list->endPage();	// end HTML page for printing
}	// ET DB error all teams

}	// end function AA_rankinglist_Combined

}	// AA_RANKINGLIST_COMBINED_LIB_INCLUDED
?>
