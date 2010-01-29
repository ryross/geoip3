<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Provides access to MaxMind's GeoIP. Converted to Kohana3 by Ryder Ross
 *
 * @package    KohanaGeoip
 * @author     Doru Moisa
 */


class Geoip3 {

	// Configuration
	protected $_config;
	
	// geoip instances
	protected static $instance;
	protected $_geoinstance;
	protected $_cache = array();
	
	
	/**
	 * Singleton pattern
	 *
	 * @return Geoip3
	 */
	public static function instance()
	{
		if ( ! isset(Geoip3::$instance))
		{
			// Load the configuration for this type
			$config = Kohana::config('geoip3');

			// Create a new session instance
			Geoip3::$instance = new Geoip3($config);
		}
		
		return Geoip3::$instance;
	}
	
	public function __construct($config = null)
	{
		
		$this->_config = Kohana::config('geoip3');
		is_array($config) AND $this->_config = array_merge($this->_config, $config);
		
		
		if ( ! class_exists('GeoIP', FALSE))
		{
			// Load SwiftMailer Autoloader
			require_once Kohana::find_file('vendor', 'maxmind/geoip');
			require_once Kohana::find_file('vendor', 'maxmind/geoipcity');
			require_once Kohana::find_file('vendor', 'maxmind/geoipregionvars');
		}
		
		
		if($this->_config['useshm'])
		{
			geoip_load_shared_mem($this->_config['dbfile']);
			$this->_geoinstance = geoip_open($this->_config['dbfile'], GEOIP_SHARED_MEMORY);
			
		}
		else
		{
			$this->_geoinstance = geoip_open($this->_config['dbfile'], GEOIP_STANDARD);
		}
	}
	
	public function getCity($ipaddress)
	{
		return $this->getProperty('city', $ipaddress);
	}
    
	public function getRecord($ipaddress)
	{
		global $GEOIP_REGION_NAME;
		
		if(!$this->_config['internalcache'])
		{
			$rec = geoip_record_by_addr($this->_geoinstance, $ipaddress);
			if($rec)
			{
				$rec->region = $GEOIP_REGION_NAME[$rec->country_code][$rec->region];
			}
			return $rec;
		}
		
		if(!isset($this->_cache[$ipaddress]))
		{
			$this->_cache[$ipaddress] = geoip_record_by_addr($this->_geoinstance, $ipaddress);
			if($this->_cache[$ipaddress])
			{
				$this->_cache[$ipaddress]->region =
					$GEOIP_REGION_NAME[$this->_cache[$ipaddress]->country_code][$this->_cache[$ipaddress]->region];
			}
		}
		return $this->_cache[$ipaddress];
	}
    
	public function getCoord($ipaddress, $mode = 'geo-dms')
	{
		$rec = $this->getRecord($ipaddress);
		if(!$rec) 
		{
			return null;
		}
		
		$modes = array('geo-dms', 'geo-dec', 'geo');
		if(!in_array($mode, $modes))
		{
			$mode = 'geo-dms';
		}
		
		// standard geo
		if($mode == 'geo')
		{
			return round($rec->latitude,3).'; '.round($rec->longitude, 3);
		}
		
		$lat = round(str_replace("-","", $rec->latitude, $nr),3); // negative means South
		$lat_dir = 'N';
		if($nr)
		{	
			$lat_dir= 'S';
		}
		
		$long = round(str_replace("-","", $rec->longitude, $nr),3); // negative means West
		$long_dir = 'E';
		if($nr)
		{
			$long_dir = 'W';
		}
		
		// decimal
		if($mode == 'geo-dec')
		{
			return $lat.'&deg;'.$lat_dir.' '.$long.'&deg;'.$long_dir;
		}
		
		// degree-minute-second
		$d = floor($lat);
		$m = sprintf("%02d", round(($lat-$d)*60));
		$lat = $d.'&deg;'.$m.'&prime;'.$lat_dir;
		
		$d = floor($long);
		$m = sprintf("%02d", round(($long-$d)*60));
		$long = $d.'&deg;'.$m.'&prime;'.$long_dir;

		return $lat.' '.$long;
	}

	public function getProperty($property, $ipaddress)
	{
		$record = $this->getRecord($ipaddress);
		if(!$record)
		{
			return null;
		}
		return isset($record->$property) ? $record->$property : null;
	}
	
	public function cityInfo($ipaddress, $mode = 'geo-dms')
	{
		if(!$this->getRecord($ipaddress))
		{
			return null;
		}
		return $this->getCity($ipaddress) . ' ('.$this->getCoord($ipaddress, $mode).')';
	}
	
	public function __deconstruct()
	{
		geoip_close($this->_geoinstance);
	}
    
}