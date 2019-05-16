<?php
/*
 * Ristretto - a general purpose PHP object library
 * Copyright (c) 2019 Nicholas Costa. All rights reserved.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 */

namespace Ristretto;

/**
 * Configurable logging facility. Out of the box, log messages are printed to the
 * console via stderr by default.
 *
 * @todo Add ability to log to syslog: http://php.net/manual/en/function.syslog.php
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.2
 */
class RSLog
{
	protected static	$sharedInstance;

	protected	$log;
	protected	$logLevel = 1;
	protected	$logType = 5;

	const	ERROR = 1;
	const	WARNING = 2;
	const	INFO = 3;
	const	DEBUG = 4;
	
	const	PHP = 0;
	const	FILE = 3;
	const	CONSOLE = 5;
	
	const	MSG_MAX_LENGTH = 32767;

	/**************************************************************************//**
	 * Configures the shared instance of RSLog for app logging.
	 *
	 * If a log path is not specified or the path is not writable, a default path
	 * will be found based upon the following:
	 *	- if user is root: 		/var/log/<identifier>.log>
	 *	- if user is not root:	
	 *		- OS X:				~/Library/Logs/<identifier>.log
	 *		- Linux:			~/.<identifier>.log
	 *
	 * ...where <identifier> is the command name at runtime.
	 *
	 *****************************************************************************/
	public static function configure( $options )
	{
		$inst = RSLog::sharedInstance();
		foreach( $options as $k => $v )
		{
			switch( $k )
			{
				case 'type': $inst->logType = $v; break;
				case 'log': $inst->log = RSPath::expandPath( $v ); break;
				case 'level': $inst->logLevel = $v; break;
			}			
		}
		

		// configure a default log file
		if( $inst->log == null /* || !is_writable( $inst->log ) */ )
		{
			// identifier: command name (CLI) or file name (CGI)
			$logFile = ( isset( $argv[0]) ) ?
				basename( $argv[ 0 ] ).".log" :
				basename( $_SERVER['SCRIPT_FILENAME'] ).".log";

			if( posix_getuid() == 0 )
			{
				$logDir = '/var/log/';
			}
			else if( strpos( php_uname(), 'Darwin' ) !== false )
			{
				$logDir = RSPath::expandPath( "~/Library/Logs/" );
			}
			else
			{
				// make an invisible log file within home directory
				$logDir = RSPath::expandPath( "~/." );
			}
			
			$inst->log = $logDir.$logFile;
			$p = new RSPath( $inst->log );
			if( !$p->writable() )
			{
		        fwrite( STDERR, $inst->timestamp()." [error]: default log file '$logDir$logFile' not writable" );
				$inst->log = null;
			}
		}
	
		return $inst;
	}

	/**************************************************************************//**
	 * Manages the shared instance of RSLog. This is a global log structure that
	 * can be used throughout an application for logging facilities.
	 *****************************************************************************/
	public static function sharedInstance()
	{
		if( self::$sharedInstance === null ) self::$sharedInstance = new RSLog();
		return self::$sharedInstance;
	}

	
	/**************************************************************************//**
	 * Send an error message to the app shared log. Accepts a variable parameter
	 * list.
	 *****************************************************************************/
	public static function error()
	{
		$log = RSLog::sharedInstance();
		call_user_func_array(
			array( $log, 'log' ),
			array_merge( array( RSLog::ERROR ), func_get_args() ) );
	}

	/**************************************************************************//**
	 * Send a warning message to the app shared log. Accepts a variable parameter
	 * list.
	 *****************************************************************************/
	public static function warning()
	{
		$log = RSLog::sharedInstance();
		call_user_func_array(
			array( $log, 'log' ),
			array_merge( array( RSLog::WARNING ), func_get_args() ) );
	}

	/**************************************************************************//**
	 * Send an info message to the app shared log. Accepts a variable parameter
	 * list.
	 *****************************************************************************/
	public static function info()
	{
		$log = RSLog::sharedInstance();
		call_user_func_array(
			array( $log, 'log' ),
			array_merge( array( RSLog::INFO ), func_get_args() ) );
	}

	/**************************************************************************//**
	 * Send a debug message to the app shared log. Accepts a variable parameter
	 * list.
	 *****************************************************************************/
	public static function debug()
	{
		$log = RSLog::sharedInstance();
		call_user_func_array(
			array( $log, 'log' ),
			array_merge( array( RSLog::DEBUG ), func_get_args() ) );
	}

	
#mark -

	/**************************************************************************//**
	 * Constructs an RSLog object with basic parameters.
	 *
	 *****************************************************************************/
	public function __construct( $log = null, $logLevel = RSLog::INFO )
	{
		$this->log = $log;
		$this->logLevel = $logLevel;
	}

	/**************************************************************************//**
	 * Sets the file path for messages to be logged.
	 * 
	 * @param $log the path to the log file
	 *****************************************************************************/
	public function setLog( $log )
	{
		$this->log = RSPath::expandPath( $log );
	}
	
	/**************************************************************************//**
	 * Sets the log type.
	 *
	 * @todo Validate $type as class constant
	 * @param $type A class constant representing the type of log.
	 *****************************************************************************/
	public function setType( $type )
	{
		$this->logType = $type;
	}
	
	/**************************************************************************//**
	 * Sets the log level. After being set, messages sent to the log with a level
	 * higher than the set log level will be ignored.
	 *
	 * @todo Validate $level as class constant
	 * @param $level A class constant representing the log level.
	 *****************************************************************************/
	public function setLevel( $level )
	{
		$this->logLevel = $level;
	}

	/**************************************************************************//**
	 * Send an error message to the log. Accepts a variable parameter list.
	 *****************************************************************************/
	public function logError()
	{
		call_user_func_array(
			array( $this, 'log' ),
			array_merge( array( RSLog::ERROR ), func_get_args() ) );
	}

	/**************************************************************************//**
	 * Send a warning message to the log. Accepts a variable parameter list.
	 *****************************************************************************/
	public function logWarning()
	{
		call_user_func_array(
			array( $this, 'log' ),
			array_merge( array( RSLog::WARNING ), func_get_args() ) );
	}

	/**************************************************************************//**
	 * Send an info message to the log. Accepts a variable parameter list.
	 *****************************************************************************/
	public function logInfo()
	{
		call_user_func_array(
			array( $this, 'log' ),
			array_merge( array( RSLog::INFO ), func_get_args() ) );
	}

	/**************************************************************************//**
	 * Send a debug message to the log. Accepts a variable parameter list.
	 *****************************************************************************/
	public function logDebug()
	{
		call_user_func_array(
			array( $this, 'log' ),
			array_merge( array( RSLog::DEBUG ), func_get_args() ) );
	}


	/**************************************************************************//**
	 * Sends a message to the log at the level specified. Receives a variable
	 * argument list of mixed type. Each variable argument is formed into a 
	 * string representation and output to the log.
	 *
	 * @param $level the log level for this message; if the level specified is
	 *				 greater than the set level for the log, the messge is
	 *				 ignored.
	 *****************************************************************************/
	public function log( $level = RSLog::INFO )
	{
		if( $level > $this->logLevel ) return;

		$arguments = func_get_args();
		$argCount = func_num_args();
		
		$type = '';
		switch( $level )
		{
			case RSLog::ERROR: $type = 'error'; break;
			case RSLog::WARNING: $type = 'warn'; break;
			case RSLog::DEBUG: $type = 'debug'; break;
			default:
			case RSLog::INFO: $type = 'info'; break;
		}

		// get text representation of passed parameters
		$text = '';
		for( $i = 1; $i < $argCount; $i++ ) $text .= $this->stringify( $arguments[ $i ] );

		// form into error string
		$errStr = $this->timestamp().' ['.$type.']: '.$text."\n";
		
		// log out based upon log type
		switch( $this->logType )
		{
			case RSLog::PHP:
			case RSLog::FILE:
				error_log( $errStr, $this->logType, $this->log );
				break;
			case RSLog::CONSOLE:
		        fwrite( STDERR, $errStr );
				break;
			default:
				error_log( $errStr, $this->logType, $this->log );
				break;
		}
	}
	
	
	/**************************************************************************//**
	 * Returns a readable string representing the provided parameter.
	 * 
	 * @param mixed variable to be made into a string.
	 * @return a string representing the received parameter.
	 *****************************************************************************/
	protected function stringify( $param )
	{
		switch( gettype( $param ) )
		{
			case "integer":
			case "double":
			case "string":
				return "$param";
				break;
			
			case "boolean":
				return ( $param ) ? "true" : "false";
				break;
			
			case "array":
				return print_r( $param, true );
				break;
				
			case "resource":
				return "(resource: ".get_resource_type( $param )." )";
			
			case "NULL":
				return "(null)";
				break;
		}
	}
	
	/**************************************************************************//**
	 * Private function to create a timestamp for log messages.
	 *
	 * @todo Consider allowing the format to be specified as an option?
	 * @return a text representation of the current timestamp
	 *****************************************************************************/
	private function timestamp()
	{
		$time = date("Y-m-d H:i:s");
		list($usec, ) = explode(" ", microtime());
		$usec = round($usec, 4);
		$usec = substr("$usec", 2, 10);
		
		// pad the time string with trailing zeroes
		$timeString = "$time.$usec";
		for($i = strlen($timeString); $i < 24; $i++) $timeString .= '0';

		return $timeString;	
	}
}

?>