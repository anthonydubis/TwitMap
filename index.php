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
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=visualization"></script>
    <script type="text/javascript">

    var map, heatmap;
    var current_points = [];
    var heatmaps = [];
    function load() {
      map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(47.6145, -122.3418),
        zoom: 2,
        mapTypeId: 'roadmap'
      });

      populateMap();
      setInterval(populateMap, 5000);
    }

    function populateMap() {
      var tweet_xml_url = "gen_tweet_xml.php?keyid=" + <?php Print($currentid); ?>;

      downloadUrl(tweet_xml_url, function(data) {
        if (heatmap)
          heatmaps.push(heatmap);
        if (heatmaps.length > 5) {
          console.log("Deleting a heatmap");
          var oldHeatmap = heatmaps.shift();
          oldHeatmap.setMap(null);
        }

        var xml = data.responseXML;
        var pointsData = xml.documentElement.getElementsByTagName("marker");
        console.log("Number of returned points: " + pointsData.length);
        buildData(pointsData);
        
        // Setup the heatmap
        var pointArray = new google.maps.MVCArray(current_points);
        heatmap = new google.maps.visualization.HeatmapLayer({
          data: pointArray
        });
        current_points = [];

        heatmap.setMap(map);
      });
    }

    function buildData(pointsData) {
      for (var i = 0; i < pointsData.length; i++) {
        var pos = new google.maps.LatLng(
            parseFloat(pointsData[i].getAttribute("lat")), 
            parseFloat(pointsData[i].getAttribute("lng")));
        current_points.push(pos);
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