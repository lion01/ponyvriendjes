<?php
/**
 *  @package AdminTools
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  --
 * 
 *  Command-line script to schedule the File Alteration Monitor check
 */

// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
if(function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	if(function_exists('error_reporting')) {
		$oldLevel = error_reporting(0);
	}
	$serverTimezone = @date_default_timezone_get();
	if(empty($serverTimezone) || !is_string($serverTimezone)) $serverTimezone = 'UTC';
	if(function_exists('error_reporting')) {
		error_reporting($oldLevel);
	}
	@date_default_timezone_set( $serverTimezone);
}

// Define ourselves as a parent file
define( '_JEXEC', 1 );
define('AKEEBAENGINE', 1); // Enable Akeeba Engine

// Required by the CMS
define('DS', DIRECTORY_SEPARATOR);

// Load system defines
if (file_exists(dirname(__FILE__).'/defines.php')) {
        dirname(__FILE__).'/defines.php';
}
if (!defined('_JDEFINES')) {
        define('JPATH_BASE', dirname(__FILE__).'/../');
        require_once JPATH_BASE.'/includes/defines.php';
}

// Load the rest of the framework include files
include_once JPATH_LIBRARIES.'/import.php';
require_once JPATH_LIBRARIES.'/cms.php';

// Load the JApplicationCli class
jimport( 'joomla.application.cli' );

/**
 * Akeeba Backup CLI application 
 */
class AkeebaBackupCLI extends JApplicationCli
{
	public function execute()
	{
		// Load the language files
		$paths = array(JPATH_ADMINISTRATOR, JPATH_ROOT);
		$jlang = JFactory::getLanguage();
		$jlang->load('com_akeeba', $paths[0], 'en-GB', true);
		$jlang->load('com_akeeba', $paths[1], 'en-GB', true);
		$jlang->load('com_akeeba'.'.override', $paths[0], 'en-GB', true);
		$jlang->load('com_akeeba'.'.override', $paths[1], 'en-GB', true);
		
		// Get the backup profile and description
		$profile = $this->input->get('profile', 1, 'int');
		$description = $this->input->get('description', 'Command-line backup', 'string');
		$overrides = $this->getOption('override', array(), false);
		
		if(!empty($overrides))
		{
			$override_message = "\nConfiguration variables overriden in the command line:\n";
			$override_message .= implode(', ', array_keys($overrides) );
			$override_message .= "\n";
		}
		else
		{
			$override_message = "";
		}
		
		$debugmessage = '';
		if($this->input->get('debug',-1,'int') != -1) {
			if(!defined('AKEEBADEBUG')) {
				define('AKEEBADEBUG',1);
			}
			$debugmessage = "*** DEBUG MODE ENABLED ***\n";
		}

		$version = AKEEBA_VERSION;
		$date = AKEEBA_DATE;
		$start_backup = time();
		$memusage = $this->memUsage();

		if($this->input->get('quiet',-1,'int') == -1) {
			echo <<<ENDBLOCK
Akeeba Backup CLI $version ($date)
Copyright (C) 2010-2011 Nicholas K. Dionysopoulos
-------------------------------------------------------------------------------
Akeeba Backup is Free Software, distributed under the terms of the GNU General
Public License version 3 or, at your option, any later version.
This program comes with ABSOLUTELY NO WARRANTY as per sections 15 & 16 of the
license. See http://www.gnu.org/licenses/gpl-3.0.html for details.
-------------------------------------------------------------------------------
$debugmessage
Starting a new backup with the following parameters:
Profile ID  $profile
Description "$description"
$override_message
Current memory usage: $memusage


ENDBLOCK;
		}
		
		// Attempt to use an infinite time limit, in case you are using the PHP CGI binary instead
		// of the PHP CLI binary. This will not work with Safe Mode, though.
		$safe_mode = true;
		if(function_exists('ini_get')) {
			$safe_mode = ini_get('safe_mode');
		}
		if(!$safe_mode && function_exists('set_time_limit')) {
			if($this->input->get('quiet',-1,'int') == -1) {
				echo "Unsetting time limit restrictions.\n";
			}
			@set_time_limit(0);
		} elseif (!$safe_mode) {
			if($this->input->get('quiet',-1,'int') == -1) {
				echo "Could not unset time limit restrictions; you may get a timeout error\n";
			}
		} else {
			if($this->input->get('quiet',-1,'int') == -1) {
				echo "You are using PHP's Safe Mode; you may get a timeout error\n";	
			}
		}
		if($this->input->get('quiet',-1,'int') == -1) {
			echo "\n";
		}

		// Log some paths
		if($this->input->get('quiet',-1,'int') == -1) {
			echo "Site paths determined by this script:\n";
			echo "JPATH_BASE : ".JPATH_BASE."\n";
			echo "JPATH_ADMINISTRATOR : ".JPATH_ADMINISTRATOR."\n\n";
		}

		// Load the engine
		$factoryPath = JPATH_ADMINISTRATOR.'/components/com_akeeba/akeeba/factory.php';
		define('JPATH_COMPONENT_ADMINISTRATOR',JPATH_ADMINISTRATOR.'/components/com_akeeba');
		if(!file_exists($factoryPath)) {
			echo "ERROR!\n";
			echo "Could not load the backup engine; file does not exist. Technical information:\n";
			echo "Path to ".basename(__FILE__).": ".dirname(__FILE__)."\n";
			echo "Path to factory file: $factoryPath\n";
			die("\n");
		} else {
			try {
				require_once $factoryPath;
			} catch(Exception $e) {
				echo "ERROR!\n";
				echo "Backup engine returned an error. Technical information:\n";
				echo "Error message:\n\n";
				echo $e->getMessage()."\n\n";
				echo "Path to ".basename(__FILE__).":".dirname(__FILE__)."\n";
				echo "Path to factory file: $factoryPath\n";
				die("\n");
			}
		}

		// Forced CLI mode settings
		define('AKEEBA_PROFILE', $profile);
		define('AKEEBA_BACKUP_ORIGIN', 'cli');
		
		// Force loading CLI-mode translation class
		$dummy = new AEUtilTranslate;

		// Load the profile
		AEPlatform::getInstance()->load_configuration($profile);
		
		// Reset Kettenrad and its storage
		AECoreKettenrad::reset(array(
			'maxrun'	=> 0
		));
		AEUtilTempvars::reset(AKEEBA_BACKUP_ORIGIN);

		// Setup
		$kettenrad = AEFactory::getKettenrad();
		$options = array(
			'description'	=> $description,
			'comment'		=> ''
		);
		if(!empty($overrides)) {
			AEPlatform::getInstance()->configOverrides = $overrides;
		}
		$kettenrad->setup($options);

		// Dummy array so that the loop iterates once
		$array = array(
			'HasRun'	=> 0,
			'Error'		=> ''
		);

		$warnings_flag = false;
		
		while( ($array['HasRun'] != 1) && (empty($array['Error'])) )
		{
			AEUtilLogger::openLog(AKEEBA_BACKUP_ORIGIN);
			AEUtilLogger::WriteLog(true,'');
			// Apply overrides in the command line
			if(!empty($overrides))
			{
				$config = AEFactory::getConfiguration();
				foreach($overrides as $key => $value)
				{
					$config->set($key, $value);
				}
			}
			// Apply engine optimization overrides
			$config = AEFactory::getConfiguration();
			$config->set('akeeba.tuning.min_exec_time',0);
			$config->set('akeeba.tuning.nobreak.beforelargefile',1);
			$config->set('akeeba.tuning.nobreak.afterlargefile',1);
			$config->set('akeeba.tuning.nobreak.proactive',1);
			$config->set('akeeba.tuning.nobreak.finalization',1);
			$config->set('akeeba.tuning.settimelimit',0);
			$config->set('akeeba.tuning.nobreak.domains',0);

			$kettenrad->tick();
			AEFactory::getTimer()->resetTime();
			$array = $kettenrad->getStatusArray();
			AEUtilLogger::closeLog();
			$time = date('Y-m-d H:i:s \G\M\TO (T)');
			$memusage = $this->memUsage();

			$warnings = "no warnings issued (good)";
			$stepWarnings = false;
			if(!empty($array['Warnings'])) {
				$warnings_flag = true;
				$warnings = "POTENTIAL PROBLEMS DETECTED; ". count($array['Warnings'])." warnings issued (see below).\n";
				foreach($array['Warnings'] as $line) {
					$warnings .= "\t$line\n";
				}
				$stepWarnings = true;
				$kettenrad->resetWarnings();
			}

		if(($this->input->get('quiet',-1,'int') == -1) || $stepWarnings) echo <<<ENDSTEPINFO
Last Tick   : $time
Domain      : {$array['Domain']}
Step        : {$array['Step']}
Substep     : {$array['Substep']}
Memory used : $memusage
Warnings    : $warnings


ENDSTEPINFO;
		}
		
		// Clean up
		AEUtilTempvars::reset(AKEEBA_BACKUP_ORIGIN);

		if(!empty($array['Error']))
		{
			echo "An error has occurred:\n{$array['Error']}\n\n";
			$exitCode = 2;
		}
		else
		{
			if($this->input->get('quiet',-1,'int') == -1) {
				echo "Backup job finished successfully after approximately ".$this->timeago($start_backup, time(), '', false)."\n";
			}
		}

		if($warnings_flag && !hasOption('quiet')) {
			echo "\n".str_repeat('=',79)."\n";
			echo "!!!!!  W A R N I N G  !!!!!\n\n";
			echo "Akeeba Backup issued warnings during the backup process. You have to review them\n";
			echo "and make sure that your backup has completed successfully. Always test a backup with\n";
			echo "warnings to make sure that it is working properly, by restoring it to a local server.\n";
			echo "DO NOT IGNORE THIS MESSAGE! AN UNTESTED BACKUP IS AS GOOD AS NO BACKUP AT ALL.\n";
			echo "\n".str_repeat('=',79)."\n";
		} elseif($warnings_flag) {
			$exitCode = 1;
		}

		if($this->input->get('quiet',-1,'int') == -1) {
			echo "Peak memory usage: ".$this->peakMemUsage()."\n\n";
		}

		$this->close($exitCode);
	}
	
	/**
	 * Returns a fancy formatted time lapse code
	 * 
	 * @param  $referencedate	int		Timestamp of the reference date/time
	 * @param  $timepointer		int		Timestamp of the current date/time
	 * @param  $measureby		string	One of s, m, h, d, or y (time unit)
	 * @param  $autotext			bool
	 * 
	 * @return  string
	 */
	private function timeago($referencedate=0, $timepointer='', $measureby='', $autotext=true)
	{
		if($timepointer == '') {
			$timepointer = time();
		}
		
		// Raw time difference
		$Raw = $timepointer-$referencedate;
		$Clean = abs($Raw);
		
		$calcNum = array(
			array('s', 60),
			array('m', 60*60),
			array('h', 60*60*60),
			array('d', 60*60*60*24),
			array('y', 60*60*60*24*365)
		);
		
		$calc = array(
			's' => array(1, 'second'),
			'm' => array(60, 'minute'),
			'h' => array(60*60, 'hour'),
			'd' => array(60*60*24, 'day'),
			'y' => array(60*60*24*365, 'year')
		);

		if($measureby == ''){
			$usemeasure = 's';

			for($i=0; $i<count($calcNum); $i++){
				if($Clean <= $calcNum[$i][1]){
					$usemeasure = $calcNum[$i][0];
					$i = count($calcNum);
				}
			}
		} else {
			$usemeasure = $measureby;
		}

		$datedifference = floor($Clean/$calc[$usemeasure][0]);

		if($autotext==true && ($timepointer==time())){
			if($Raw < 0){
				$prospect = ' from now';
			} else {
				$prospect = ' ago';
			}
		} else {
			$prospect = '';
		}

		if($referencedate != 0){
			if($datedifference == 1){
				return $datedifference . ' ' . $calc[$usemeasure][1] . ' ' . $prospect;
			} else {
				return $datedifference . ' ' . $calc[$usemeasure][1] . 's ' . $prospect;
			}
		} else {
			return 'No input time referenced.';
		}
	}
	
	/**
	 * Returns the current memory usage
	 * 
	 * @return string 
	 */
	private function memUsage()
	{
		if(function_exists('memory_get_usage')) {
			$size = memory_get_usage();
			$unit=array('b','Kb','Mb','Gb','Tb','Pb');
			return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		} else {
			return "(unknown)";
		}
	}

	/**
	 * Returns the peak memory usage
	 * 
	 * @return string 
	 */
	private function peakMemUsage()
	{
		if(function_exists('memory_get_peak_usage')) {
			$size = memory_get_peak_usage();
			$unit=array('b','Kb','Mb','Gb','Tb','Pb');
			return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		} else {
			return "(unknown)";
		}
	}
	
	/**
	 * Parses POSIX command line options and returns them as an associative array. Each array item contains
	 * a single dimensional array of values. Arguments without a dash are silently ignored.
	 * @return array
	 */
	private function parseOptions()
	{
		global $argc, $argv;

		// Workaround for PHP-CGI
		if(!isset($argc) && !isset($argv))
		{
			$query = "";
			if(!empty($_GET)) foreach($_GET as $k => $v) {
				$query .= " $k";
				if($v != "") {
					$query .= "=$v";
				}
			}
			$query = ltrim($query);
			$argv = explode(' ',$query);
			$argc = count($argv);
		}

		$currentName	= "";
		$options		= array();

		for ($i = 1; $i < $argc; $i++) {
			$argument = $argv[$i];
			if(strpos($argument,"-")===0)
			{
				$argument = ltrim($argument, '-');
				if( strstr($argument, '=') )
				{
					list($name, $value) = explode( '=', $argument, 2);
				}
				else
				{
					$name = $argument;
					$value = null;
				}
				$currentName=$name;
				if( !isset($options[$currentName]) || ($options[$currentName]==NULL) )
				{
					$options[$currentName]=array();
				}
			}
			else
			{
				$value = $argument;
			}
			if( (!is_null($value)) && (!is_null($currentName)) )
			{
				if(strstr($value,'='))
				{
					$parts = explode('=',$value,2);
					$key = $parts[0];
					$value = $parts[1];
				}
				else
				{
					$key = null;
				}

				$values=$options[$currentName];
				if(is_null($key)) {
					array_push($values,$value);
				} else {
					$values[$key] = $value;
				}
				$options[$currentName]=$values;
			}
		}
		return $options;
	}

	/**
	* Returns the value of a command line option
	* @param string $key The full name of the option, e.g. "foobar"
	* @param mixed $default The default value to return
	* @param bool $first_item_only Return only the first value specified (default = true)
	* @return mixed
	*/
	private function getOption($key, $default = null, $first_item_only = true)
	{
		static $options = null;
		if( is_null($options) )
		{
			$options = $this->parseOptions();
		}

		if( !array_key_exists($key, $options) )
		{
			return $default;
		}
		else
		{
			if( $first_item_only )
			{
				return $options[$key][0];
			}
			else
			{
				return $options[$key];
			}
		}
	}
}

// Load the version file
require_once JPATH_ADMINISTRATOR.'/components/com_akeeba/version.php';

// Instanciate and run the application
JApplicationCli::getInstance( 'AkeebaBackupCLI' )->execute( );