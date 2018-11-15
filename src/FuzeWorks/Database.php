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

namespace FuzeWorks;
use FuzeWorks\Exception\DatabaseException;
use FW_DB;
use FW_DB_forge;
use FW_DB_utility;

/**
 * Database loading class
 * 
 * Loads databases, forges and utilities in a standardized manner. 
 * 
 * @author  TechFuze <contact@techfuze.net>
 * @copyright (c) 2013 - 2014, TechFuze. (https://techfuze.net)
 * 
 */
class Database
{
    
    /**
     * The default database forge.
     * @var FW_DB|null
     */
    protected static $defaultDB = null;

    /**
     * Array of all the non-default databases
     * @var array FW_DB|null
     */    
    protected static $databases = array();
    
    /**
     * The default database forge.
     * @var FW_DB_forge|null
     */
    protected static $defaultForge = null;
    
    /**
     * Array of all the non-default databases forges.
     * @var array FW_DB_forge|null
     */    
    protected static $forges = array();

    /**
     * The default database utility.
     * @var FW_DB_utility|null
     */
    protected static $defaultUtil = null;
    
    /**
     * Register with the TracyBridge upon startup
     */
    public function __construct()
    {
        if (class_exists('Tracy\Debugger', true))
        {
            DatabaseTracyBridge::register();
        }
    }

    /**
     * Retrieve a database using a DSN or the default configuration.
     *
     * If a string is provided like this: 'dbdriver://username:password@hostname/database',
     * the string will be interpreted and converted into a database connection parameter array.
     *
     * If a string is provided with a name, like this: 'default' the 'default' connection from the
     * configuration file will be loaded. If no string is provided the default database will be loaded.
     *
     * If the $newInstance is a true boolean, a new instance will be loaded instead of loading the
     * default one. $newInstance will also make sure that the loaded database is not default one.
     * This behaviour will be changed in the future.
     *
     *
     * If $queryBuilder = false is provided, the database will load without a queryBuilder.
     * By default the queryBuilder will load.
     *
     * @param string $parameters
     * @param bool $newInstance
     * @param bool $queryBuilder
     * @return FW_DB|bool
     * @throws DatabaseException
     * @throws Exception\EventException
     */
    public static function get($parameters = '', $newInstance = false, $queryBuilder = null)
    {
        // Fire the event to allow settings to be changed
        $event = Events::fireEvent('databaseLoadDriverEvent', $parameters, $newInstance, $queryBuilder);
        if ($event->isCancelled())
        {
            return false;
        }

        // If an instance already exists and is requested, return it
        if (isset($event->database) && empty($event->parameters))
        {
            return self::$defaultDB = $event->database;
        }
        elseif (isset($event->database) && !empty($event->parameters))
        {
            return self::$databases[$event->parameters] = $event->database;
        }
        elseif (empty($event->parameters) && !$event->newInstance && is_object(self::$defaultDB) && ! empty(self::$defaultDB->conn_id))
        {
            return $reference = self::$defaultDB;
        }
        elseif (!empty($event->parameters) && !$event->newInstance && isset(self::$databases[$event->parameters])) 
        {
            return $reference = self::$databases[$event->parameters];
        }

        // If a new instance is required, load it
        require_once (dirname(__DIR__) . DS .   'Database'.DS.'DB.php');

        if ($event->newInstance === TRUE)
        {
            $database = DB($event->parameters, $event->queryBuilder);
        }
        elseif (empty($event->parameters) && $event->newInstance === FALSE)
        {
            $database = self::$defaultDB = DB($event->parameters, $event->queryBuilder);
        }
        else
        {
            $database = self::$databases[$event->parameters] = DB($event->parameters, $event->queryBuilder);
        }

        // Tie it into the Tracy Bar if available
        if (class_exists('\Tracy\Debugger', true))
        {
            DatabaseTracyBridge::registerDatabase($database);
        }

        return $database;
    }

    /**
     * Retrieves a database forge from the provided or default database.
     *
     * If no database is provided, the default database will be used.
     *
     * @param FW_DB|null $database
     * @param bool $newInstance
     * @return FW_DB_forge
     * @throws DatabaseException
     * @throws Exception\EventException
     */
    public static function getForge($database = null, $newInstance = false)
    {
        // Fire the event to allow settings to be changed
        $event = Events::fireEvent('databaseLoadForgeEvent', $database, $newInstance);
        if ($event->isCancelled())
        {
            return false;
        }

        // First check if we're talking about the default forge and that one is already set
        if (is_object($event->forge) && ($event->forge instanceof FW_DB_forge) )
        {
            return $event->forge;
        }
        elseif (is_object($event->database) && $event->database === self::$defaultDB && is_object(self::$defaultForge))
        {
            return $reference = self::$defaultForge;
        }
        elseif ( ! is_object($event->database) OR ! ($event->database instanceof FW_DB))
        {
            isset(self::$defaultDB) OR self::get('', false);
            $database =& self::$defaultDB;
        }

        require_once (dirname(__DIR__) . DS .   'Database'.DS.'DB_forge.php');
        require_once(dirname(__DIR__) . DS .   'Database'.DS.'drivers'.DS.$database->dbdriver.DS.$database->dbdriver.'_forge.php');

        if ( ! empty($database->subdriver))
        {
            $driver_path = dirname(__DIR__) . DS .   'Database'.DS.'drivers'.DS.$database->dbdriver.DS.'subdrivers'.DS.$database->dbdriver.'_'.$database->subdriver.'_forge.php';
            if (file_exists($driver_path))
            {
                require_once($driver_path);
                $class = 'FW_DB_'.$database->dbdriver.'_'.$database->subdriver.'_forge';
            }
            else
            {
                throw new DatabaseException("Could not load forge. Driver file does not exist.", 1);
            }
        }
        else
        {
            $class = 'FW_DB_'.$database->dbdriver.'_forge';
        }

        // Create a new instance of set the default database
        if ($event->newInstance)
        {
            return new $class($database);
        }
        else 
        {
            return self::$defaultForge = new $class($database);
        }
    }

    /**
     * Retrieves a database utility from the provided or default database.
     *
     * If no database is provided, the default database will be used.
     *
     * @param FW_DB|null $database
     * @param bool $newInstance
     * @return FW_DB_utility
     * @throws DatabaseException
     * @throws Exception\EventException
     */
    public static function getUtil($database = null, $newInstance = false)
    {
        // Fire the event to allow settings to be changed
        $event = Events::fireEvent('databaseLoadUtilEvent', $database, $newInstance);
        if ($event->isCancelled())
        {
            return false;
        }

        // First check if we're talking about the default util and that one is already set
        if (is_object($event->util) && ($event->util instanceof FW_DB_utility))
        {
            return $event->util;
        }
        elseif (is_object($event->database) && $event->database === self::$defaultDB && is_object(self::$defaultUtil))
        {
            return $reference = self::$defaultUtil;
        }

        if ( ! is_object($event->database) OR ! ($event->database instanceof FW_DB))
        {
            isset(self::$defaultDB) OR self::get('', false);
            $database = & self::$defaultDB;
        }

        require_once (dirname(__DIR__) . DS .   'Database'.DS.'DB_utility.php');
        require_once(dirname(__DIR__) . DS .   'Database'.DS.'drivers'.DS.$database->dbdriver.DS.$database->dbdriver.'_utility.php');
        $class = 'FW_DB_'.$database->dbdriver.'_utility';

        if ($event->newInstance)
        {
            return new $class($database);
        }      
        else
        {
            return self::$defaultUtil = new $class($database);
        }
    }
}