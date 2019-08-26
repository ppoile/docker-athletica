<html>
<head>

</head><body>
<h2>Export Daten f&uuml;r Finisherclip UBS Kids Cup</h2>
<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require('../../parameters.inc.php');
include("lib/dbconnect.php");

$sql_meetings = "SELECT *
                            FROM meeting
                        ORDER BY xMeeting;";
$res_meetings = mysql_query($sql_meetings);

if(mysql_errno() > 0)        // DB error
{
    exit("Verbindungsfehler: ".mysql_errno() . ": " . mysql_error());
} else {

  $sql_emptymeeting = 'select * from meeting where Nummer="";';
  $res_emptymeeting = mysql_num_rows(mysql_query($sql_emptymeeting));

  ?>
  <div id="apiexport">
    <?php
    if($res_emptymeeting>0){
      echo '<h3>Fehler!</h3><br />Mindestens ein Meeting hat keine Meetingnummer. Bitte trage diese nach!<br />
      <table>
      <tr>
      <td>
      <img src="nummer.jpg" width="200"/>
      </td><td><style type="text/css">
    table.tableizer-table {
        font-size: 10px;
        border: 1px solid #CCC;
        font-family: Arial, Helvetica, sans-serif;
    }
    .tableizer-table td {
        padding: 1px;
        margin: 2px;
    }
    .tableizer-table th {
        background-color: #104E8B;
        color: #FFF;
        font-weight: bold;
    }
</style>
<table class="tableizer-table">
<thead><tr class="tableizer-firstrow"><th>EventID</th><th>Ort</th><th>Kanton</th><th>Datum</th></tr></thead><tbody>
<tr><td>213351</td><td>Lugano</td><td>TI</td><td>10.06.2019</td></tr>
<tr><td>213261</td><td>Versoix</td><td>GE</td><td>16.06.2019</td></tr>
<tr><td>213166</td><td>Zug</td><td>ZG</td><td>19.06.2019</td></tr>
<tr><td>213216</td><td>Stans</td><td>OW/NW</td><td>29.06.2019</td></tr>
<tr><td>213257</td><td>Landquart</td><td>GR</td><td>29.06.2019</td></tr>
<tr><td>213304</td><td>Chailly sur Montreux</td><td>VD</td><td>06.07.2019</td></tr>
<tr><td>213237</td><td>Le Mouret</td><td>FR</td><td>30.06.2019</td></tr>
<tr><td>213305</td><td>Luzern</td><td>LU</td><td>30.06.2019</td></tr>
<tr><td>213276</td><td>Stein</td><td>AG</td><td>25.08.2019</td></tr>
<tr><td>213350</td><td>Riehen</td><td>BL</td><td>17.08.2019</td></tr>
<tr><td>213303</td><td>Riehen</td><td>BS</td><td>17.08.2019</td></tr>
<tr><td>213306</td><td>Schaffhausen</td><td>SH</td><td>17.08.2019</td></tr>
<tr><td>213168</td><td>M&uuml;mliswil-Ramiswil</td><td>SO</td><td>25.08.2019</td></tr>
<tr><td>213349</td><td>Herisau</td><td>AI/AR</td><td>25.08.2019</td></tr>
<tr><td>213259</td><td>St.Gallen</td><td>SG</td><td>25.08.2019</td></tr>
<tr><td>213260</td><td>Kreuzlingen</td><td>TG</td><td>21.08.2019</td></tr>
<tr><td>213167</td><td>K&uuml;ssnacht am Rigi</td><td>SZ</td><td>24.08.2019</td></tr>
<tr><td>213278</td><td>Glarus</td><td>GL</td><td>24.08.2019</td></tr>
<tr><td>213170</td><td>La Chaux-de-Fonds</td><td>NE</td><td>17.08.2019</td></tr>
<tr><td>213409</td><td>Sierre</td><td>VS</td><td>17.08.2019</td></tr>
<tr><td>213311</td><td>Del&eacute;mont</td><td>JU</td><td>29.06.2019</td></tr>
<tr><td>213238</td><td>Wetzikon</td><td>ZH</td><td>24.08.2019</td></tr>
<tr><td>213169</td><td>Interlaken</td><td>BE</td><td>25.08.2019</td></tr>
<tr><td>213277</td><td>Schaan</td><td>FL</td><td>30.06.2019</td></tr>
</tbody></table>
      </td>
      </tr>
      </table></div>';
    }
    else{
    ?>
    <a style="color:red;" href="api-export.php">Alle vorhandenen Meetings exportieren / export tout les meetings</a><br /></div>
  <?php
}
    /*
    while ($row_meetings = mysql_fetch_assoc($res_meetings)) {

        $meeting_id = $row_meetings['xMeeting'];
        ?>

        <h3>Altes Format (2016):</h3><a href="export.php?type=entries&meeting=<?=$meeting_id?>"><?=$row_meetings['Name']?></a><br>
        <?php

        $sql_serien = "SELECT
              kategorie.Kurzname As kat_name
              , wettkampf.xWettkampf As event_id
              , runde.Startzeit As event_time
            FROM
              serie
              LEFT JOIN runde USING (xRunde)
              LEFT JOIN wettkampf USING (xWettkampf)
              LEFT JOIN kategorie USING (xKategorie)
            WHERE xMeeting = $meeting_id
                AND xDisziplin = 40
            GROUP BY xWettkampf
            ORDER BY runde.Startzeit;";
        $res_serien = mysql_query($sql_serien);

        if(mysql_errno() > 0)        // DB error
        {
            exit("Verbindungsfehler: ".mysql_errno() . ": " . mysql_error());
        } else {
            while ($row_serien = mysql_fetch_assoc($res_serien)) {
                ?>
                <a href="api-export.php?type=serie&event=<?=$row_serien['event_id']?>"><?=$row_serien ['kat_name']?></a> (<?=$row_serien ['event_time']?>)<br>
                <?php
            }
        }

    }
    */
}


?>
<script>
if(navigator.onLine==true){
  document.getElementById("apiexport").style.display="block";
}
</script>

</body>
</html>
