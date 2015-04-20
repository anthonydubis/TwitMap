<?php

// Should this be require("aws.phar"); as seen on index.php
require 'aws.phar'; 
require("twitmap_includes.php");

// Create namespace alias
use Aws\Sns\MessageValidator\Message;
use Aws\Sns\MessageValidator\MessageValidator;
use Guzzle\Http\Client;

// Make sure the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die;
}

// Handle the message
try {
    // Create a message from the post data and validate its signature
    $message = Message::fromRawPostData();
    $validator = new MessageValidator();
    $validator->validate($message);
} catch (Exception $e) {
    // Pretend we're not here if the message is invalid
    http_response_code(404);
    die;
}
 
if ($message->get('Type') === 'SubscriptionConfirmation') {
    // Send a request to the SubscribeURL to complete subscription
    (new Client)->get($message->get('SubscribeURL'))->send();
} elseif ($message->get('Type') === 'Notification') {
	$conn = new mysqli($servername, $username, $password, $dbname, $port);
	if ($conn->connect_error) {
    	die("Connection failed: " . $conn->connect_error);
	} 

	$sql = "SELECT AVG(x.sentiment) AS avg FROM (SELECT sentiment FROM Tweets t WHERE t.sentiment != 0.0 ORDER BY t.id DESC LIMIT 100) AS x";
    $result = $conn->query($sql);
  	$row = $result->fetch_assoc();

  	$avg = $row['avg'];
	$sql2 = "UPDATE Sentiment set sentiment = '$avg'";
	$result2 = $conn->query($sql2);
	$result2->close();
	$conn->close();


	// $sql = "SELECT key_id FROM Keywords";
	// $result = $conn->query($sql);
	// while ($row = $result->fetch_assoc()) {
	// 	$text = '0.8';
	// 	$keyId = $row['key_id'];
	// 	$sql2 = "UPDATE Keywords SET sentiment = '$text' WHERE key_id = '$keyId'";
 //  		$result2 = $conn->query($sql2);
	// 	$result2->close();
	// }
	// $result->close();
	// $conn->close();



	// $sql = "SELECT key_id FROM Keywords";
	// $result = $conn->query($sql);
	// while ($row = $result->fetch_assoc()) {
	// 	$keyId = $row['key_id'];
	// 	$sql2 = "SELECT AVG(x.sentiment) AS avg FROM (SELECT sentiment FROM Tweets t WHERE t.key_id == '$keyId' AND t.sentiment != 0.0 ORDER BY t.id DESC LIMIT 100) AS x";
 //  		$result2 = $conn->query($sql2);
 //  		$row2 = $result2->fetch_assoc();

	// 	if ($row2) {
	// 	  	if (isset($row2['avg'])) {
	// 	  		$avg = $row2['avg'];
	// 	  		$sql3 = "UPDATE Keywords SET sentiment = '$avg' WHERE key_id = '$keyId'";
	// 	  		$result3 = $conn->query($sql3);
	// 	  		$result3->close();
	// 		} 
	// 	} 
	// 	$result2->close();
	// }

	// $result->close();
	// $conn->close();
}

?>