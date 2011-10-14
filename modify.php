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
$args = arguments($argv);

if (isset($args['conf'])) $conf = $args['conf']; else $conf = "config.php";

require $conf;

try {
	$db = new PDO('sqlite:' . $db);
} catch (PDOException $e) {
	die('Unable to connect to the database.'. $e);
}


if($args['add']) {
	if(!get("uid", false)) {
		die("Please specify a uid\n");
	}
	try {
		$prep = $db->prepare("INSERT INTO servers (`uid`, `hostname`, `node`, `provider`, `disabled`, `time`) VALUES (?, ?, ?, ?, ?, ?)");
		$prep->execute(array($args['uid'], get("hostname", $args['uid']), get("node", "n/a"), get("provider", "n/a"), get("disabled", 0), time()));
		echo "Successfully added server\n";
	} catch(PDOException $e) {
		echo "Error occurred while adding server: $e\n";
	}
} else if($args['delete']) {
	if(!get("delete", false)) {
		die("Please specify a uid (--delete=uid)\n");
	}
	try {
		$prep = $db->prepare("DELETE FROM servers WHERE `uid` = ?");
		$prep->execute(array($args['delete']));
		echo "Successfuly deleted server\n";
	} catch(PDOException $e) {
		echo "Error occurred while removing server: $e\n";
	}
} else if($args['enable']) {
	if(!get("enable", false)) {
		die("Please specify a uid (--enable=<uid>)\n");
	}
	set_disabled($args['enable'], false);
} else if($args['disable']) {
	if(!get("disable", false)) {
		die("Please specify a uid (--disable=uid)\n");
	}
	set_disabled($args['disable'], true);
} else {
	die("No function defined\n");
}

function set_disabled($uid, $disable) {
	global $db;
	try {
		$prep = $db->prepare("UPDATE servers SET `disabled` = ? WHERE uid = ?");
		$prep->execute(array($disable ? 1 : 0, $uid));
		echo "$uid ".($disable ? "disabled" : "enabled").".\n";
	} catch(PDOException $e) {
		echo "Unable to enable/disable $uid: $e\n";
		return false;
	}
}

function get($name, $defaultval="") {
	global $args;
	if(empty($args[$name]) || $args[$name] === true) {
		return $defaultval;
	}
	return $args[$name];
}

function arguments($argv) {
	$out = array();
	foreach ($argv as $arg) {
		if (strpos($arg, '--') === 0) {
			$compspec = explode('=', $arg);
			$key = str_replace('--', '', array_shift($compspec));
			if(!strpos($arg, '=')) {
				$value = true;
			} else {
				$value = join('=', $compspec);
			}
			$out[$key] = $value;
		} elseif (strpos($arg, '-') === 0) {
			$key = str_replace('-', '', $arg);
			if (!isset($out[$key])) $out[$key] = true;
		}
	}
	return $out;
}
?>