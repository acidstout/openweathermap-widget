# OpenWeatherMap.org Weather Widget
A weather widget for WordPress featuring data provided by the OpenWeatherMap.org API. See (https://openweathermap.org) for details.

## Description
The plugin parses data returned by the OpenWeatherMap.org API to display weather information of a given location. All weather data is cached at least for three hours, unless you use the geolocation feature (not cached).
Use as a widget or add a shortcode like this (e.g. in posts, ...):

`[weather location="London, UK" units="imperial" override_title="Weather" forecast_days="0" show_stats="false"]`


You can also use the city ID instead of the name. This is the recommended way to do it, because it's much more precise than the name:

`[weather location="2657896" units="metric" forecast_days="3" show_stats="0"]`


The provided API key is connected to a free account for demo purposes only. In order to not get banned due to heavy usage, you should use your own API key instead; either in the widget configuration or by using the shortcode:

`[weather location="2657896" openweathermap_api_key="<insert your API key here>"]`


Please note that I do not provide an empty index.php file with my WordPress plugins, because security by obscurity is poor man's choice instead of using a proper configuration.

## Settings
* Location: Enter something like Long Beach, CA or just Los Angeles. Alternatively enter the location ID as provided on the OpenWeatherMap.org website. To use the browser's geolocation capabilities enable the "automatic" option. This will disable the location input field.
* Units: °F or °C (default).
* Forecast: Shows forecast weather information for the configured number of days.
* Override location title: Change the title of the location.
* OpenWeatherMap API key: Provide your own API key here to circumvent limitations of my free account.
* Show details: Show things like humidity, wind, highs and lows etc.
* Link to extended forecast: Make the location clickable. Opens details page of the configured location in a new tab when clicked.

## Installation
1. Add plugin to the plugins folder.
2. Activate the plugin.
3. Use a shortcode or the widget.

## Privacy policy
This widget itself does not collect, store or analyze any personal data. However, by using the OpenWeatherMap.org API such data may be transferred to or requested by OpenWeatherMap.org in order to provide you weather information of the location information you provided. For details please see their privacy policy at https://openweathermap.org/privacy-policy

## License
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA. Further details on this license can be found [here](https://www.gnu.org/licenses/gpl-3.0.html).