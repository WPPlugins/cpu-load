<?php
error_reporting(E_NONE); 
/*
Plugin Name: CPU Load
Plugin URI: http://patrick-designs.de
Description: Zeigt die aktuellen CPU-Werte an, sowie den Gesamdurchschnitt, Tagesdurchschnitt, und den höchsten Tageswert.
Version: 1.0.2
Author: Patrick Hausmann
Author URI: http://patrick-designs.de
Update Server: http://patrick-designs.de/downloads/cpu_log.zip
Min WP Version: 3.0
Max WP Version: 3.0.4
*/



/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (version 2) as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
*/

//Variablen bestimmen
$wpdb->cl = $wpdb->prefix . 'cpu_log';
$page=$_SERVER['PHP_SELF'];


function cpuload() {
	
	global $wpdb, $page;
	$stats = exec("uptime");
	$time=time()+3600;
	preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/', $stats, $regs);
	$prozent=$regs[1]*100;
	$result=$wpdb->query("INSERT INTO $wpdb->cl (time,prozent,page) VALUES('$time','$prozent','$page')");
	return ($prozent);
}





function cpulog_widget() {

	global $wpdb;
	$sql = $wpdb->get_results("SELECT * FROM ".$wpdb->cl." order by `time` DESC LIMIT 25");
	$sql2 = $wpdb->get_results("SELECT * FROM ".$wpdb->cl." ");

	foreach($sql AS $use) {
		$prozent[]=$use->prozent;
	}



	$anzahl=0;
	$anzahl_today=0;



	foreach($sql2 AS $use) {

		$anzahl++;
		$prozent_all=$prozent_all+$use->prozent;
		if(date("d.m.y", $use->time)==date("d.m.y", time()+3600)) { $prozent_heute[]=$use->prozent; $anzahl_today++;}
	}

	$durchschnitt= round($prozent_all/$anzahl,0);
	$durchschnitt_heute= round(array_sum($prozent_heute)/$anzahl_today,0);
?>


<table width="100%">
<tr>
<td>Aktl.: <?php echo cpuload(); ?> % </td>

<td> &#216;-Heute: <?php echo $durchschnitt_heute; ?> %</td>
<td> &#216;-Gesamt: <?php echo $durchschnitt; ?> %</td>
</tr><tr>
<td> Max. Heute: <?php echo max($prozent_heute); ?> %</td>
<td>Messungen: <?php echo $anzahl; ?> <a href="?cpuload=cpu_del">x</a></td>

</tr>
</table>

<?php
//Grafik zeichnen
	$maxvalue=max($prozent);

	echo "<img style=\"\" src=\"http://chart.apis.google.com/chart?chg=25,25&cht=lc&chs=600x50&chd=".cl_encodeChartData($prozent)."&chxt=y&chxl=0:|0|".$maxvalue."\" alt=\"google api\" width=\"100%\" height=\"50\">";

echo'<small>Besuche doch auch mal meinen Blog: <a href="http://filme-blog.com">Filme-blog.com</a></small>';
}
//Widget FUnktion zuende


//Funktion für die Daten der Google API
function cl_encodeChartData($values) {
		
	$maxValue = max($values);
	$simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$chartData = "s:";
	  for ($i = 0; $i < count($values); $i++) {
		$currentValue = $values[$i];

		if ($currentValue > -1) {
		$chartData.=substr($simpleEncoding,61*($currentValue/$maxValue),1);
		}
		  else {
		  $chartData.='_';
		  }
		 }
	return $chartData;
}


function cpulog_dash_setup() {

	wp_add_dashboard_widget('admin_dashboard_3', 'CPU', 'cpulog_widget', 10 );

}

//Falls keine Tabelle vorhanden ist, wird eine erzeugt
$clsql='CREATE TABLE IF NOT EXISTS `wp_cpu_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `prozent` int(11) NOT NULL,
  `page` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=68 ;';
require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
dbDelta($clsql);



//Allgemeiner Aufruf
if($_GET['cpuload']=="cpu_del") $wpdb->query("DELETE FROM ".$wpdb->cl."");
add_action('wp_dashboard_setup', 'cpulog_dash_setup');

//Messungen werden bei jedem Hit auf die Website aufgerufen
add_action('wp_head', 'cpuload');


?>