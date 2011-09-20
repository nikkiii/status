<?php
/*
This file is part of the status project.

    The status project is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    The status project is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with the status project.  If not, see <http://www.gnu.org/licenses/>.
*/
	require('../config.php');
	$start = microtime();
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
	header('Refresh: 60');
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Server statistics</title>
		<style type="text/css">
			body{font-family:Helvetica, Verdana;}h1{font-size:32px;}div#wrapper{width:95%;margin-left:auto;margin-right:auto;position:relative;text-align:center;}div.stats_container{position:relative;text-align:center;}div.clear{clear:both;font-size:small;}div.stats_status{font-size:12px;color:red;margin-top:2px;margin-bottom:10px;}table,td{border:1px solid #CCC;font-size:11.5px;margin:0 auto;}thead th,tbody th{background:url(data:image/gif;base64,R0lGODlhAQAfALMAAPLz9fn6/fv8/vX1+Pf5++/w8e3u7+rq6+rr7AAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAABAB8AAAQQ8MiDqjElgz06+UEYCGQZAQA7) repeat-x;color:#666;border:1px solid #CCC;padding:5px 10px;}tfoot td,tfoot th{border:1px solid #CCC;color:#666;padding:4px;}div.progress-container{border:1px solid #ccc;width:100px;text-align:left;background:#FFF;text-size:3px;float:left;margin:2px 5px 2px 0;padding:1px;}div.progress-container-percent{padding:2px;}div.progress-container > div{background-color:/*#ACE97C*/#00BFFF;height:12px;}.offline{background-color:#FFE6E6;}.online-but-no-data{background-color:#3F6;}.5pad{padding:5px;}.bartext{text-align:center;width:100px;}.loadavg{-moz-border-radius:5px;border-radius:5px;text-align:center;padding:2px;}a:link,a:active,a:visited{color:#666;text-decoration:underline;}
		</style>
	</head>
	<body>
		<a href="https://github.com/nikkiii/status"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://a248.e.akamai.net/assets.github.com/img/30f550e0d38ceb6ef5b81500c64d970b7fb0f028/687474703a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub"></a>
		<div id="wrapper">
			<h1>Server statistics</h1>
			<div class="stats_container" id="stats">
				<div class="stats_table" id="table">
		<table style="border: 1;">
		<thead>
			<tr>
				<th scope="col">#</th>
				<th scope="col">Unique ID</th>
				<th scope="col">Node</th>
				<th scope="col">Last Updated</th>
				<th scope="col">Uptime</th>
				<th scope="col">RAM</th>
				<th scope="col">Disk</th>
				<th scope="col">Load</th>
			</tr>
		</thead>
			<tfoot>

<?php
	try {
		$db = new PDO('sqlite:'. $db);
	} catch (PDOException $e) {
		error_log($_SERVER['SCRIPT_FILENAME'] .' - Unable to connect to the database: '. $e);
		die('Unable to connect to the database - please try again later.');
	}
	$dbs = $db->prepare('SELECT * FROM servers WHERE disabled = 0 ORDER BY provider ASC');
	$result = $dbs->execute();
	$i = 0;
	$provider = '';
	while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
		$i++;
		if ($row['provider'] != $provider) {
			echo '<tr><td colspan="8" style="text-align: left; vertical-align: middle; font-weight: bold; font-size: 10px; padding-left: 5px;">'. $row['provider'] .'</td></tr>';
			$provider = $row['provider'];
		}
	   	if ($row['status'] == "0") {
			echo '<tr style="text-align: center" class="offline">';
		} elseif ($row['uptime'] == "n/a") {
			echo '<tr style="text-align: center" class="online-but-no-data">';
		} else {
			echo '<tr style="text-align: center">';
		}
		echo '<td>'. $i .'</td>';
		echo '<td>'. $row['uid'] .'</td>';
		echo '<td>'. $row['node'] .'</td>';
		echo '<td>'. sec_human(time() - $row['time']) .'</td>';
		echo '<td>'. $row['uptime'] .'</td>';
		echo '<td class="5pad">';
		if(empty($row['mtotal'])) {
			echo "N/A";
		} else {
			$mp = ($row['mused']-$row['mbuffers'])/$row['mtotal']*100;
			$used = $row['mused'] - $row['mbuffers'];
			echo '<div class="progress-container"><div class="progress-container-percent" style="width:'. $mp .'%"><div class="bartext">'. $used .'/'. $row['mtotal'] .'MB</div></div></div></td>';
		}
		echo '</td>';
		echo '<td class="5pad">';
		if(isset($row['diskused'])) {
			$mp = ($row['diskused']/$row['disktotal'])*100;
			echo '<div class="progress-container"><div class="progress-container-percent" style="width:'. $mp .'%"><div class="bartext">'. format_kbytes($row['diskused']) .'/'. format_kbytes($row['disktotal']) .'GB</div></div></div>';
		} else {
			echo 'N/A';
		}
		echo '</td>';
		echo '<td class="5pad">';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load1']).'">'. sprintf('%.02f', $row['load1']) .'</span>&nbsp;';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load5']).'">'. sprintf('%.02f', $row['load5']) .'</span>&nbsp;';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load15']).'">'. sprintf('%.02f', $row['load15']) .'</span>&nbsp;';
		echo '</td>';
		echo '</tr>';
	}
	
	/* From http://www.php.net/manual/en/function.filesize.php#100097, removed bytes*/
	function format_kbytes($size) {
		return round($size/1024/1024, 2);
	}
	
	/* Thanks to 'vld' over at irc.frantech.ca for providing me with the following two functions! */
	function sec_human($sec) {
		if($sec < 60) { return $sec.'s'; }
		$tstring = '';
		$days  = floor($sec / 86400);
		$hrs   = floor(bcmod($sec, 86400) / 3600);
		$mins  = round(bcmod(bcmod($sec, 86400), 3600) / 60);
		if($days > 0) { $tstring = $days.'d '; }
		if($hrs  > 0) { $tstring .= $hrs.'h '; }
		if($mins > 0) { $tstring .= $mins.'m '; }
		return substr($tstring, 0, -1);
	}

	function gen_color($load) {
		$green = 0;
		$red = 3;
		$colors = array('00FF00', '11FF00', '22FF00', '33FF00', '44FF00', '55FF00', '66FF00', '77FF00', '88FF00', '99FF00', 'AAFF00', 'BBFF00', 'CCFF00', 'DDFF00', 'EEFF00', 'FFFF00', 'FFEE00', 'FFDD00', 'FFCC00', 'FFBB00', 'FFAA00', 'FF9900', 'FF8800', 'FF7700', 'FF6600', 'FF5500', 'FF4400', 'FF3300', 'FF2200', 'FF1100', 'FF0000');
		$count = count($colors)-1;
		$map = intval((($load - $green) * $count) / ($red - $green));
		if($map > $count) { $map = $count; }
		return $colors[$map];
	}
	$end = microtime();
?>
			</tfoot>
	</table>
				</div>
			</div>
			<div class="clear">Generated in <?php echo $end - $start; ?>s.</div>
		</div>
	</body>
</html>
