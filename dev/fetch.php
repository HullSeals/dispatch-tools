<?php
//UserSpice Required
require_once '../../users/init.php';
//fetch.php;
$db = include 'db.php';
$mysqli = new mysqli($db['server'], $db['user'], $db['pass'], "sealsudb", $db['port']);
	$sql = "SELECT seal_name, seal_id FROM staff
			WHERE (seal_name LIKE '%".$_GET['query']."%') AND seal_id <> 0 AND del_flag <> 1
      ORDER BY seal_name ASC
			LIMIT 10";

	$result = $mysqli->query($sql);

	$json = [];
	while($row = $result->fetch_assoc()){
	     $json[] = $row['seal_name'];
	}
	echo json_encode($json);
?>
