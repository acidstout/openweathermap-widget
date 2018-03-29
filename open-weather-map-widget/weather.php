<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Plugin Name:   OpenWeatherMap.org Weather Widget
 * Plugin URI:    https://www.rekow.ch
 * Description:   A weather widget featuring data provided by the OpenWeatherMap.org API.
 * Version:       1.2
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


define('OPEN_WEATHER_MAP_PLUGIN_NAME', 'open-weather-map-widget');
define('OPEN_WEATHER_MAP_PLUGIN_DIR', plugins_url('', __FILE__));

$minified = (defined('WP_DEBUG') && WP_DEBUG === true) ? '' : '.min';



include_once 'include/openweathermap.class.php';
use OpenWeatherMap\Weather;

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
		
		$location 		 = isset($instance['location']) ? $instance['location'] : false;
		$use_geolocation = isset($instance['use_geolocation']) ? $instance['use_geolocation'] : false;
		$owm_api_key	 = isset($instance['openweathermap_api_key']) ? $instance['openweathermap_api_key'] : '';
		$override_title	 = isset($instance['override_title']) ? $instance['override_title'] : false;
		$units 			 = isset($instance['units']) ? $instance['units'] : false;
		$forecast_days 	 = isset($instance['forecast_days']) ? $instance['forecast_days'] : false;
		$show_stats 	 = isset($instance['show_stats']) ? $instance['show_stats'] : 0;
		$show_link 		 = isset($instance['show_link']) ? $instance['show_link'] : 0;

		echo $before_widget;
		
		if ($use_geolocation) {?>
			<script type="text/javascript">
				var OpenWeatherMapWidget = '<?php echo OPEN_WEATHER_MAP_PLUGIN_DIR;?>/weather-ajax.php';
				var OpenWeatherMapWidgetData = 'geolocation=1&ajax=1&apikey=<?php echo $owm_api_key;
						?>&title=<?php echo $override_title;
						?>&units=<?php echo $units;
						?>&forecast=<?php echo $forecast_days;
						?>&stats=<?php echo $show_stats;
						?>&link=<?php echo $show_link;
						?>';
			</script>
			<div id="weather-geolocation"></div><?php
			wp_enqueue_script('weather_widget_script');
		} else {
			// Create a new Weather object
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
			
			// Get weather data.
			echo $weather->getWeatherData();
		}
		
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
		$instance['location']               = isset($new_instance['location']) ? trim(strip_tags($new_instance['location'])) : $old_instance['location'];
		$instance['use_geolocation']        = isset($new_instance['use_geolocation']) ? trim(strip_tags($new_instance['use_geolocation'])) : 0;
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
		global $minified;
		
		$location        = isset($instance['location']) ? esc_attr($instance['location']) : '';
		$use_geolocation = (isset($instance['use_geolocation']) && $instance['use_geolocation'] == 1) ? 1 : 0;
		$owm_api_key     = isset($instance['openweathermap_api_key']) ? esc_attr($instance['openweathermap_api_key']) : '';
		$override_title  = isset($instance['override_title']) ? esc_attr($instance['override_title']) : '';
		$units           = isset($instance['units']) ? esc_attr($instance['units']) : 'metric';
		$forecast_days   = isset($instance['forecast_days']) ? esc_attr($instance['forecast_days']) : 5;
		$show_stats      = (isset($instance['show_stats']) && $instance['show_stats'] == 1) ? 1 : 0;
		$show_link       = (isset($instance['show_link']) && $instance['show_link'] == 1) ? 1 : 0;
		
		$avail_forecasts = array(
				0 => __('Disabled', 'weather'),
				1 => '1 ' . __('day', 'weather'),
				2 => '2 ' . __('days', 'weather'),
				3 => '3 ' . __('days', 'weather'),
				4 => '4 ' . __('days', 'weather'),
				5 => '5 ' . __('days', 'weather')
		);
		
		$checked_imperial = '';
		$checked_metric = 'checked="checked"';
		
		if ($units == 'imperial') {
			$checked_imperial = 'checked="checked"';
			$checked_metric = '';
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('location');?>"><?php _e('Location:', 'weather');?></label><br/>
			<input class="use_geolocation" id="<?php echo $this->get_field_name('use_geolocation');?>" name="<?php echo $this->get_field_name('use_geolocation');?>" type="checkbox" value="1" <?php if ($use_geolocation) echo 'checked="checked"';?>/>
			<label class="use_geolocation" for="<?php echo $this->get_field_name('use_geolocation');?>"><?php _e('Automatic', 'weather')?></label>
			<input class="location" id="<?php echo $this->get_field_id('location');?>" name="<?php echo $this->get_field_name('location');?>" type="text" value="<?php echo $location;?>" placeholder="<?php _e('Enter city name', 'weather');?>" <?php if ($use_geolocation) echo 'disabled="disabled"';?>/>
			<br/>
		</p>
		<div class="location_notice"><a href="https://openweathermap.org/" target="_blank">Use this link to find your city.</a></div>

		<p>
			<label class="not_linked"><?php _e('Units:', 'weather');?></label> &nbsp;
			<input id="<?php echo $this->get_field_id('units');?>_f" name="<?php echo $this->get_field_name('units');?>" type="radio" value="imperial" <?php echo $checked_imperial;?>/><label for="<?php echo $this->get_field_id('units');?>_f">°F</label> &nbsp; &nbsp;
			<input id="<?php echo $this->get_field_id('units');?>_c" name="<?php echo $this->get_field_name('units');?>" type="radio" value="metric" <?php echo $checked_metric;?>/><label for="<?php echo $this->get_field_id('units');?>_c">°C</label>
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
			<input id="<?php echo $this->get_field_id('show_stats');?>" name="<?php echo $this->get_field_name('show_stats');?>" type="checkbox" value="1" <?php if($show_stats) echo ' checked="checked"';?>/>
			<label for="<?php echo $this->get_field_id('show_stats');?>"><?php _e('Show details', 'weather');?></label>
		</p>
		
		<p>
			<input id="<?php echo $this->get_field_id('show_link');?>" name="<?php echo $this->get_field_name('show_link');?>" type="checkbox" value="1" <?php if($show_link) echo ' checked="checked"';?>/>
			<label for="<?php echo $this->get_field_id('show_link');?>"><?php _e('Link to extended forecast', 'weather');?></label>
		</p>
		<link rel="stylesheet" type="text/css" href="<?php echo OPEN_WEATHER_MAP_PLUGIN_DIR;?>/css/options<?php echo $minified;?>.css"/>
		<script type="text/javascript" src="<?php echo OPEN_WEATHER_MAP_PLUGIN_DIR;?>/js/options<?php echo $minified;?>.js"></script>
		<?php 
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
 * Enqueue widget style
 * 
 * @return boolean
 */
function enqueue_weather_style() {
	global $minified;
	return wp_register_style('weather_widget_style', OPEN_WEATHER_MAP_PLUGIN_DIR . '/css/weather' . $minified . '.css');
}


/**
 * Enqueue widget javascript
 * 
 * @return boolean
 */
function enqueue_weather_script() {
	global $minified;
	return wp_register_script('weather_widget_script', OPEN_WEATHER_MAP_PLUGIN_DIR . '/js/weather' . $minified . '.js');
}


/**
 * Register widget
 * 
 * @return void
 */
function init_weather_plugin() {
	return register_widget("owm_weather_widget");
}


function register_settings() {
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
		register_setting(OPEN_WEATHER_MAP_PLUGIN_NAME, $setting);
	}
}


/**
 * Initialize actions etc.
 */
add_action('wp_enqueue_scripts', 'enqueue_weather_style');
add_action('wp_enqueue_scripts', 'enqueue_weather_script');
add_action('widgets_init', 'init_weather_plugin');
add_shortcode('weather', 'weather_shortcode');

if (is_admin()) {
	add_action('admin_init', 'register_settings');
}