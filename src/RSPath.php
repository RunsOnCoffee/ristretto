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

/**************************************************************************//**
 * Representation of a file path. Provides common-use functionality for file
 * handling and manipulation.
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 *****************************************************************************/
class RSPath
{
	public		$path;	/**< the path string represented by this object */
	protected	$pi;	/**< the path info, if it has been retrieved */

	/**************************************************************************//**
	 * Expands tilde in standard POSIX path.
	 *****************************************************************************/
	public static function expandPath( $path )
	{
		$p = new RSPath( $path );
		return $p->fullPath();
	}
	
	/**************************************************************************//**
	 * Create an RSPath object.
	 *****************************************************************************/
	public function __construct( $path )
	{
		$this->path = $path;
	}

	/**************************************************************************//**
	 * Determines if the path can be written by the current user.
	 *****************************************************************************/
	public function writable()
	{
		// quick check -- if exists AND can write, we're done
		if( is_writable( $this->path ) ) return true;
		
		$tmp = $this->path.uniqid(mt_rand()).'.writable';
		$r = @fopen( $tmp, "w" );
		if( $r !== false )
		{
			@fclose( $r );
			@unlink( $tmp );
			return true;
		}
		
		return false;
	}

	/**************************************************************************//**
	 * Returns the path extension.
	 *****************************************************************************/
	public function extension()
	{
		if( !$this->pi ) $this->pi = pathinfo( $this->path );
		return $this->pi['extension'];
	}

	/**************************************************************************//**
	 * Returns the full expanded path. This is not the same as the real path,
	 * which follows symlinks and only returns a path if the file exists.
	 *****************************************************************************/
	public function fullPath()
	{
		$path = $this->path;
		if ( strpos( $path, '~' ) !== false && function_exists( 'posix_getuid' ) )
		{
			$info = posix_getpwuid( posix_getuid() );
			$path = str_replace( '~', $info['dir'], $path );
		}

		return $path;
	}
	
	/**************************************************************************//**
	 * Determines if the path exists.
	 *****************************************************************************/
	public function exists()
	{
		return file_exists( $this->path );
	}

	public function __toString()
	{
		return $this->path;
	}
	
}
?>