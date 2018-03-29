jQuery(function() {
	/**
	 * Toggles a property of an element between true and false
	 * in relation to the status of another element.
	 * 
	 * @returns false
	 */
	function toggleProperty(trigger_id, toggle_id, property) {
		if (jQuery(trigger_id).is(':checked')) {
			jQuery(toggle_id).prop(property, true);
		} else {
			jQuery(toggle_id).prop(property, false);
			jQuery(toggle_id).removeProp(property);
		}
		
		return false;
	}


	jQuery("input.use_geolocation").change(function() {
		toggleProperty('input.use_geolocation', '.location', 'disabled');
	});
});
