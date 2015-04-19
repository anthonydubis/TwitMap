<?php

header("Content-Type: text/event-stream");
// header("Cache-Control: no-cache");
// header("Connection: keep-alive");

$counter = rand(1,10);
while (1) {
  // Every second, send a "ping" event.
  echo "event: sentiment\n";

  $curDate = date(DATE_ISO8601);
  $counter--;
  if (!$counter) {
  	echo 'data: This is a message at time ' . $curDate . "\n\n";
    $counter = rand(1, 10);
  } else {
	$curDate = date(DATE_ISO8601);
    echo 'data: {"time": "' . $curDate . '"}';
    echo "\n\n";
  }
  
  ob_flush();	
  flush();
  sleep(1);
}