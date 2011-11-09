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

function gen_string($len = 5) {
	return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',$len)),0,$len);
}

if (isset($args['conf'])) $conf = $args['conf']; else $conf = "config.php";

require $conf;

if($args['help']) {
	echo "PHP Status Project 1.0 - $args[help]\n";
	if($args['help'] == 1) {
		$contents = "  Useful arguments:\n";
		$contents .= "\tadd - Add a server to the database\n";
		$contents .= "\tdelete=<uid> - Delete a server\n";
		$contents .= "\tenable=<uid> - Enable a server\n";
		$contents .= "\tdisable=<uid> - Disable a server\n";
		$contents .= "\tconvert=<oldfile> - Convert an old database (Does not keep stats, and you must move the old database since the new one will overwrite it)\n";
		echo $contents;
	} else if(in_array($args['help'], array("add", "delete", "enable", "disable", "convert"))) {
		switch($args['help']) {
			case "add": {
				echo "Adds a server to the database\n";
				echo "Possible fields: uid, os (0 = linux, 1 = windows), hostname, provider, node, disabled (0 or 1)\n";
				echo "Example usage: php modify.php --add --uid=<uid> --hostname=<hostname> --provider=<provider> --node=<node>\n";
				echo "To add a server using scrd, use \"--nokey\"\n";
				break;
			}
			case "delete": {
				echo "Deletes a server from the database\n";
				echo "Example usage: php modify.php --delete=<uid>\n";
				break;
			}
			case "enable": {
				echo "Enables a server\n";
				echo "Example usage: php modify.php --enable=<uid>\n";
				break;
			}
			case "disable": {
				echo "Disables a server\n";
				echo "Example usage: php modify.php --disable=<uid>\n";
				break;
			}
			case "convert": {
				echo "Converts an old database\n";
				echo "Note: You will need to move your old database to a new file, and then download the new database file before converting\n";
				echo "Example usage: php modify.php --convert=<old file>\n";
				echo "To use older servers with statsend, you must manually create a key, or remove them and re-add\n";
			}
		}
	}
	die();
}

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
		$key = get("nokey", false) ? false : gen_string(10);
		$prep = $db->prepare("INSERT INTO servers (`uid`, `key`, `os`, `hostname`, `node`, `provider`, `disabled`, `time`) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$prep->execute(array($args['uid'], ($key ? $key : 0), get("os", 0), get("hostname", $args['uid']), get("node", "n/a"), get("provider", "n/a"), get("disabled", 0), time()));
		echo "Successfully added server\n";
		if($key) {
			echo "Update your StatSend configuration file to include this key: $key\n";
		}
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
} else if($args['convert']) {
	if(file_exists($args['convert'])) {
		$old = new PDO('sqlite:' . $args['convert']);
		$q = $old->prepare("SELECT * FROM servers");
		$q->execute();
		while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
			$prep = $db->prepare("INSERT INTO servers (`uid`, `key`, `os`, `hostname`, `node`, `provider`, `disabled`, `time`) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$prep->execute(array($row['uid'], 0, 0, $row['hostname'], $row['node'], $row['provider'], $row['disabled'], $row['time']));
			echo "Server $row[uid] inserted\n";
		}
	} else {
		die("File $args[convert] does not exist!");
	}
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