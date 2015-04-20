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

    var customIcons = {
      tweet: { icon: 'http://labs.google.com/ridefinder/images/mm_20_blue.png' }
    };

    var map, heatmap, intervalID;
    var infoWindow = new google.maps.InfoWindow;
    var realTime = true;
    var tweet_markers =[];
    var tweet_locations = [];
    var tweets_returned = [];

// Setup the server-side event source for receiving sentiment updates
    var source = new EventSource("sentiment_sse.php");
  
    source.addEventListener("sentiment", function(e) {
      document.getElementById("sentiment").innerHTML = e.data;
      console.log(e.data);
    }, false);

    source.addEventListener("open", function(e) {
      console.log("Connection was opened.");
    }, false);

    source.addEventListener("error", function(e) {
      console.log("Error - the connection was lost.");
    }, false);

    function load() {
      map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(39.8282, -98.58795),
        zoom: 2,
        mapTypeId: 'roadmap'
      });

      populateMap();
      intervalID = setInterval(populateMap, 5000);
    }

    function populateMap() {
      if (!realTime && tweet_markers.length > 0)
        return;

      var tweet_xml_url = "gen_tweet_xml.php?realtime=" + realTime;
      tweet_xml_url = tweet_xml_url + "&keyid=" + <?php Print($currentid); ?>;

      downloadUrl(tweet_xml_url, function(data) {
        if (realTime && tweets_returned.length > 10) {
          var num_to_remove = tweets_returned.shift();
          tweet_locations = tweet_locations.slice(num_to_remove);
        }

        var xml = data.responseXML;
        var pointsData = xml.documentElement.getElementsByTagName("marker");
        if (realTime) {
          tweets_returned.push(pointsData.length);
          buildTweetLocations(pointsData);

          // Setup the heatmap
          var pointArray = new google.maps.MVCArray(tweet_locations);
          var newHeatmap = new google.maps.visualization.HeatmapLayer({
            data: pointArray
          });

          newHeatmap.setMap(map);
          if (heatmap)
            heatmap.setMap(null);
          heatmap = newHeatmap;

        } else {
          plotMarkers(pointsData);
        }
        
        document.getElementById("tweet_count").innerHTML = "Displaying <b>" + getTweetCount() + " Tweets</b>";
      });
    }

    function removeMarkersFromMap() {
      for (var i = 0; i < tweet_markers.length; i++)
        tweet_markers[i].setMap(null);
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
        });
        bindInfoWindow(marker, map, infoWindow, html);
        tweet_markers.push(marker);
      }
    }

    function bindInfoWindow(marker, map, infoWindow, html) {
       google.maps.event.addListener(marker, 'click', function() {
         infoWindow.setContent(html);
         infoWindow.open(map, marker);
       });
     }

    function getTweetCount() {
      var count = 0;
      if (realTime) {
        for (var i = 0; i < tweets_returned.length; i++)
          count += tweets_returned[i];
      } else {
        count = tweet_markers.length;
      }
      return count;
    }

    function buildTweetLocations(pointsData) {
      for (var i = 0; i < pointsData.length; i++) {
        var pos = new google.maps.LatLng(
            parseFloat(pointsData[i].getAttribute("lat")), 
            parseFloat(pointsData[i].getAttribute("lng")));
        tweet_locations.push(pos);
      }
    }

    function toggleRealTime() {
      clearInterval(intervalID);
      realTime = !realTime;

      if (realTime) {
        removeMarkersFromMap();
        tweet_markers = [];
        intervalID = setInterval(populateMap, 5000);
      } else {
        if (heatmap) {
          heatmap.setMap(null);
          heatmap = null;
        }
      }
      populateMap();
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
    <div id="panel">
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
        <span id="tweet_count">Displaying <b>0 Tweets</b></span>
        <span> - </span>
        <span id="sentiment">Gathering Sentiment</span>
      </form>
      <button id="toggle" onclick="toggleRealTime()">Toggle Real-Time</button></span>
    </div>

    <div id="map"></div>
  </body>

<?php
$result->close();
$conn->close();
?>