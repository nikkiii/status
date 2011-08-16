<?php
	if (isset($argv[1])) $conf = $argv[1]; else $conf = "config.php";
	require $conf;
	require 'Net/Ping.php';

	try {
		$db = new PDO('sqlite:'. $db);
	} catch (PDOException $e) {
		die('Unable to connect to the database.'. $e);
	}

	$dbs = $db->prepare("SELECT * FROM servers WHERE disabled = 0");
	$result = $dbs->execute();
	if ($result) {
		$ra = $dbs->fetchAll(PDO::FETCH_ASSOC);
		foreach ($ra as $i => $row) {
			$fp = @fsockopen($row['hostname'], 12908, $errno, $errstr, 5);
			if (!$fp) {
				$ping = Net_Ping::factory();
				$ping->setArgs(array('count' => 8));
				$pr = $ping->ping($row['hostname']);
				if ($pr->_loss == "0") {
					updateserver(1, 'n/a', 'n/a', 'n/a', 'n/a', '0', 'n/a', 'n/a', 'n/a', $row['uid']);
					if ($row['status'] == '0') {
						mail($email, 'Server '. $row['uid'] .' is up!', 'Server '. $row['uid'] .' on node '. $row['node'] .' is up. That rocks!');
					}
				} else {
					updateserver(0, 'n/a', 'n/a', 'n/a', 'n/a', '0', 'n/a', 'n/a', 'n/a', $row['uid']);
					if ($row['status'] == '1') {
						mail($email, 'Server '. $row['uid'] .' is down', 'Server '. $row['uid'] .' on node '. $row['node'] .' is down. That sucks!');
					}
				}
			} else {
				$result = fgets($fp, 2048);
				@fclose($fp);
				$result = json_decode($result, true);
				updateserver(1, $result['uplo']['uptime'], $result['ram']['total'], $result['ram']['used'], $result['ram']['free'], $result['ram']['bufcac'], $result['uplo']['load1'], $result['uplo']['load5'], $result['uplo']['load15'], $row['uid']);
				if ($row['status'] == "0") {
					mail($email, 'Server '. $row['uid'] .' is up', 'Server '. $row['uid'] .' on node '. $row['node'] .' is up. That rocks!');
				}
			}
		}
	}

	function updateserver($status, $uptime, $mtotal, $mused, $mfree, $mbuffers, $load1, $load5, $load15, $uid) {
		global $db;
		try {
			$dbs = $db->prepare('UPDATE servers SET time = ?, status = ?, uptime = ?, mtotal = ?, mused = ?, mfree = ?, mbuffers = ?, load1 = ?, load5 = ?, load15 = ? WHERE uid = ?');
			$dbs->execute(array(time(), $status, $uptime, $mtotal, $mused, $mfree, $mbuffers, $load1, $load5, $load15, $uid));
		} catch (PDOException $e) {
			echo $e;
			die('Something broke!');
		}

	}

?>

