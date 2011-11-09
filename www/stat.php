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
    
    Credits to DimeCadmium for the base of this file
*/

require '../config.php';
require 'Net/Ping.php';

try {
	$db = new PDO('sqlite:' . $db);
} catch (PDOException $e) {
	die('Unable to connect to the database.'. $e);
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = file_get_contents("php://input");
$result = json_decode($result, true);

$query = $db->prepare("SELECT COUNT(*) AS count FROM servers WHERE uid = ? AND key = ?");
$query->execute(array($result['uid'], $result['key']));
$res = $query->fetch(PDO::FETCH_ASSOC);
if(!$res || $res['count'] == 0) {
	die("unauthorized\n");
}
	
updateserver(1, $result['uplo']['uptime'], $result['ram']['total'], $result['ram']['used'], $result['ram']['free'], $result['ram']['bufcac'], $result['disk']['total']['total'], $result['disk']['total']['used'], $result['disk']['total']['avail'], $result['uplo']['load1'], $result['uplo']['load5'], $result['uplo']['load15'], $result['uid']);

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
