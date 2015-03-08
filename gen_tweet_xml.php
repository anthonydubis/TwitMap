<?php
require("twitmap_includes.php");

// Set the last tweet ID you returned and the current keyID
session_start();
if (!isset($_SESSION["lastTweetID"])) {
    $_SESSION["lastTweetID"] = 0;
}

if (!isset($_SESSION["lastKeyID"])) {
	$_SESSION["lastKeyID"] = $_GET["keyid"];
} else {
	$keyID = $_GET["keyid"];
	if ($keyID != $_SESSION["lastKeyID"]) {
		$_SESSION["lastKeyID"] = $keyID;
		$_SESSION["lastTweetID"] = 0;
	}
}

// Start XML file and create parent node
$dom = new DOMDocument("1.0");
$node = $dom->createElement("markers");
$parnode = $dom->appendChild($node);

// Establish the connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Get the tweets
$sql = "SELECT * FROM Tweets WHERE id > ".$_SESSION["lastTweetID"];
if ($_SESSION["lastKeyID"] != 0) {
	$sql .= " AND key_id = ".$_SESSION["lastKeyID"];
}
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

  	$_SESSION["lastTweetID"] = max($row['id'], $_SESSION["lastTweetID"]);
}

echo $dom->saveXML();
   
$result->close();
$conn->close();

?>