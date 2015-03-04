<?php
require("twitmap_includes.php");

// Start XML file and create parent node
$dom = new DOMDocument("1.0");
$node = $dom->createElement("markers");
$parnode = $dom->appendChild($node);
$keyid = $_GET["keyid"];

// Establish the connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Get the tweets
$sql = "SELECT * FROM Tweets";
if ($keyid != 0)
	$sql .= " WHERE key_id = ".$keyid;
$result = $conn->query($sql);


header("Content-type: text/xml");

// Iterate through the rows, adding XML nodes for each
while ($row = $result->fetch_assoc()) {
	// ADD to XML document node
	$node = $dom->createElement("marker");
	$newnode = $parnode->appendChild($node);

	$newnode->setAttribute('tweetID', $row['id']);
	$newnode->setAttribute('keywordID', $row['key_id']);
  	$newnode->setAttribute('lat', $row['lat']);
  	$newnode->setAttribute('lng', $row['lng']);
}

echo $dom->saveXML();
   
$result->close();
$conn->close();

?>