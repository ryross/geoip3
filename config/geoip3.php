<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  KohanaGeoip
 *
 * Settings related to the Kohana MAXMIND GeoIP module.
 */


return array
(
	'dbfile' => MODPATH.'ko3geoip'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'GeoLiteCity.dat',
	'useshm' => FALSE,
	'internalcache' => FALSE;

);
