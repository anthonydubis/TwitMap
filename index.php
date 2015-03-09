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
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>TwitMap</title>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=visualization"></script>
    <script type="text/javascript">

    var map, heatmap;
    var tweet_locations = [];
    var tweets_returned = [];
    function load() {
      map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(39.8282, -98.58795),
        zoom: 2,
        mapTypeId: 'roadmap'
      });

      populateMap();
      setInterval(populateMap, 5000);
    }

    function populateMap() {
      var tweet_xml_url = "gen_tweet_xml.php?keyid=" + <?php Print($currentid); ?>;

      downloadUrl(tweet_xml_url, function(data) {
        if (tweets_returned.length > 10) {
          var num_to_remove = tweets_returned.shift();
          tweet_locations = tweet_locations.slice(num_to_remove);
        }

        var xml = data.responseXML;
        var pointsData = xml.documentElement.getElementsByTagName("marker");
        tweets_returned.push(pointsData.length);
        buildData(pointsData);
        
        // Setup the heatmap
        var pointArray = new google.maps.MVCArray(tweet_locations);
        var newHeatmap = new google.maps.visualization.HeatmapLayer({
          data: pointArray
        });

        newHeatmap.setMap(map);
        if (heatmap)
          heatmap.setMap(null);
        heatmap = newHeatmap;
        document.getElementById("tweet_count").innerHTML = "Displaying <b>" + getTweetCount() + " Tweets</b>";
      });
    }

    function getTweetCount() {
      var count = 0;
      for (var i = 0; i < tweets_returned.length; i++)
        count += tweets_returned[i];
      return count;
    }

    function buildData(pointsData) {
      for (var i = 0; i < pointsData.length; i++) {
        var pos = new google.maps.LatLng(
            parseFloat(pointsData[i].getAttribute("lat")), 
            parseFloat(pointsData[i].getAttribute("lng")));
        tweet_locations.push(pos);
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
    <div id="keyword_selector">
      <p>
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
        </form>
      </p>
      <p id="tweet_count">Displaying <b>0 Tweets</b></p>
    </div>

    <div id="map"></div>
  </body>

<?php
$result->close();
$conn->close();
?>