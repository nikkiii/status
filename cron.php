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

	if (isset($argv[1])) $conf = $argv[1]; else $conf = "config.php";
	require $conf;
	require 'Net/Ping.php';

	try {
		$db = new PDO('sqlite:' . $db);
	} catch (PDOException $e) {
		die('Unable to connect to the database.'. $e);
	}

	$dbs = $db->prepare("SELECT * FROM servers WHERE disabled = 0 AND key = 0");
	$result = $dbs->execute();
	if ($result) {
		$ra = $dbs->fetchAll(PDO::FETCH_ASSOC);
		foreach ($ra as $i => $row) {
			$fp = @fsockopen($row['hostname'], $port, $errno, $errstr, 5);
			if (!$fp) {
				$ping = Net_Ping::factory();
				$ping->setArgs(array('count' => 8));
				$pr = $ping->ping($row['hostname']);
				if ($pr->_loss == "0") {
					updateserver(1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $row['uid']);
				} else {
					updateserver(0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $row['uid']);
				}
			} else {
				$result = fgets($fp, 2048);
				@fclose($fp);
				$result = json_decode($result, true);
				updateserver(1, $result['uplo']['uptime'], $result['ram']['total'], $result['ram']['used'], $result['ram']['free'], $result['ram']['bufcac'], $result['disk']['total']['total'], $result['disk']['total']['used'], $result['disk']['total']['avail'], $result['uplo']['load1'], $result['uplo']['load5'], $result['uplo']['load15'], $row['uid']);
			}
		}
	} else {
		echo "Found no servers...\n";
	}

	function updateserver($status, $uptime, $mtotal, $mused, $mfree, $mbuffers, $disktotal, $diskused, $diskfree, $load1, $load5, $load15, $uid) {
		global $db;
		try {
			$dbs = $db->prepare('UPDATE servers SET time = ?, status = ?, uptime = ?, mtotal = ?, mused = ?, mfree = ?, mbuffers = ?, disktotal = ?, diskused = ?, diskfree = ?, load1 = ?, load5 = ?, load15 = ? WHERE uid = ?');
			$dbs->execute(array(time(), $status, $uptime, $mtotal, $mused, $mfree, $mbuffers, $disktotal, $diskused, $diskfree, $load1, $load5, $load15, $uid));
		} catch (PDOException $e) {
			echo $e;
			die('Something broke!');
		}
	}

?>

