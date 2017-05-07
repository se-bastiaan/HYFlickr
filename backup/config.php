<?php

class HYFlickrOptions {

	const NAME = 'hyflickr_options';
	const API_KEY = 'api_key';
	const API_SECRET = 'api_secret';
	const API_USER = 'api_user';
	const API_TOKEN = 'api_token';

	static function get($option) {
		return get_option(self::NAME)[$option];
	}
	
	static function update($option, $value) {
		$options = get_option(self::NAME);
		$options[$option] = $value;
		update_option(self::NAME, $options);
	}

}