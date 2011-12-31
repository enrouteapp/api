<?php 

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| API Format
|--------------------------------------------------------------------------
|
| What format should the data be returned in by default?
|
|	Default: xml
|
*/
$config['api_default_format'] = 'json';

/*
|--------------------------------------------------------------------------
| API Database Group
|--------------------------------------------------------------------------
|
| Connect to a database group for keys, logging, etc. It will only connect
| if you have any of these features enabled.
|
|	'default'
|
*/
$config['api_database_group'] = 'default';

/*
|--------------------------------------------------------------------------
| API Keys Table Name
|--------------------------------------------------------------------------
|
| The table name in your database that stores API Keys.
|
|	'keys'
|
*/
$config['api_keys_table'] = 'api_key';

/*
|--------------------------------------------------------------------------
| API Enable Keys
|--------------------------------------------------------------------------
|
| When set to true MY_Controller will look for a key and match it to the DB.
| If no key is provided, the request will return an error.
|
|	FALSE

	CREATE TABLE `keys` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `key` varchar(40) NOT NULL,
	  `level` int(2) NOT NULL,
	  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
	  `date_created` int(11) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
|
*/
$config['api_enable_keys'] = FALSE;

/*
|--------------------------------------------------------------------------
| API Key Length
|--------------------------------------------------------------------------
|
| How long should created keys be? Double check this in your db schema.
|
|	Default: 32
|	Max: 40
|
*/
$config['api_key_length'] = 32;

/*
|--------------------------------------------------------------------------
| API Key Variable
|--------------------------------------------------------------------------
|
| Which variable will provide us the API Key
|
| Default: X-API-KEY
|
*/
$config['api_key_name'] = 'X-API-KEY';

/*
|--------------------------------------------------------------------------
| API Limits Table Name
|--------------------------------------------------------------------------
|
| The table name in your database that stores limits.
|
|	'logs'
|
*/
$config['api_limits_table'] = 'api_limit';

/*
|--------------------------------------------------------------------------
| API Enable Limits
|--------------------------------------------------------------------------
|
| When set to true MY_Controller will count the number of uses of each method
| by an API key each hour. This is a general rule that can be overridden in the
| $this->method array in each controller.
|
|	FALSE
|
	CREATE TABLE `limits` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `uri` varchar(255) NOT NULL,
	  `count` int(10) NOT NULL,
	  `hour_started` int(11) NOT NULL,
	  `api_key` varchar(40) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
|
*/
$config['api_enable_limits'] = FALSE;

/*
|--------------------------------------------------------------------------
| Routing Configuration
|--------------------------------------------------------------------------
*/

// cache control
$config["stoplist_ttl"] = 30; // number of seconds before stoplist expires
$config["google_api_ttl"] = 3600; // number of seconds before google api results expire

// route control
$config["stopped_time"] = 45; // estimated number of seconds vehicle will stay at each stop
$config["gps_precision"] = 2; // number of decimal places to use for GPS comparison
$config["gps_search_radius"] = 100; // size of search radius, in meters, to use when comparing GPS
$config["max_route_time"] = 2; // maximum length of route, in hours (4 default)

// readable date formatting
$config["date_format"] = "m/d/Y h:i A"; // human readable date format, see php:date()

// manual route control for testing
$config["ignore_start"] = strtotime("April 12, 2011 2:00 PM");
$config["ignore_end"] = strtotime("April 12, 2011 3:00 PM");
$config["manual_end_of_route"] = 0; //strtotime("April 12, 2011 3:30 PM");

// foursquare credentials
$config["fsq_username"] = "";
$config["fsq_password"] = "";
$config["fsq_search_rate"] = $config["stoplist_ttl"]*5;

