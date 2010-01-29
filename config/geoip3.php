<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  GeoIP3
 *
 * Settings related to the Kohana 3 MAXMIND GeoIP module.
 */


return array
(
	'dbfile' => MODPATH.'geoip3'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'GeoLiteCity.dat',
	'useshm' => FALSE,
	'internalcache' => TRUE

);
