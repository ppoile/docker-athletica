<?php
require('../../parameters.inc.php');
include("lib/dbconnect.php");
include("lib/functions.php");

$csv_column_separator = ';';
$csv_row_separator = "\n";
$content = '';

  if($_GET['type'] == 'entries') {
      
      $meeting_id = $_GET['meeting'];
      
      $sql_athletes = "SELECT 
                            region.Anzeige As canton
                            , anmeldung.Startnummer As athlet_bib
                            , athlet.Vorname As athlet_vorname
                            , athlet.Name As athlet_nachname
                            , athlet.Email As athlet_email
                            , kategorie.Kurzname As athlet_kategorie
                        FROM anmeldung
                            LEFT JOIN athlet USING(xAthlet)
                            LEFT JOIN region USING(xRegion)
                            LEFT JOIN kategorie USING(xKategorie)
                        WHERE xMeeting = $meeting_id
                        ORDER BY athlet_bib;";    
        $res_athletes = mysql_query($sql_athletes);
         
        if(mysql_errno() > 0)        // DB error
        {
            exit("Verbindungsfehler: ".mysql_errno() . ": " . mysql_error());
        } else {
            $content .= '"'.csv_prepare('Kanton').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Startnummer').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Vorname').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Nachname').'"'.$csv_column_separator;    
            //$content .= '"'.csv_prepare('Email').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Kategorie').'"'.$csv_row_separator;    
            
            while ($row_athletes = mysql_fetch_assoc($res_athletes)) { 
                $canton = $row_athletes['canton'];
                
                $content .= '"'.csv_prepare($row_athletes['canton']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_bib']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_vorname']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_nachname']).'"'.$csv_column_separator;
                //$content .= '"'.csv_prepare($row_athletes['athlet_email']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_kategorie']).'"'.$csv_row_separator;
            }
            $filename = $canton . "_" . date('Ymd_His', time());
            csv_output($content, $filename);
            
        }
      
  } elseif ($_GET['type'] == 'serie') {
      
      $event_id = $_GET['event'];
      
      $sql_athletes = "SELECT 
                            region.Anzeige As canton
                            , anmeldung.Startnummer As athlet_bib
                            , athlet.Vorname As athlet_vorname
                            , athlet.Name As athlet_nachname
                            , athlet.Email As athlet_email
                            , kategorie.Kurzname As athlet_kategorie
                            , serie.Bezeichnung As athlet_serie 
                        FROM serienstart 
                          LEFT JOIN serie USING (xSerie) 
                          LEFT JOIN runde USING (xRunde) 
                          LEFT JOIN wettkampf USING (xWettkampf) 
                          LEFT JOIN START USING (xStart) 
                          LEFT JOIN anmeldung USING (xAnmeldung) 
                          LEFT JOIN athlet USING (xAthlet) 
                          LEFT JOIN region USING (xRegion) 
                          LEFT JOIN kategorie ON kategorie.xKategorie = anmeldung.xKategorie
                      WHERE wettkampf.xWettkampf = $event_id
                        ORDER BY athlet_serie, serienstart.Bahn;";    
        $res_athletes = mysql_query($sql_athletes);
         
        if(mysql_errno() > 0)        // DB error
        {
            exit("Verbindungsfehler: ".mysql_errno() . ": " . mysql_error());
        } else {
            $content .= '"'.csv_prepare('Kanton').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Startnummer').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Vorname').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Nachname').'"'.$csv_column_separator;    
            //$content .= '"'.csv_prepare('Email').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Kategorie').'"'.$csv_column_separator;    
            $content .= '"'.csv_prepare('Serie').'"'.$csv_row_separator;    
            
            while ($row_athletes = mysql_fetch_assoc($res_athletes)) {
                $canton = $row_athletes['canton'];
                $category = $row_athletes['athlet_kategorie'];
             
                $content .= '"'.csv_prepare($row_athletes['canton']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_bib']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_vorname']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_nachname']).'"'.$csv_column_separator;
                //$content .= '"'.csv_prepare($row_athletes['athlet_email']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_kategorie']).'"'.$csv_column_separator;
                $content .= '"'.csv_prepare($row_athletes['athlet_serie']).'"'.$csv_row_separator;
            }
            $filename = $canton . "_" . $category . "_" . date('Ymd_His', time());
            csv_output($content, $filename);
            
        }
      
  } else {
      header('Location: index.php');
  }
?>
