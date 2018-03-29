<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

define('OPEN_WEATHER_MAP_PLUGIN_NAME', 'open-weather-map-widget');

$settings = array(
		'location',
		'use_geolocation',
		'openweathermap_api_key',
		'override_title',
		'units',
		'forecast_days',
		'show_stats',
		'show_link'
);

foreach ($settings as $setting) {
	unregister_setting(OPEN_WEATHER_MAP_PLUGIN_NAME, $setting);
}
