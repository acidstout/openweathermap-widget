/**
 * AJAX Handler
 * 
 * @author: nrekow
 * 
 */
var ajax = {};

/**
 * Tests browsers AJAX capability and initializes XHR.
 * 
 * @returns object
 */
ajax.init = function() {
	if (typeof XMLHttpRequest !== 'undefined') {
		return new XMLHttpRequest();
	}

	// IE compatibility
	var versions = [
		'MSXML2.XmlHttp.6.0',
		'MSXML2.XmlHttp.5.0',
		'MSXML2.XmlHttp.4.0',
		'MSXML2.XmlHttp.3.0',
		'MSXML2.XmlHttp.2.0',
		'Microsoft.XmlHttp'
	];

	var xhr;
	for (var i = 0; i < versions.length; i++) {
		try {
			xhr = new ActiveXObject(versions[i]);
			break;
		} catch (e) {
			console.warn('Your browser does not seem to support AJAX request.');
		}
	}
	
	return xhr;
};


/**
 * Send the actual AJAX request to the server.
 * 
 * @param string
 * @param string
 * @param string
 * @param boolean
 * @param function
 * @returns false
 */
ajax.send = function(url, method, data, callback, async) {
	if (typeof(url) === 'undefined') {
		console.warn('Cannot sent AJAX request to undefined URL.');
		return false;
	}
	
	if (typeof(method) === 'undefined') {
		method = 'POST';
	}

	if (typeof(data) === 'undefined') {
		data = null;
	}

	if (typeof(async) === 'undefined') {
		async = true;
	}

	method = method.toUpperCase();
	
	if (method == 'GET') {
		url = url + '?' + data;
		data = null;
	}
	
	var xhr = ajax.init();
	xhr.open(method, url, async);
	
	xhr.onreadystatechange = function () {
		if (xhr.readyState == 4) {
			callback(xhr.responseText);
		}
	};
	
	if (method == 'POST') {
		xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	}
	
	xhr.send(data);
	return false;
};



/**
 * Try to get geolocation if supported and allowed by browser.
 */
var geoData = null;
var geoResult = 'Your browser does not support geolocation.';
var geoDiv = document.getElementById('weather-geolocation');

if (navigator.geolocation) {
	navigator.geolocation.getCurrentPosition(function(position) {
		geoData = OpenWeatherMapWidgetData + '&lat=' + position.coords.latitude + '&lon=' + position.coords.longitude;

		ajax.send(OpenWeatherMapWidget, 'POST', geoData, function(result) {
			geoDiv.innerHTML = result;
			return false;
		});
	}, function() {
		geoResult = 'The geolocation service failed.';
		return false;
	});
	
	if (geoData === null) {
		geoDiv.innerHTML = geoResult;
	}
}
