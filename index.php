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
    <title>TwitMap</title>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js"></script>
    <script type="text/javascript">

    var customIcons = {
      tweet: { icon: 'http://labs.google.com/ridefinder/images/mm_20_blue.png' }
    };

    var map = null;
    var infoWindow = null;
    var current_markers = [];
    function load() {
      map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(47.6145, -122.3418),
        zoom: 2,
        mapTypeId: 'roadmap'
      });
      infoWindow = new google.maps.InfoWindow;

      populateMap();
      setInterval(populateMap, 5000);
    }

    function populateMap() {
      var tweet_xml_url = "gen_tweet_xml.php?keyid=" + <?php Print($currentid); ?>;

      downloadUrl(tweet_xml_url, function(data) {
        var xml = data.responseXML;
        var markers = xml.documentElement.getElementsByTagName("marker");
        console.log("Number of returned markers: " + markers.length);
        setAllMap(null);
        plotMarkers(markers);
      });
    }

    // Sets the map of all markers - removes markers if map is null
    function setAllMap(map) {
      for (var i = 0; i < current_markers.length; i++) {
        current_markers[i].setMap(map);
      }
      current_markers = [];
    }

    function plotMarkers(markers) {
      for (var i = 0; i < markers.length; i++) {
        var info = markers[i];
        var pos = new google.maps.LatLng(
            parseFloat(info.getAttribute("lat")), 
            parseFloat(info.getAttribute("lng")));
        var t_id = info.getAttribute("tweetID");
        var k_id = info.getAttribute("keywordID");
        var html = "<b> TweetID: " + t_id + "</b><br/>";
        var icon = customIcons["tweet"] || {};

        var marker = new google.maps.Marker({
          map: map,
          position: pos,
          icon: icon.icon
        });
        current_markers.push(marker);
      }
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