<?php
/**
 * FuzeWorks Framework Database Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2018 TechFuze
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
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.1.4
 *
 * @version Version 1.1.4
 */

use FuzeWorks\Factory;
use FuzeWorks\Database;
use FuzeWorks\Exception\DatabaseException;
use FuzeWorks\Core;

/**
 * Initialize the database
 *
 * Converted from CodeIgniter.
 *
 * @package        FuzeWorks
 * @category    Database
 * @author    EllisLab Dev Team
 * @link    https://codeigniter.com/user_guide/database/
 * @license        http://opensource.org/licenses/MIT	MIT License
 *
 * @param    string|string[] $params
 * @param    bool $query_builder_override
 *                Determines if query builder should be used or not
 * @return mixed
 * @throws DatabaseException
 */
function &DB($params = '', $query_builder_override = NULL)
{
	// Load the DB config file if a DSN string wasn't passed
	if (is_string($params) && strpos($params, '://') === FALSE)
	{
		// First retrieve the config file
		try {
		    $config = Factory::getInstance()->config->get('database');
		} catch (ConfigException $e) {
			throw new DatabaseException($e->getMessage(), 1);
		}

		// Determine if there are actually settings in the config file
		if ( ! isset($config->databases) OR count($config->databases) === 0)
		{
			throw new DatabaseException('No database connection settings were found in the database config file.', 1);
		}

		// Define the active group
		$active_group = ($params !== '' ? $params : $config->active_group);

		if ( ! isset($active_group))
		{
			throw new DatabaseException('You have not specified a database connection group via $active_group in your config.database.php file.', 1);
		}
		elseif ( ! isset($config->databases[$active_group]))
		{
			throw new DatabaseException('You have specified an invalid database connection group ('.$active_group.') in your config.database.php file.', 1);
		}

		$params = $config->databases[$active_group];
	}
	elseif (is_string($params))
	{
		/**
		 * Parse the URL from the DSN string
		 * Database settings can be passed as discreet
		 * parameters or as a data source name in the first
		 * parameter. DSNs must have this prototype:
		 * $dsn = 'driver://username:password@hostname/database';
		 */
		if (($dsn = @parse_url($params)) === FALSE)
		{
			throw new DatabaseException('Invalid DB Connection String', 1);
		}

		$params = array(
			'dbdriver'	=> $dsn['scheme'],
			'hostname'	=> isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
			'port'		=> isset($dsn['port']) ? rawurldecode($dsn['port']) : '',
			'username'	=> isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
			'password'	=> isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
			'database'	=> isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : ''
		);

		// Were additional config items set?
		if (isset($dsn['query']))
		{
			parse_str($dsn['query'], $extra);

			foreach ($extra as $key => $val)
			{
				if (is_string($val) && in_array(strtoupper($val), array('TRUE', 'FALSE', 'NULL')))
				{
					$val = var_export($val, TRUE);
				}

				$params[$key] = $val;
			}
		}
	}

	// No DB specified yet? Beat them senseless...
	if (empty($params['dbdriver']))
	{
		throw new DatabaseException('You have not selected a database type to connect to.', 1);
	}

	// Load the DB classes. Note: Since the query builder class is optional
	// we need to dynamically create a class that extends proper parent class
	// based on whether we're using the query builder class or not.
	if ($query_builder_override !== NULL)
	{
		$query_builder = $query_builder_override;
	}
	// Backwards compatibility work-around for keeping the
	// $active_record config variable working. Should be
	// removed in v3.1
	elseif ( ! isset($query_builder) && isset($active_record))
	{
		$query_builder = $active_record;
	}

	require_once(dirname(__DIR__) . DS . 'Database'.DS.'DB_driver.php');

	if ( ! isset($query_builder) OR $query_builder === TRUE)
	{
		require_once(dirname(__DIR__) . DS . 'Database'.DS.'DB_query_builder.php');
		if ( ! class_exists('FW_DB', FALSE))
		{
			/**
			 * FW_DB
			 *
			 * Acts as an alias for both FW_DB_driver and FW_DB_query_builder.
			 *
			 * @see	FW_DB_query_builder
			 * @see	FW_DB_driver
			 */
			class FW_DB extends FW_DB_query_builder { }
		}
	}
	elseif ( ! class_exists('FW_DB', FALSE))
	{
		/**
	 	 * @ignore
		 */
		class FW_DB extends FW_DB_driver { }
	}

	// Load the DB driver
	$driver_file = dirname(__DIR__) . DS . 'Database'.DS.'drivers'.DS.$params['dbdriver'].DS.$params['dbdriver'].'_driver.php';

	if (!file_exists($driver_file))
	{
		throw new DatabaseException("Invalid DB Driver", 1);
	}

	require_once($driver_file);

	// Instantiate the DB adapter
	$driver = 'FW_DB_'.$params['dbdriver'].'_driver';
	$DB = new $driver($params);

	// Check for a subdriver
	if ( ! empty($DB->subdriver))
	{
		$driver_file = dirname(__DIR__) . DS . 'Database'.DS.'drivers'.DS.$DB->dbdriver.DS.'subdrivers'.DS.$DB->dbdriver.'_'.$DB->subdriver.'_driver.php';

		if (file_exists($driver_file))
		{
			require_once($driver_file);
			$driver = 'FW_DB_'.$DB->dbdriver.'_'.$DB->subdriver.'_driver';
			$DB = new $driver($params);
		}
	}

	$DB->initialize();
	return $DB;
}
