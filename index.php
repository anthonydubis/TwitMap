<?php 
require("twitmap_includes.php"); 
$currentid = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $currentid = $_POST['keyword_id'];
}
?>

<!DOCTYPE html >
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>PHP/MySQL & Google Maps Example</title>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js"></script>
    <script type="text/javascript">
    //<![CDATA[

    var customIcons = {
      tweet: {
        icon: 'http://labs.google.com/ridefinder/images/mm_20_blue.png'
      },
      bar: {
        icon: 'http://labs.google.com/ridefinder/images/mm_20_red.png'
      }
    };

    function load() {
      var map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(47.6145, -122.3418),
        zoom: 2,
        mapTypeId: 'roadmap'
      });
      var infoWindow = new google.maps.InfoWindow;

      // Change this depending on the name of your PHP file
      var tweet_xml_url = "gen_tweet_xml.php?keyid=";
      var js_key_id = <?php Print($currentid); ?>;
      var urlWithGet = tweet_xml_url.concat(js_key_id);
      downloadUrl(urlWithGet, function(data) {
        var xml = data.responseXML;
        var markers = xml.documentElement.getElementsByTagName("marker");
        for (var i = 0; i < markers.length; i++) {
          var t_id = markers[i].getAttribute("tweetID");
          var k_id = markers[i].getAttribute("keywordID");
          var point = new google.maps.LatLng(
              parseFloat(markers[i].getAttribute("lat")),
              parseFloat(markers[i].getAttribute("lng")));
          var html = "<b> TweetID: " + t_id + "</b><br/>";
          var icon = customIcons["Tweet"] || {};

          var marker = new google.maps.Marker({
            map: map,
            position: point,
            icon: icon.icon
          });
          bindInfoWindow(marker, map, infoWindow, html);
        }
      });
    }

    function bindInfoWindow(marker, map, infoWindow, html) {
      google.maps.event.addListener(marker, 'click', function() {
        infoWindow.setContent(html);
        infoWindow.open(map, marker);
      });
    }

    function downloadUrl(url, callback) {
      var request = window.ActiveXObject ?
          new ActiveXObject('Microsoft.XMLHTTP') :
          new XMLHttpRequest;

      request.onreadystatechange = function() {
        if (request.readyState == 4) {
          request.onreadystatechange = doNothing;
          callback(request, request.status);
        }
      };

      request.open('GET', url, true);
      request.send(null);
    }

    function doNothing() {}

    //]]>

  </script>

  </head>

<?php

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Get the tweets
$sql = "SELECT * FROM Keywords ORDER BY keyword ASC";
$result = $conn->query($sql);

?>

  <body onload="load()">
    <div id="form" style="width: 1000px; height: 10px"></div>
      <form action="" method="post">
        Keyword: <select name="keyword_id">
          <option value=0 <?php if ($currentid == 0) echo " selected=\"selected\""; ?>>All</option>
          <?php
          while ($row = $result->fetch_assoc()) {
            $key_id = $row['key_id'];
            if ($key_id == $currentid)
              echo "<option value=\"$key_id\" selected=\"selected\">".$row["keyword"]."</option>";
            else
              echo "<option value=\"$key_id\">".$row["keyword"]."</option>";
          }
          ?>
        </select>
        <input type="submit" value="Map It">
        <br><br>
    <div id="map" style="width: 1000px; height: 600px"></div>
  </body>

<?php
$result->close();
$conn->close();
?>