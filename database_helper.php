<?php

$servername = "localhost"; //Usually 'localhost'
$dbname = ""; //Name of the database
$username = ""; //Username for the database
$password = ""; //Password for the database
$table_name = 'IMDbToYIFI'; //Name for the new table

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) die("Connection failed: " . mysqli_connect_error());
if(!table_exists($table_name, $conn)){
	echo "<ul><li>Table '$table_name' not found on DB '$dbname'. Creating it now...</li>";
	if(create_table($table_name, $conn)){
		echo "<li>Table '$table_name' created successfully! Please refresh this page.</li>";
	} else {
		echo "<li>Something went wrong while creating '$table_name'. Please refresh this page.</li>";
	}
	echo '</ul>';
	die();
}

function table_exists($table, $conn){
	$result = mysqli_query($conn,"SHOW TABLES LIKE '".$table."'");
	$count = mysqli_num_rows($result);
	return $count ==1;
}

function create_table($table, $conn){
	$sql = "CREATE TABLE ".$table." (
	id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
	imdb_id VARCHAR(20) NOT NULL,
	720p TEXT NOT NULL,
	1080p TEXT NOT NULL,
	last_check_unixtime BIGINT NOT NULL
	)";
	return mysqli_query($conn, $sql);
}

function store_torrent($imdb_id, $hd="", $full_hd=""){
	global $conn, $table_name;
	if(get_torrent($imdb_id))$sql = "UPDATE ".$table_name." SET imdb_id='$imdb_id', 720p='$hd', 1080p='$full_hd', last_check_unixtime='".time()."' WHERE imdb_id='$imdb_id'";
	else $sql = "INSERT INTO ".$table_name." (imdb_id, 720p, 1080p, last_check_unixtime) VALUES ('".$imdb_id."', '".$hd."', '".$full_hd."', '".time()."')";
	return mysqli_query($conn, $sql);
}

function get_torrent($imdb_id){
	global $conn, $table_name;
	$result = mysqli_query($conn,"SELECT * FROM ".$table_name." WHERE imdb_id = '".$imdb_id."'");
	while($row = mysqli_fetch_array($result))
	{
		$arr = array(
		        "imdb_id" => $row['imdb_id'],
		        "720p" => $row['720p'],
		        "1080p" => $row['1080p'],
		        "last_check_unixtime" => $row['last_check_unixtime']
		);
		return $arr;
	}
}
