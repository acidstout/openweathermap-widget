=== OpenWeatherMap.org Weather Widget ===
Contributors: Nils Rekow
Tags: weather, forecast, responsive, openweathermap, widgets, sidebar
Requires at least: 4.x
Tested up to: 4.9.4
Stable 1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
The plugin parses data from the OpenWeatherMap.org API to display weather information of a given location. All weather data is cached at least for three hours.
Use as a widget or add a shortcode like this (e.g. in posts, ...):
`[weather location="London, UK" units="imperial" override_title="Weather" forecast_days="0" show_stats="false"]`

You can also use the city ID instead of the name. This is the recommended way to do it, because it's much more precise than the name:
`[weather location="2657896" units="metric" forecast_days="3" show_stats="0"]`

The provided API key is connected to a free account for demo purposes only. In order to not get banned due to heavy usage, you should use your own API key instead; either in the widget configuration or by using the shortcode:
`[weather location="2657896" openweathermap_api_key="<insert your API key here>"]`

Settings:
* Location: Enter something like Long Beach, CA or just Los Angeles. Alternatively just enter the location ID as provided on the OpenWeatherMap.org website.
* Units: °F or °C (default).
* Forecast: Shows forecast weather information for the configured number of days.
* Override location title: Change the title of the location.
* OpenWeatherMap API key: Provide your own API key here to circumvent limitations of my free account.
* Show details: Show things like humidity, wind, highs and lows etc.
* Link to extended forecast: Make the location clickable. Opens details page of the configured location in a new tab when clicked.

== Installation ==
1. Add plugin to the plugins folder.
2. Activate the plugin.
3. Use a shortcode or the widget.
