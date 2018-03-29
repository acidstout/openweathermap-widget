<?php
/**
 * Simple wrapper to handle AJAX calls if automatic geolocation is enabled.
 * 
 * @author nrekow
 */
include_once 'include/openweathermap.class.php';
use OpenWeatherMap\Weather;

if (isset($_REQUEST['ajax']) && !empty($_REQUEST['ajax'])) {
	$location = (isset($_REQUEST['lat']) && isset($_REQUEST['lon'])) ? '&lat=' . trim(strip_tags($_REQUEST['lat'])) . '&lon=' . trim(strip_tags($_REQUEST['lon'])) : null; 
	$apikey   = (isset($_REQUEST['apikey']))   ? trim(strip_tags($_REQUEST['apikey']))   : null;
	$title    = (isset($_REQUEST['title']))    ? trim(strip_tags($_REQUEST['title']))    : null;
	$units    = (isset($_REQUEST['units']))    ? trim(strip_tags($_REQUEST['units']))    : null;
	$forecast = (isset($_REQUEST['forecast'])) ? trim(strip_tags($_REQUEST['forecast'])) : null;
	$stats    = (isset($_REQUEST['stats']))    ? trim(strip_tags($_REQUEST['stats']))    : null;
	$link     = (isset($_REQUEST['link']))     ? trim(strip_tags($_REQUEST['link']))     : null;
	
	// Create a new Weather object
	$weather = new Weather(
			array(
					'location' => $location,
					'geolocation' => true,
					'openweathermap_api_key' => $apikey,
					'override_title' => $title,
					'units' => $units,
					'forecast_days' => $forecast,
					'show_stats' => $stats,
					'show_link' => $link
			)
	);
	
	// Get weather data.
	$weatherData = $weather->getWeatherData();
	
	if (is_array($weatherData)) {
		$weatherData = $weather->getFormattedWeatherData($weatherData);
	}
	echo $weatherData;
}

die();
