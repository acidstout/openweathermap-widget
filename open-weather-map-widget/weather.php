<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Plugin Name:   OpenWeatherMap.org Weather Widget
 * Plugin URI:    https://www.rekow.ch
 * Description:   A weather widget featuring data provided by the OpenWeatherMap.org API.
 * Version:       1.0
 * Author:        Nils Rekow
 * Author URI:    https://www.rekow.ch
 * License:
 * -----------------------------------------------------------------------------
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 3 of the License, or (at your option) any later
 * version. This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA
 * -----------------------------------------------------------------------------
 */


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
	private $_city_id        = '';
	private $_slug           = '';
	private $_location       = false;
	private $_units          = 'metric';
	private $_override_title = false;
	private $_days_to_show   = 5;
	private $_show_stats     = 0;
	private $_show_link      = 0;
	private $_weather_data   = array();
	private	$_windSpeedUnit = 'km/h';
	
	
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
		isset($atts['location'])       ? $this->_location       = $atts['location'] : null;
		isset($atts['override_title']) ? $this->_override_title = $atts['override_title'] : null;
		isset($atts['forecast_days'])  ? $this->_days_to_show   = $atts['forecast_days'] : null;
		isset($atts['show_stats'])     ? $this->_show_stats     = $atts['show_stats'] : null;
		isset($atts['show_link'])      ? $this->_show_link      = $atts['show_link'] : null;
		
		// Get weather data.
		$this->_weather_data = $this->_weather_logic();
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
		if (Weather::_DEBUG) {
			error_log('Skipping transient city ID.', 0);
		} else {
			$id = get_transient($transient);
		
			if ($id) {
				return $id;
			}
		}

		if (Weather::_DEBUG) {
			error_log('Updating city ID. ' . $url, 0);
		}
		
		// Get fresh data (e.g. city ID).
		$content = file_get_contents($url);
		
		if ($content) {
			$data = json_decode($content);
			
			if (Weather::_DEBUG_JSON) {
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
		
		// Fetched cached data.
		if (Weather::_DEBUG) {
			error_log('Skipping transient weather data.', 0);
		} else {
			$data = get_transient($transient);
		}
		
		
		// Get fresh data (e.g. todays weather).
		if (!isset($data['today'])) {
			$url = $this->_api_urls['today'] . $this->_api_key . '&id=' . $this->_city_id . '&units=' . $this->_units;
			
			if (Weather::_DEBUG) {
				error_log('Updating todays weather data. ' . $url, 0);
			}
			
			$content = file_get_contents($url);
			
			if ($content) {
				$data['today'] = json_decode($content);
				
				if (Weather::_DEBUG_JSON) {
					error_log("Call to update weather data returned: \n" . print_r($data['today'], true), 0);
				}
			}
		}
		
		
		// Get weather forecast.
		if (!isset($data['forecast']->list) && $this->_days_to_show > 0) {
			$url = $this->_api_urls['forecast'] . $this->_api_key . '&id=' . $this->_city_id . '&units=' . $this->_units . '&mode=daily_compact';
			
			if (Weather::_DEBUG) {
				error_log('Updating forecast for the next ' . $this->_days_to_show . ' days. ' . $url, 0);
			}
			
			$content = file_get_contents($url);

			if ($content) {
				$data['forecast'] = json_decode($content);
				
				if (Weather::_DEBUG_JSON) {
					error_log("Call to update forecast returned: \n" . print_r($data['forecast'], true), 0);
				}
			}
		}
		
		// Cache weather data for 3hrs and return the data.
		if (isset($data) && isset($data['today'])) {
			set_transient($transient, $data, 10800);
			return $data;
		}
		
		return false;
	}
	
	
	/**
	 * Main routine to handle all the stuff.
	 * 
	 * @return boolean|string
	 */
	private function _weather_logic() {
		$output = '';
		$wind_direction = '';
		$units_display = ($this->_units == "metric") ? __('C', 'weather') : __('F', 'weather');

		if (!$this->_location) {
			return $this->_weather_error(__('City not set.', 'weather'));
		}
		
		
		// Lookup the city ID by its configured name.
		$this->_slug = sanitize_title($this->_location);
		$this->_city_id = $this->_location;
		
		if (!is_numeric($this->_location)) {
			$this->_city_id = $this->_getCityId();
		}
		

		// Throw an error in case $city_id still contains "invalid" data.
		if (!$this->_city_id) {
			return $this->_weather_error( __('City could not be found', 'weather') );
		}

		
		// Try to fetch weather data according to configured settings.
		$this->_weather_data = $this->_getWeatherData();
		

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
		
		
		// Override location name?
		$header_title = ($this->_override_title) ? $this->_override_title : $today->name;
		
		
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
			$forecast = $this->_weather_data['forecast'];
			$day_of_week = '';
			$day_of_week_prev = '';
			
			foreach((array)$forecast->list as $forecast) {
				$day_of_week = date('D', $forecast->dt);
				
				if ($dt_today >= date('Ymd', $forecast->dt)) {
					continue;
				}
				
				if ($day_of_week == $day_of_week_prev && !empty($day_of_week_prev)) {
					continue;
				}
				
				if (date('Hi', $forecast->dt) != '1200') {
					continue;
				}

				$output .= '
					<div class="weather-forecast-day" title="' . $forecast->weather[0]->description . '">
						<div><img src="' . $this->_weather_icon_urls['small'] . $forecast->weather[0]->icon . '.png" alt=""/></div>
						<div class="weather-forecast-day-temp">' . number_format($forecast->main->temp, 1) . ' °' . $units_display . '</div>
						<div class="weather-forecast-day-abbr">' . $day_of_week . '</div>
					</div>';
				
				if ($day_count == $this->_days_to_show) {
					break;
				}
				
				$day_of_week_prev = $day_of_week;
				$day_count++;
			}
			
			$output .= '</div>';
		}
		
		$output .= '</div>';
		return $output;
	}
}




/**
 * 
 * Weather widget class
 * 
 * @author nrekow
 *
 */
class owm_weather_widget extends WP_Widget {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$widget_ops = array('classname' => 'owm_weather_widget', 'description' => __('A widget that displays weather information based on the OpenWeatherMap.org API.', 'wp_widget_plugin'));
		$control_ops = array();
		parent::__construct(false, $name = __('OpenWeatherMap.org Weather Widget', 'owm_weather_widget'), $widget_ops, $control_ops);
	}

	
	/**
	 * The actual widget
	 * 
	 * {@inheritDoc}
	 * @see WP_Widget::widget()
	 */
	public function widget($args, $instance) {
		extract($args);
		
		wp_enqueue_style('weather_widget_style');
		
		$location 		= isset($instance['location']) ? $instance['location'] : false;
		$owm_api_key	= isset($instance['openweathermap_api_key']) ? $instance['openweathermap_api_key'] : '';
		$override_title	= isset($instance['override_title']) ? $instance['override_title'] : false;
		$units 			= isset($instance['units']) ? $instance['units'] : false;
		$forecast_days 	= isset($instance['forecast_days']) ? $instance['forecast_days'] : false;
		$show_stats 	= isset($instance['show_stats']) ? $instance['show_stats'] : 0;
		$show_link 		= isset($instance['show_link']) ? $instance['show_link'] : 0;

		echo $before_widget;
		$weather = new Weather(
			array(
				'location' => $location,
				'openweathermap_api_key' => $owm_api_key,
				'override_title' => $override_title,
				'units' => $units,
				'forecast_days' => $forecast_days,
				'show_stats' => $show_stats,
				'show_link' => $show_link
			)
		);
		
		echo $weather->getWeatherData();
		echo $after_widget;
	}
 
	
	/**
	 * Function to save changes in the widget's configuration
	 * 
	 * {@inheritDoc}
	 * @see WP_Widget::update()
	 */
	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['location']               = trim(strip_tags($new_instance['location']));
		$instance['openweathermap_api_key'] = trim(strip_tags($new_instance['openweathermap_api_key']));
		$instance['override_title']         = trim(strip_tags($new_instance['override_title']));
		$instance['units']                  = trim(strip_tags($new_instance['units']));
		$instance['forecast_days']          = trim(strip_tags($new_instance['forecast_days']));
		$instance['show_stats']             = isset($new_instance['show_stats']) ? trim(strip_tags($new_instance['show_stats'])) : 0;
		$instance['show_link']              = isset($new_instance['show_link']) ? trim(strip_tags($new_instance['show_link'])) : 0;
		
		return $instance;
	}
 
	
	/**
	 * Widget form in admin panel
	 * 
	 * {@inheritDoc}
	 * @see WP_Widget::form()
	 */
	public function form($instance) {	
		$location       = isset($instance['location']) ? esc_attr($instance['location']) : '';
		$owm_api_key    = isset($instance['openweathermap_api_key']) ? esc_attr($instance['openweathermap_api_key']) : '';
		$override_title = isset($instance['override_title']) ? esc_attr($instance['override_title']) : '';
		$units          = isset($instance['units']) ? esc_attr($instance['units']) : 'metric';
		$forecast_days  = isset($instance['forecast_days']) ? esc_attr($instance['forecast_days']) : 5;
		$show_stats     = (isset($instance['show_stats']) AND $instance['show_stats'] == 1) ? 1 : 0;
		$show_link      = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
		
		$avail_forecasts = array(
				0 => __('Disabled', 'weather'),
				1 => '1 ' . __('day', 'weather'),
				2 => '2 ' . __('days', 'weather'),
				3 => '3 ' . __('days', 'weather'),
				4 => '4 ' . __('days', 'weather'),
				5 => '5 ' . __('days', 'weather')
		);
		
		$checked_imperial = '';
		$checked_metric = 'checked';
		
		if ($units == 'imperial') {
			$checked_imperial = 'checked';
			$checked_metric = '';
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('location');?>"><?php _e('Location:', 'weather');?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('location');?>" name="<?php echo $this->get_field_name('location');?>" type="text" value="<?php echo $location;?>" placeholder="<?php _e('Enter city name', 'weather');?>"/><br/>
			<a href="https://openweathermap.org/" target="_blank">Use this link to find your city.</a>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('units');?>"><?php _e('Units:', 'weather');?></label> &nbsp;
			<input id="<?php echo $this->get_field_id('units');?>" name="<?php echo $this->get_field_name('units');?>" type="radio" value="imperial" <?php echo $checked_imperial;?>/>°F &nbsp; &nbsp;
			<input id="<?php echo $this->get_field_id('units');?>" name="<?php echo $this->get_field_name('units');?>" type="radio" value="metric" <?php echo $checked_metric;?>/>°C
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('forecast_days');?>"><?php _e('Forecast:', 'weather');?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id('forecast_days');?>" name="<?php echo $this->get_field_name('forecast_days');?>"><?php 
				foreach($avail_forecasts as $key => $value) {
					$selected = '';
					if ($key == $forecast_days) {
						$selected = ' selected';
					}
					?><option value="<?php echo $key;?>"<?php echo $selected;?>><?php echo $value;?></option><?php
				}?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('override_title');?>"><?php _e('Override location title:', 'weather');?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('override_title');?>" name="<?php echo $this->get_field_name('override_title');?>" type="text" value="<?php echo $override_title;?>" placeholder="<?php _e('Optional', 'weather');?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('openweathermap_api_key');?>"><?php _e('OpenWeatherMap API key:', 'weather');?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('openweathermap_api_key');?>" name="<?php echo $this->get_field_name('openweathermap_api_key');?>" type="text" value="<?php echo $owm_api_key;?>" placeholder="<?php _e('Optional', 'weather');?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('show_stats');?>"><?php _e('Show details:', 'weather');?></label>  &nbsp;
			<input id="<?php echo $this->get_field_id('show_stats');?>" name="<?php echo $this->get_field_name('show_stats');?>" type="checkbox" value="1" <?php if($show_stats) echo ' checked="checked"';?> />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('show_link');?>"><?php _e('Link to extended forecast:', 'weather');?></label>  &nbsp;
			<input id="<?php echo $this->get_field_id('show_link');?>" name="<?php echo $this->get_field_name('show_link');?>" type="checkbox" value="1" <?php if($show_link) echo ' checked="checked"';?> />
		</p><?php 
	}
}


/**
 * Shortcode wrapper
 * 
 * @param array $atts
 * @return array|string|boolean|mixed
 */
function weather_shortcode($atts) {
	$weather = new Weather($atts);
	return $weather->getWeatherData();
}


/**
 * Initialize actions etc.
 */
add_action('wp_enqueue_scripts', create_function('', 'return wp_register_style("weather_widget_style", plugin_dir_url( __FILE__ ) . "/css/weather.css");'));
add_action('widgets_init', create_function('', 'return register_widget("owm_weather_widget");'));
add_shortcode('weather', 'weather_shortcode');
