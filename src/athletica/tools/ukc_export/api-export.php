<?php
/*print_r($_POST);
print_r($_GET);**/

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require('../../parameters.inc.php');
include("lib/dbconnect.php");
include("lib/functions.php");
  
$sql_athletes = "SELECT
anmeldung.kidId As kidID
, concat(athlet.Vorname,' ',athlet.Name) as name
, DATE_FORMAT(athlet.Geburtstag,'%d-%m-%Y') as date_of_birth
, athlet.Geschlecht as gender
, anmeldung.Startnummer As concurent_no
, athlet.Email As email
, concat(kategorie.Kurzname,'-',serie.Bezeichnung)  As race_name
, serienstart.Bahn as bahn
, serie.xSerie As serie_id
, meeting.Nummer
FROM serienstart
LEFT JOIN serie USING (xSerie)
LEFT JOIN runde USING (xRunde)
LEFT JOIN wettkampf USING (xWettkampf)
LEFT JOIN meeting USING(xMeeting)
LEFT JOIN START USING (xStart)
LEFT JOIN anmeldung USING (xAnmeldung)
LEFT JOIN athlet USING (xAthlet)
LEFT JOIN region USING (xRegion)
LEFT JOIN kategorie ON kategorie.xKategorie = anmeldung.xKategorie
ORDER BY serie_id, bahn";

$res_athletes = mysql_query($sql_athletes);

if(mysql_errno() > 0)        // DB error
{
    exit("Verbindungsfehler: ".mysql_errno() . ": " . mysql_error());
} else {
    while ($row_athletes = mysql_fetch_assoc($res_athletes)) {
        $athlets[]=$row_athletes;
    }
}

// remove 0 from category
$pattern = '/(\w{2,4}-[WM])0?(.*)/';
$replacement = '$1$2';



$fp = fopen('import_users.csv', 'w');
foreach ($athlets as $fields) {
    fputcsv($fp, $fields);
}
//$res = send2ir($_POST['mail'],$_POST['password']);
$json=json_decode($res,true);

echo '<br>';
echo "Die Daten wurden formatiert und sind nun <a href='import_users.csv' target='_blank' download>hier</a> verf&uumlgbar. Bitte bringen Sie dieses File zum Mitarbeiter von Yoveo<br />";
echo "Les participants sont exporte. S'il vous plait les telecharge <a href='import_users.csv' target='_blank' download>ici</a> et le donner a le representative de Yoveo<br />";

fclose($fp);


?>
