<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Provides access to MaxMind's GeoIP. Converted to Kohana3 by Ryder Ross. 
 * Original module by Doru Moisa.
 *
 * @package    Geoip3
 * @author     Ryder Ross
 */


class Geoip3 {

	protected $_config = array();
	protected static $_instance;
	protected $_geoinstance;
	protected $_cache = array();
	
	public static function instance()
	{
		if ( ! isset(Geoip3::$_instance))
		{
			// Load the configuration for this type
			$config = Kohana::config('geoip3');
			
			// Create a new session instance
			Geoip3::$_instance = new Geoip3($config);
		}
		
		return Geoip3::$_instance;
	}
	
	private function __construct($config = NULL)
	{
		$this->_config = ($config === NULL) ? Kohana::config('geoip3') : $config;
		
		if ( ! class_exists('GeoIP', FALSE))
		{
			// Load MaxMind GeoIP classes
			require_once Kohana::find_file('vendor', 'maxmind/geoip');
			require_once Kohana::find_file('vendor', 'maxmind/geoipcity');
			require_once Kohana::find_file('vendor', 'maxmind/geoipregionvars');
		}
		
		
		if ($this->_config->useshm)
		{
			geoip_load_shared_mem($this->_config->dbfile);
			$this->_geoinstance = geoip_open($this->_config->dbfile, GEOIP_SHARED_MEMORY);
			
		}
		else
		{
			$this->_geoinstance = geoip_open($this->_config->dbfile, GEOIP_STANDARD);
		}
	}
	
	public function city($ipaddress)
	{
		return $this->property('city', $ipaddress);
	}
	
	public function record($ipaddress)
	{
		global $GEOIP_REGION_NAME;
		
		if ( ! $this->_config->internalcache)
		{
			$rec = geoip_record_by_addr($this->_geoinstance, $ipaddress);
			if ($rec)
			{
				$rec->region = $GEOIP_REGION_NAME[$rec->country_code][$rec->region];
			}
			return $rec;
		}
		
		if ( ! isset($this->_cache[$ipaddress]))
		{
			$this->_cache[$ipaddress] = geoip_record_by_addr($this->_geoinstance, $ipaddress);
			if ($this->_cache[$ipaddress])
			{
				$this->_cache[$ipaddress]->region =
					$GEOIP_REGION_NAME[$this->_cache[$ipaddress]->country_code][$this->_cache[$ipaddress]->region];
			}
		}
		return $this->_cache[$ipaddress];
	}
	
	public function coord($ipaddress, $mode = 'geo-dms')
	{
		$rec = $this->record($ipaddress);
		if ( ! $rec) 
			return NULL;
		
		$modes = array('geo-dms', 'geo-dec', 'geo');
		if ( ! in_array($mode, $modes))
		{
			$mode = 'geo-dms';
		}
		
		// standard geo
		if ($mode == 'geo')
			return round($rec->latitude,3).'; '.round($rec->longitude, 3);
		
		
		$lat = round(str_replace("-","", $rec->latitude, $nr),3); // negative means South
		$lat_dir = 'N';
		if ($nr)
		{	
			$lat_dir= 'S';
		}
		
		$long = round(str_replace("-","", $rec->longitude, $nr),3); // negative means West
		$long_dir = 'E';
		if ($nr)
		{
			$long_dir = 'W';
		}
		
		// decimal
		if ($mode == 'geo-dec')
			return $lat.'&deg;'.$lat_dir.' '.$long.'&deg;'.$long_dir;
		
		
		// degree-minute-second
		$d = floor($lat);
		$m = sprintf("%02d", round(($lat-$d)*60));
		$lat = $d.'&deg;'.$m.'&prime;'.$lat_dir;
		
		$d = floor($long);
		$m = sprintf("%02d", round(($long-$d)*60));
		$long = $d.'&deg;'.$m.'&prime;'.$long_dir;
		
		return $lat.' '.$long;
	}
	
	public function property($property, $ipaddress)
	{
		$record = $this->record($ipaddress);
		
		if ( ! $record )
			return NULL;
			
		return isset($record->$property) ? $record->$property : NULL;
	}
	
	public function city_info($ipaddress, $mode = 'geo-dms')
	{
		if ( ! $this->record($ipaddress))
			return NULL;
		
		return $this->city($ipaddress).' ('.$this->coord($ipaddress, $mode).')';
	}
	
	private function __deconstruct()
	{
		geoip_close($this->_geoinstance);
	}
	
}