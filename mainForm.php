<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">

	<title>Postal information</title>
	<meta name="author" content="Lucas">

	<!-- <link rel="stylesheet" href="css/styles.css?v=1.0"> -->
</head>

<body>
	<form action="" method="post">
		Post Code
		<!-- 
			De we accept submission without '-' (ie: 1600022)?
			If yes we should modify the pattern to [0-9]{3}-?[0-9]{4}
		-->
		<input type="text" name="postalCode" 
			placeholder="160-0022" pattern="[0-9]{3}-[0-9]{4}" autofocus
			<?php
				// If the user entered a value we keep it.
				if(isset($_POST['postalCode']))
					{ echo 'value="' . $_POST['postalCode'] . '"'; } 
			?>
			>
		<input type="submit" name="submit" value="Submit">
	</form>
	
	<section id="place-name">
<?php
	/*
		In this section we wrote a location name. 
		The name is corresponding on the postalCodegiven. 
		
		We use GeoNames API, here the documentation link:
		https://www.geonames.org/export/web-services.html
	*/
	if (isset($_POST['submit'])) {
		if (isset($_POST['postalCode'] /* and valid */))
		{
			$postalCode = $_POST['postalCode'];
			$geonamesAPIUsername = 'lucasgeo';
			$url = 'http://api.geonames.org/postalCodeLookupJSON?postalcode=' . $postalCode
				. '&country=JP&username=' . $geonamesAPIUsername;
				
			$data = file_get_contents($url);
			$decodedData = json_decode($data);
			if ($decodedData != NULL)
			{
				$postalCodes = $decodedData->postalcodes;
				
				if(count($postalCodes) == 1)
				{
					$placeInfo = $postalCodes[0];
?>
		<h1>
			<?php echo $placeInfo->adminName1 ?>, 
			<?php echo $placeInfo->placeName ?>, 
			<?php echo $placeInfo->adminName2 ?> 
		</h1>
<?php 						
					$lat = $placeInfo->lat;
					$lng = $placeInfo->lng;
					
					$placeName = $placeInfo->placeName;
					
					// Keep adminName without the 'ToDouFuKen' part.
					$str = explode(' ', $placeInfo->adminName2);
					$cityName = $str[0];
					$str = explode(' ', $placeInfo->adminName1);
					$prefectureName = $str[0];
				}
				// endif: count($postalCodes) == 1
				// Do we add a message if postalCode is not valid ? (count = 0)
				// What if our postalCode request return many row ? (count > 1)
			}
		}
	}
?>
	</section>
	
	<section id="forecast">
		<h4>3-day forecast</h4>
		<div style="display:table; border-spacing:32px 0rem;">
<?php 
	/*
		In this section we weather forecast on 3 day. 
		We give today, tomorrow and day after tomorrow. 
		
		We use MetaWeather API, here the documentation link:
		https://www.metaweather.com/api/
		
		This section need a pre-set latitude and longitude, 
		this pre-set is done in the 'place-name' section;
	*/
	if(isset($lat) && isset($lng)) {
		// metweather use the WOEID (Where On Earth IDentifier).
		// We first use our latitude and longitude information to get it.
		$url = sprintf('https://www.metaweather.com/api/location/search/?lattlong=%s,%s', 
			$lat, $lng);
			
		$data = file_get_contents($url);
		$locations = json_decode($data);
		if ($locations != NULL && count($locations) > 0)
		{	
			$woeid = $locations[0]->woeid;
			
			// With the woeid we wil request the latest weather forecast. 
			// We request 3 times, from day+0 (today) to day+2. 
			for($i = 0; $i < 3; $i++)
			{
				// Japan time is GMT/UTC + 9
				$date = gmdate('Y/m/d', strtotime('+' . $i . ' day + 9 hour'));
				
				$url = sprintf('https://www.metaweather.com/api/location/%d/%s/', 
					$woeid, $date);
				
				$data = file_get_contents($url);
				$forecasts = json_decode($data);
				$latestForecast = $forecasts[0];
				
?>
			<div style="
				display:table-cell; 
				text-align:center; 
				border:1px solid black;
				padding:5px;">
			<img src="https://www.metaweather.com/static/img/weather/
				<?php echo $latestForecast->weather_state_abbr ?>.svg" 
				style="width:128px"><br />
			<?php echo $latestForecast->applicable_date 
				. ' ' 
				. date('D', strtotime($latestForecast->applicable_date));?><br />
			<h3><?php echo $latestForecast->weather_state_name ?></h3>
			Max: <?php echo intval($latestForecast->max_temp) ?>&deg; 
			Min: <?php echo intval($latestForecast->min_temp) ?>&deg;<br />
			</div>
<?php

			} // End of for loop.		
		}
	}
?>
		</div>
	</section>
	
	<section id="map" style="display:block; float:left; width:256px; margin:10px;">
		<h4>Map</h4>
<?php
	/*
		In this section we show an image of the map. 
		The map is centered on the postalCode localisation given. 
		
		We use Bind API, here the documentation link:
		https://docs.microsoft.com/en-us/bingmaps/rest-services/imagery/get-a-static-map
		
		This section need a pre-set latitude and longitude, 
		this pre-set is done in the 'place-name' section;
	*/

	// $lat & $lng are defined in the section "place-name". 
	// They are defined only if the user submit a valid postal code.
	if(isset($lat) && isset($lng)) {
		$imagerySet = 'Road';
		$centerPoint = $lat . ', ' . $lng;
		$zoomLevel = 14; // Min: 0; Max: 20
		$width = 256;
		$length = 256;
		$bingMapsAPIKey = "AidMSPZeufyg72iRYqYj1PmS9pIoZWtWbnoy9ulhH6S5wE9LDMplJD10Tqer1tGd";
		$url = sprintf('https://dev.virtualearth.net/REST/v1/Imagery/Map/%s/%s/%d?mapSize=%d,%d&key=%s', 
			$imagerySet, $centerPoint, $zoomLevel, 
			$width, $length, 
			$bingMapsAPIKey);
		
		echo sprintf('<img src="%s" alt="CenterPoint and ZoomLevel Static Map" data-linktype="relative-path">', 
			$url);
	}
?>
	</section>
	
	<section style="width:256px; display:block; float:left; margin:10px;">
	<h4>City Info (source: wiki)</h4>
	<p>
<?php
	/*
		In this section we write small text about the place. 
		The source is Wikipedia. 
		
		We use Dbpedia API, here the website link:
		https://wiki.dbpedia.org
		
		This section need a pre-set city and prefecture name, 
		this pre-set is done in the 'place-name' section;
	*/
	if (isset($cityName) && isset($prefectureName)) {
		
		// We first generated the link with a correct request at : http://dbpedia.org/sparql 
		$urlStart = 'https://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=SELECT+*+%0D%0AWHERE+%7B%0D%0A';
		$urlEnd = '+rdfs%3Acomment+%3Fcomment%0D%0A++FILTER+langMatches%28lang%28%3Fcomment%29%2C+%27en%27%29%0D%0A%7D&format=application%2Fsparql-results%2Bjson&CXML_redir_for_subjs=121&CXML_redir_for_hrefs=&timeout=100&debug=on&run=+Run+Query+';
		
		// We modify the part of our link to add a variable for the city name. 
		$urlSimpleCase = '+<http%3A%2F%2Fdbpedia.org%2Fresource%2F' 
			. $cityName . '>';
		// In some case the city name is ambigious (many result). 
		// In this case we will need to modify a little the request adding prefeture. 
		$urlAmbigiousCase = '+<http%3A%2F%2Fdbpedia.org%2Fresource%2F' 
			. $cityName . '%2C_'. $prefectureName . '>';
		
		
		$url = $urlStart . $urlSimpleCase . $urlEnd;
		
		$data = file_get_contents($url);
		$decodedData = json_decode($data);
		
		// If we have an empty table, then we are in the ambigious case. 
		// We retrieve one more time with our second url.
		if (count($decodedData->results->bindings)==0)
		{
			$url = $urlStart . $urlAmbigiousCase . $urlEnd;
			
			$data = file_get_contents($url);
			$decodedData = json_decode($data);
		}

		echo $decodedData->results->bindings[0]->comment->value;
		
	}
?>
	</p>
	</section>

	<!-- <script src="js/scripts.js"></script> -->
</body>
</html>