<?php
require("twitmap_includes.php");

// Set the last tweet ID you returned and the current keyID
$realTime = $_GET["realtime"];
$keyID = $_GET["keyid"];

session_start();
if (!isset($_SESSION["lastTweetID"])) {
    $_SESSION["lastTweetID"] = 0;
} 

if (!isset($_SESSION["lastKeyID"])) {
	$_SESSION["lastKeyID"] = $keyID;
} else {
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
$sql = "SELECT * FROM Tweets";

// Fetch from last tweet ID if working in real-time
if ($realTime == "true") 
	$sql .= " WHERE id > ".$_SESSION["lastTweetID"];

// Filter by a key id if a keyword is selected
if ($_SESSION["lastKeyID"] != "0") {
	if ($realTime == "true")
		$sql .= " AND key_id = ".$_SESSION["lastKeyID"];
	else
		$sql .= " WHERE key_id = ".$_SESSION["lastKeyID"];
}

// Limit the returned results if real-time and it's the first fetch
if (($realTime == "true") && ($_SESSION["lastTweetID"] == "0"))
	$sql .= " ORDER BY id DESC LIMIT 300";

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

  	if ($realTime == "true")
  		$_SESSION["lastTweetID"] = max($row['id'], $_SESSION["lastTweetID"]);
}

echo $dom->saveXML();
   
$result->close();
$conn->close();

?>