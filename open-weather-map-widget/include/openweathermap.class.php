<?php

namespace OpenWeatherMap;


/**
 * Workaround to handle AJAX requests.
 * Background: AJAX requests are executed outside of WordPress
 * and therefore cannot make use of WP functions.
 */
if (!function_exists('__')) {
	function __($text, $domain = 'default') {
		return $text;
	}
}


/**
 * 
 * Gets weather information from OpenWeatherMap.org and prepares it for use in a WordPress widget.
 * 
 * @author nrekow
 *
 */
class Weather {
	// Disable debug-mode by default.
	public const _DEBUG      = false;
	public const _DEBUG_JSON = false;
	
	// Set our OpenWeatherMap API key. This is a free account for demo purposes only. You may want to set your own key instead.
	private $_api_key = '4ce82f11d1c28e3ba0095fa17619c0a1';
	
	// Set OpenWeatherMap API endpoints.
	private $_api_urls = array(
			'find'     => 'https://api.openweathermap.org/data/2.5/find?',
			'today'    => 'https://api.openweathermap.org/data/2.5/weather?',
			'forecast' => 'https://api.openweathermap.org/data/2.5/forecast?'
	);
	
	private $_weather_icon_urls = array(
			'large' => '//openweathermap.org/themes/openweathermap/assets/vendor/owm/img/widgets/',
			'small' => '//openweathermap.org/img/w/'
	);
	
	// Set default settings.
	private $_city_id         = '';
	private $_slug            = '';
	private $_location        = 'Hamburg';
	private $_geolocation     = false;
	private $_units           = 'metric';
	private $_override_title  = false;
	private $_days_to_show    = 5;
	private $_show_stats      = false;
	private $_show_link       = false;
	private $_weather_data    = array();
	private	$_windSpeedUnit   = 'km/h';
	
	
	/**
	 * Constructor
	 *
	 * @param array $atts
	 */
	public function __construct($atts) {
		if (isset($atts['units']) && $atts['units'] == 'imperial') {
			$this->_units = 'imperial';
			$this->_windSpeedUnit = 'mph';
		}
		
		// Check for custom API key.
		(isset($atts['openweathermap_api_key']) && !empty($atts['openweathermap_api_key'])) ? $this->_api_key = $atts['openweathermap_api_key'] : null;
		$this->_api_key = 'appid=' . $this->_api_key;
		
		// Check other options.
		(isset($atts['location']) && !empty($atts['location'])) ? $this->_location = $atts['location'] : null;
		(isset($atts['geolocation'])) ? $this->_geolocation = $atts['geolocation'] : null;
		(isset($atts['override_title'])) ? $this->_override_title = $atts['override_title'] : null;
		(isset($atts['forecast_days'])) ? $this->_days_to_show = $atts['forecast_days'] : null;
		(isset($atts['show_stats'])) ? $this->_show_stats = $atts['show_stats'] : null;
		(isset($atts['show_link'])) ? $this->_show_link = $atts['show_link'] : null;
		
		// Get weather data.
		if ($this->_geolocation) {
			$this->_weather_data = $this->_getWeatherData();
		} else {
			$this->_weather_data = $this->_weather_logic();
		}
	}
	
	
	/**
	 * Function to return previously fetched weather data. Only used in the shortcode wrapper.
	 *
	 * @return array|mixed|boolean|string
	 */
	public function getWeatherData() {
		return $this->_weather_data;
	}
	
	
	/**
	 * Get a formatted representation of previously fetched weather data.
	 * 
	 * @param boolean $ajaxData
	 * @return string|boolean
	 */
	public function getFormattedWeatherData($weatherData = false) {
		if ($weatherData) {
			return $this->_weather_logic($weatherData);
		}
		
		return false;
	}
	
	/**
	 * In case of error, write message into log file.
	 * Also writes message to screen.
	 *
	 * @param boolean $msg
	 * @return boolean
	 */
	private function _weather_error($msg = false) {
		if (!$msg) {
			$msg = __('No weather information available', 'weather');
		}
		
		error_log(print_r($msg, true), 0);
		echo '<br/>' . $msg;
		
		return false;
	}
	
	
	/**
	 * Lookup city ID by its configured name and cache the data for one month.
	 *
	 * @return mixed|boolean
	 */
	private function _getCityId() {
		$transient = 'weather-cityid-' . $this->_slug;
		$url = $this->_api_urls['find'] . $this->_api_key . '&q=' . htmlspecialchars($this->_location, ENT_SUBSTITUTE);
		
		// Fetch cached data.
		if (self::_DEBUG) {
			error_log('Skipping transient city ID.', 0);
		} else {
			$id = get_transient($transient);
			
			if ($id) {
				return $id;
			}
		}
		
		if (self::_DEBUG) {
			error_log('Updating city ID. ' . $url, 0);
		}
		
		// Get fresh data (e.g. city ID).
		$content = file_get_contents($url);
		
		if ($content) {
			$data = json_decode($content);
			
			if (self::_DEBUG_JSON) {
				error_log("Call to get city ID returned: \n" . print_r($data, true), 0);
			}
			
			if (isset($data->message) && strpos($data->message, 'not found') !== false) {
				return false;
			}
			
			if (isset($data->list) && isset($data->list[0]->id)) {
				// Cache data for one month and return it.
				set_transient($transient, $data->list[0]->id, 2629743);
				return $data->list[0]->id;
			}
		}
		
		return false;
	}
	
	
	/**
	 * Fetch weather data
	 *
	 * @return mixed|boolean
	 */
	private function _getWeatherData() {
		$transient = 'weather-' . $this->_units . '-' . $this->_slug;
		
		// Fetched cached data. Contains current weather and forecast.
		if (self::_DEBUG) {
			error_log('Skipping transient weather data.', 0);
		} else if (!$this->_geolocation) {
			$data = get_transient($transient);
		}
		
		// Get fresh data (e.g. todays weather).
		if (!isset($data['today'])) {
			$url = $this->_api_urls['today'] . $this->_api_key .'&units=' . $this->_units;
			
			if ($this->_geolocation) {
				// The $_location contains the latitude and longitude of the current location encoded as URL parameters.
				$url .= $this->_location;
			} else {
				// Otherwise use the $_city_id.
				$url .= '&id=' . $this->_city_id;
			}
			
			if (self::_DEBUG) {
				error_log('Updating todays weather data. ' . $url, 0);
			}
			
			$content = file_get_contents($url);
			
			if ($content) {
				$data['today'] = json_decode($content);
				
				if (self::_DEBUG_JSON) {
					error_log("Call to update weather data returned: \n" . print_r($data['today'], true), 0);
				}
			}
		}
		
		
		// Get weather forecast.
		if (!isset($data['forecast']->list) && $this->_days_to_show > 0) {
			$url = $this->_api_urls['forecast'] . $this->_api_key . '&units=' . $this->_units . '&mode=daily_compact';
			
			if ($this->_geolocation) {
				$url .= $this->_location;
			} else {
				$url .= '&id=' . $this->_city_id;
			}
			
			if (self::_DEBUG) {
				error_log('Updating forecast for the next ' . $this->_days_to_show . ' days. ' . $url, 0);
			}
			
			$content = file_get_contents($url);
			
			if ($content) {
				$data['forecast'] = json_decode($content);
				
				if (self::_DEBUG_JSON) {
					error_log("Call to update forecast returned: \n" . print_r($data['forecast'], true), 0);
				}
			}
		}
		
		// Cache weather data for 3hrs and return the data.
		if (isset($data) && isset($data['today'])) {
			if (!$this->_geolocation) {
				set_transient($transient, $data, 10800);
			}
			return $data;
		}
		
		return false;
	}
	
	
	/**
	 * Main routine to handle all the stuff.
	 *
	 * @param boolean|array
	 * @return boolean|string
	 */
	private function _weather_logic($ajaxData = false) {
		$output = '';
		$wind_direction = '';
		
		// Decide which unit to use.
		$units_display = ($this->_units == "metric") ? __('C', 'weather') : __('F', 'weather');
		
		if (!$this->_geolocation) {
			// Return a message if no location has been set.
			if (!$this->_location) {
				return $this->_weather_error(__('City not set.', 'weather'));
			}
			
			
			// Lookup the city ID by its configured name.
			$this->_slug = sanitize_title($this->_location);
			$this->_city_id = $this->_location;
			
			if (!is_numeric($this->_city_id)) {
				$this->_city_id = $this->_getCityId();
			}
			
			
			// Throw an error in case $city_id still contains "invalid" data.
			if (!$this->_city_id) {
				return $this->_weather_error( __('City could not be found', 'weather') );
			}
		}
		
		
		// Try to fetch weather data according to configured settings.
		if ($ajaxData) {
			$this->_slug = 'geolocation';
			$this->_weather_data = $ajaxData;
			$this->_city_id = $ajaxData['today']->id;
		} else {
			$this->_weather_data = $this->_getWeatherData();
		}
		
		
		// No weather data could be fetched.
		if (!$this->_weather_data) {
			return $this->_weather_error();
		}
		
		
		// Todays temparatures
		$today 		= $this->_weather_data['today'];
		$today_temp = number_format($today->main->temp, 1);
		$today_high = number_format($today->main->temp_max, 1);
		$today_low 	= number_format($today->main->temp_min, 1);
		$feels		= $today->weather[0]->id;
		
		// Use configured city name (if it's not a city ID) instead of the name returned by the API call.
		// This will preserve accents in the city name.
		$header_title = (!is_numeric($this->_location) && !$ajaxData) ? $this->_location : $today->name;
		
		// Override location name?
		if ($this->_override_title) {
			$header_title = $this->_override_title;
		}
		
		// The OpenWeatherMap API returns wind speed in "m/s" when requesting metric units.
		// So, we need to convert it to "km/h".
		// When requesting the use of imperial units it properly returns "mph".
		if ($this->_units == 'metric') {
			$today->wind->speed = $today->wind->speed * 3.6;
		}
		
		$today->main->humidity = round($today->main->humidity, 1);
		$today->wind->speed = round($today->wind->speed, 1);
		
		$wind_label = array (
				__('N', 'weather'),
				__('NNE', 'weather'),
				__('NE', 'weather'),
				__('ENE', 'weather'),
				__('E', 'weather'),
				__('ESE', 'weather'),
				__('SE', 'weather'),
				__('SSE', 'weather'),
				__('S', 'weather'),
				__('SSW', 'weather'),
				__('SW', 'weather'),
				__('WSW', 'weather'),
				__('W', 'weather'),
				__('WNW', 'weather'),
				__('NW', 'weather'),
				__('NNW', 'weather')
		);
		
		if (isset($today->wind->deg)) {
			$wind_direction = $wind_label[ fmod((($today->wind->deg + 11) / 22.5), 16) ];
		}
		
		$show_stats_class = (!$this->_show_stats) ? 'without_stats' : '';
		
		
		// Prepare widget output
		$output .= '<div id="weather-' . $this->_slug . '" class="weather-wrap ' . $show_stats_class . '">';
		$output .= '<div class="weather-condition"><img src="' . $this->_weather_icon_urls['large'] . $today->weather[0]->icon. '.png" alt="' . $today->weather[0]->description . '" title="' . $today->weather[0]->description . '"/></div>';
		$output .= '<div class="weather-header">';
		
		if ($this->_show_link && $this->_city_id) {
			$output .= '<a href="http://openweathermap.org/city/' . $this->_city_id . '" target="_blank" title="' . __('Click for extended forecast', 'weather') . '">';
		}
		
		$output .= $header_title;
		
		if ($this->_show_link && $this->_city_id) {
			$output .= '</a>';
		}
		
		$output .= '<br/>' . $today_temp . ' °' . $units_display . '</div>';
		
		
		// Show weather details
		if ($this->_show_stats) {
			$output .= '
				<div class="weather-todays-stats">
					<div class="awe_desc">' . $today->weather[0]->description . '</div>
					<div class="awe_humidty">humidity: ' . $today->main->humidity . '% </div>
					<div class="awe_wind">wind: ' . $today->wind->speed . $this->_windSpeedUnit . ' ' . $wind_direction . '</div>
					<div class="awe_highlow">H: ' . $today_high . ' °' . $units_display . ' &bull; L: ' . $today_low . ' °' . $units_display . ' </div>
				</div>';
		}
		
		
		// Show forecast
		if ($this->_days_to_show > 0) {
			$output .= '<div class="weather-forecast days_' . $this->_days_to_show . '">';
			$day_count = 1;
			$dt_today = date('Ymd');
			$forecast = $this->_weather_data['forecast'];	// Contains the actual forecast data.
			$day_of_week = '';
			$day_of_week_prev = '';
			
			foreach((array)$forecast->list as $forecast) {
				// Get day of the week of the forecast data.
				$day_of_week = date('D', $forecast->dt);
				
				// Todays date is the date in the forecast, then skip.
				if ($dt_today >= date('Ymd', $forecast->dt)) {
					continue;
				}
				
				// Skip weather data if we already display that day.
				if ($day_of_week == $day_of_week_prev && !empty($day_of_week_prev)) {
					continue;
				}
				
				// Skip weather data if its time is not around noon. This adds some tolerance to the time range.
				if (date('Hi', $forecast->dt) < '1100' || date('Hi', $forecast->dt) > 1300) {
					continue;
				}
				
				// Add forecast entry to our output.
				$output .= '
					<div class="weather-forecast-day" title="' . $forecast->weather[0]->description . '">
						<div><img src="' . $this->_weather_icon_urls['small'] . $forecast->weather[0]->icon . '.png" alt=""/></div>
						<div class="weather-forecast-day-temp">' . number_format($forecast->main->temp, 1) . ' °' . $units_display . '</div>
						<div class="weather-forecast-day-abbr">' . $day_of_week . '</div>
					</div>';
				
				// Break the foreach-loop if the number of days to display is reached.
				if ($day_count == $this->_days_to_show) {
					break;
				}
				
				// Remember current day as the "previous" day.
				$day_of_week_prev = $day_of_week;
				
				// Count up.
				$day_count++;
			}
			
			$output .= '</div>';
		}
		
		$output .= '</div>';	// Close <div id="weather-' . $this->_slug . '" ...>
		return $output;
	}
}
