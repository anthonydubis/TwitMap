<?php

require("twitmap_includes.php"); 

header("Content-Type: text/event-stream");


// // header("Cache-Control: no-cache");
// // header("Connection: keep-alive");

// Establish the connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

while (1) {
  // Every second, send a "ping" event.
  echo "event: sentiment\n";

  // Get the tweets
  $sql = "SELECT AVG(x.sentiment) AS avg FROM (SELECT sentiment FROM Tweets t WHERE t.sentiment != 0.0 ORDER BY t.id DESC LIMIT 100) AS x";
  $result = $conn->query($sql);
  $row = $result->fetch_assoc();
  if ($row) {
  	if (isset($row['avg'])) {
  		if ($row['avg'] >= 0.0) {
  			echo 'data: Positive: ' .$row['avg']. "\n\n";
  		} else {
  			echo 'data: Negative: ' .$row['avg']. "\n\n";
  		}
		echo 'data: ' .$row['avg']. "\n\n";
	} else {
		echo "data: Gathering Sentiment...\n\n";
	}
  } else {
  	echo "data: Gathering Sentiment...\n\n";
  } 
  
  ob_flush();	
  flush();
  sleep(1);
}

?>