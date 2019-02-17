<?php
/**
 * FuzeWorks Framework Layout Template System.
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
 * @since Version 0.0.1
 *
 * @version Version 1.2.0
 */


namespace FuzeWorks;

use FuzeWorks\Event\LayoutLoadEvent;
use FuzeWorks\TemplateEngine\{JsonEngine,PHPEngine,SmartyEngine,LatteEngine,TemplateEngine};
use FuzeWorks\Exception\LayoutException;
use FuzeWorks\Exception\EventException;

/**
 * Layout and Template Manager for FuzeWorks.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 */
class Layout
{
    use ComponentPathsTrait;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * The file which the current template is loaded from
     *
     * @var null|string
     */
    public $file = null;

    /**
     * The directory where the current template is loaded from
     *
     * @var null|string
     */
    public $directory = null;

    /**
     * All assigned currently assigned to the template.
     *
     * @var array Associative Assigned Variable Array
     */
    protected $assigned_variables = array();

    /**
     * All engines that can be used for templates.
     *
     * @var array of engines
     */
    protected $engines = array();

    /**
     * All file extensions that can be used and are bound to a template engine.
     *
     * @var array of names of engines
     */
    protected $file_extensions = array();

    /**
     * whether the template engines are already called.
     *
     * @var bool True if loaded
     */
    protected $engines_loaded = false;

    /**
     * The currently selected template engine.
     *
     * @var TemplateEngine
     */
    protected $current_engine;

    /**
     * Standard Component method for initializing components after adding extensions
     */
    public function init()
    {
        $this->factory = Factory::getInstance();
    }

    /**
     * Retrieve a template file using a string and a directory and immediatly parse it to the output class.
     *
     * What template file gets loaded depends on the template engine that is being used.
     * PHP for example uses .php files. Providing this function with 'home/dashboard' will load the home/layout.dashboard.php file.
     * You can also provide no particular engine, and the manager will decide what template to load.
     * Remember that doing so will result in a LayoutException when multiple compatible files are found.
     *
     * @param string $file          File to load
     * @param array  $directories   Directories to load it from, uses componentPaths if none provided
     *
     * @return mixed
     * @throws LayoutException On error
     * @throws EventException
     * @throws Exception\ConfigException
     */
    public function display(string $file, array $directories = []): bool
    {
        $contents = $this->get($file, $directories);
        $event = Events::fireEvent('layoutDisplayEvent', $contents, $file, $directories);
        if (!$event->isCancelled())
            echo $event->contents;

        return true;
    }

    /**
     * Retrieve a template file using a string and a directory.
     *
     * What template file gets loaded depends on the template engine that is being used.
     * PHP for example uses .php files. Providing this function with 'home/dashboard' will load the home/layout.dashboard.php file.
     * You can also provide no particular engine, and the manager will decide what template to load.
     * Remember that doing so will result in a LayoutException when multiple compatible files are found.
     *
     * @param string $file File to load
     * @param array  $directories Directory to load it from
     *
     * @return string The output of the template
     * @throws LayoutException On error
     */
    public function get(string $file, array $directories = []): string
    {
        Logger::newLevel("Loading template file '".$file."'");

        // Determine what directories should be checked
        $directories = (empty($directories) ? $this->componentPaths : [3 => $directories]);

        // First load the template engines
        $this->loadTemplateEngines();

        // First retrieve the filePath
        if (is_null($this->current_engine)) {
            $this->setFileFromString($file, $directories, array_keys($this->file_extensions));
        } else {
            $this->setFileFromString($file, $directories, $this->current_engine->getFileExtensions());
        }

        // Then assign some basic variables for the template
        // @TODO: Implement csrfTokenName and csrfHash from security under layoutLoadEvent

        // Select an engine if one is not already selected
        if (is_null($this->current_engine)) {
            $this->current_engine = $this->getEngineFromExtension($this->getExtensionFromFile($this->file));
        }

        $this->current_engine->setDirectory($this->directory);

        // And run an Event to see what other parts have to say about it
        try {
            /** @var LayoutLoadEvent $event */
            $event = Events::fireEvent('layoutLoadEvent', $this->file, $this->directory, $this->current_engine, $this->assigned_variables);
            // @codeCoverageIgnoreStart
        } catch (EventException $e) {
           throw new LayoutException("layoutEvent threw exception: '".$e->getMessage()."''", 1);
           // @codeCoverageIgnoreEnd
        }

        // The event has been cancelled
        if ($event->isCancelled())
            return 'cancelled';

        // And re-fetch the data from the event
        $this->current_engine = $event->engine;
        $this->assigned_variables = $event->assigned_variables;

        Logger::stopLevel();

        // And finally run it
        if (file_exists($event->file)) {
            return $this->current_engine->get($event->file, $this->assigned_variables);
        }

        throw new LayoutException('The requested file was not found', 1);
    }

    /**
     * Retrieve a Template Engine from a File Extension.
     *
     * @param string $extension File extension to look for
     *
     * @return TemplateEngine
     * @throws LayoutException
     */
    public function getEngineFromExtension($extension): TemplateEngine
    {
        if (isset($this->file_extensions[strtolower($extension)]))
            return $this->engines[ $this->file_extensions[strtolower($extension)]];

        throw new LayoutException('Could not get Template Engine. No engine has corresponding file extension', 1);
    }

    /**
     * Retrieve the extension from a file string.
     *
     * @param string $fileString The path to the file
     *
     * @return string Extension of the file
     */
    public function getExtensionFromFile($fileString): string
    {
        return substr($fileString, strrpos($fileString, '.') + 1);
    }

    /**
     * Converts a layout string to a file using the directory and the used extensions.
     *
     * It will detect whether the file exists and choose a file according to the provided extensions
     *
     * @param string $string      The string used by a controller. eg: 'dashboard/home'
     * @param array  $directories The directories to search in for the template
     * @param array  $extensions  Extensions to use for this template. Eg array('php', 'tpl') etc.
     *
     * @return array File and directory
     * @throws LayoutException On error
     */
    public function getFileFromString(string $string, array $directories, array $extensions = []): array
    {
        // @TODO Malformed strings pass. Write better function
        if (strpbrk($string, "\\/?%*:|\"<>") === TRUE)
        {
            // @codeCoverageIgnoreStart
            throw new LayoutException('Could not get file. Invalid file string', 1);
            // @codeCoverageIgnoreEnd
        }

        // Set the file name and location
        $layoutSelector = explode('/', $string);
        if (count($layoutSelector) == 1) {
            $layoutSelector = 'layout.'.$layoutSelector[0];
        } else {
            // Get last file
            $file = end($layoutSelector);

            // Reset to start
            reset($layoutSelector);

            // Remove last value
            array_pop($layoutSelector);

            $layoutSelector[] = 'layout.'.$file;

            // And create the final value
            $layoutSelector = implode(DS, $layoutSelector);
        }

        // Iterate over componentPaths
        for ($i=Priority::getHighestPriority(); $i<=Priority::getLowestPriority(); $i++)
        {
            if (!isset($directories[$i]))
                continue;

            foreach ($directories[$i] as $directory)
            {
                // Then try and select a file
                $fileSelected = false;
                $selectedFile = null;
                foreach ($extensions as $extension) {
                    $file = $directory.DS.$layoutSelector.'.'.strtolower($extension);
                    $file = preg_replace('#/+#', '/', $file);
                    if (file_exists($file) && !$fileSelected) {
                        $selectedFile = $file;
                        $fileSelected = true;
                        Logger::log("Found matching file: '".$file."'");
                    } elseif (file_exists($file) && $fileSelected) {
                        throw new LayoutException('Could not select template. Multiple valid extensions detected. Can not choose.', 1);
                    }
                }

                if ($fileSelected)
                    return ['file' => $selectedFile, 'directory' => $directory];
            }
        }

        throw new LayoutException('Could not select template. No matching file found.');
    }

    /**
     * Converts a layout string to a file using the directory and the used extensions.
     * It also sets the file variable of this class.
     *
     * It will detect whether the file exists and choose a file according to the provided extensions
     *
     * @param string $string     The string used by a controller. eg: 'dashboard/home'
     * @param array  $directories  The directory to search in for the template
     * @param array  $extensions Extensions to use for this template. Eg array('php', 'tpl') etc.
     *
     * @throws LayoutException On error
     */
    public function setFileFromString($string, array $directories, $extensions = array())
    {
        $arr = $this->getFileFromString($string, $directories, $extensions);
        $this->file = $arr['file'];
        $this->directory = $arr['directory'];
    }

    /**
     * Get the current file to be loaded.
     *
     * @return null|string Path to the file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the file to be loaded.
     *
     * @param string $file Path to the file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Get the directory of the file to be loaded.
     *
     * @return null|string Path to the directory
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set the directory of the file to be loaded.
     *
     * @param string $directory Path to the directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Assign a variable for the template.
     *
     * @param string $key   Key of the variable
     * @param mixed  $value Value of the variable
     */
    public function assign($key, $value)
    {
        $this->assigned_variables[$key] = $value;
    }

    /**
     * Set the title of the template.
     *
     * @param string $title title of the template
     */
    public function setTitle($title)
    {
        $this->assigned_variables['title'] = $title;
    }

    /**
     * Get the title of the template.
     *
     * @return string|bool title of the template
     */
    public function getTitle()
    {
        if (!isset($this->assigned_variables['title']))
            return false;

        return $this->assigned_variables['title'];
    }

    /**
     * Set the engine for the next layout.
     *
     * @param string $name Name of the template engine
     *
     * @return bool true on success
     * @throws LayoutException on error
     */
    public function setEngine($name): bool
    {
        $this->loadTemplateEngines();
        if (isset($this->engines[$name])) {
            $this->current_engine = $this->engines[$name];
            Logger::log('Set the Template Engine to '.$name);

            return true;
        }
        throw new LayoutException('Could not set engine. Engine does not exist', 1);
    }

    /**
     * Get a loaded template engine.
     *
     * @param string $name Name of the template engine
     *
     * @return TemplateEngine
     * @throws LayoutException
     */
    public function getEngine(string $name): TemplateEngine
    {
        $this->loadTemplateEngines();
        if (isset($this->engines[$name])) {
            return $this->engines[$name];
        }
        throw new LayoutException('Could not return engine. Engine does not exist', 1);
    }

    /**
     * Register a new template engine.
     *
     * @param TemplateEngine $engineClass Object that implements the \FuzeWorks\TemplateEngine
     * @param string $engineName Name of the template engine
     * @param array $engineFileExtensions File extensions this template engine should be used for
     *
     * @return bool true on success
     * @throws LayoutException
     */
    public function registerEngine(TemplateEngine $engineClass, string $engineName, array $engineFileExtensions = []): bool
    {
        // First check if the engine already exists
        if (isset($this->engines[$engineName]))
            throw new LayoutException("Could not register engine. Engine '".$engineName."' already registered", 1);

        // Install it
        $this->engines[$engineName] = $engineClass;

        // Then install them
        foreach ($engineFileExtensions as $extension) {
            if (isset($this->file_extensions[strtolower($extension)])) {
                throw new LayoutException('Could not register engine. File extension already bound to engine', 1);
            }

            // And add it
            $this->file_extensions[strtolower($extension)] = $engineName;
        }

        // And log it
        Logger::log('Registered Template Engine: '.$engineName);

        return true;
    }

    /**
     * Load the template engines by sending a layoutLoadEngineEvent.
     * @throws LayoutException
     * @returns bool True on loading. False when already loaded
     */
    public function loadTemplateEngines(): bool
    {
        if (!$this->engines_loaded) {
            // Fire Engine Event
            try {
                Events::fireEvent('layoutLoadEngineEvent');
            } catch (Exception\EventException $e) {
                throw new LayoutException("Could not loadTemplateEngines. layoutLoadEngineEvent threw exception: '".$e->getMessage()."''", 1);
            }

            // Load the engines provided in this file
            // PHP Engine
            $this->registerEngine(new PHPEngine(), 'PHP', array('php'));

            // JSON Engine
            if (extension_loaded('json'))
                $this->registerEngine(new JsonEngine(), 'JSON', array('json'));

            // Latte Engine
            if (class_exists('\Latte\Engine', true))
                $this->registerEngine(new LatteEngine(), 'Latte', array('latte'));

            // Smarty Engine
            if (class_exists('\Smarty', true))
                $this->registerEngine(new SmartyEngine(), 'Smarty', array('tpl'));

            $this->engines_loaded = true;
            return true;
        }

        return false;
    }

    /**
     * Resets the layout manager to its default state.
     */
    public function reset()
    {
        if (!is_null($this->current_engine))
            $this->current_engine->reset();

        // Unload the engines
        $this->engines = array();
        $this->engines_loaded = false;
        $this->file_extensions = array();

        $this->current_engine = null;
        $this->assigned_variables = array();
        Logger::log('Reset the layout manager to its default state');
    }
}