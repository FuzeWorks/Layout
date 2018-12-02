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
 
namespace FuzeWorks\TemplateEngine;

use FuzeWorks\Core;
use Smarty;

/**
 * Wrapper for the Smarty Template Engine.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 */
class SmartyEngine implements TemplateEngine
{
    /**
     * The currently used directory by the template.
     *
     * @var string
     */
    protected $directory;

    /**
     * All the currently assigned variables.
     *
     * @var array
     */
    protected $assigned_variables = array();

    /**
     * Instance of the Smarty Template Engine.
     *
     * @var \Smarty
     */
    protected $smartyInstance;

    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    public function get($file, $assigned_variables)
    {
        // First set all the variables
        $this->assigned_variables = $assigned_variables;

        // Load Smarty
        $this->loadSmarty();

        // Set the directory
        $this->smartyInstance->setTemplateDir($this->directory);

        // Then assign all variables
        foreach ($this->assigned_variables as $key => $value) {
            $this->smartyInstance->assign($key, $value);
        }

        // And finally, load the template
        return $this->smartyInstance->fetch($file);
    }

    /**
     * Loads a Smarty instance if it is not already loaded.
     */
    private function loadSmarty()
    {
        if (is_null($this->smartyInstance)) {
            $this->smartyInstance = new Smarty();

            // Then prepare all variables
            $this->smartyInstance->setCompileDir(Core::$tempDir . DS . 'Smarty' . DS . 'Compile');
            $this->smartyInstance->setCacheDir(Core::$tempDir . DS . 'Smarty');
        }
    }

    public function getFileExtensions(): array
    {
        return array('tpl');
    }

    public function reset(): bool
    {
        $this->smartyInstance = null;
        $this->directory = null;
        $this->assigned_variables = array();

        return true;
    }

    /**
     * Retrieve a value from Smarty.
     *
     * @param string $name Variable name
     *
     * @return mixed Variable Value
     *
     * @throws \FuzeWorks\LayoutException on error
     */
    public function __get($name)
    {
        // First load Smarty
        $this->loadSmarty();

        return $this->smartyInstance->$name;
    }

    /**
     * Set a variable in Smarty.
     *
     * @param string $name  Variable Name
     * @param mixed  $value Variable Value
     *
     * @throws \FuzeWorks\LayoutException on error
     */
    public function __set($name, $value)
    {
        // First load Smarty
        $this->loadSmarty();

        $this->smartyInstance->$name = $value;
    }

    /**
     * Calls a function in Smarty.
     *
     * @param string     $name   Name of the function to be called
     * @param Paramaters $params Parameters to be used
     *
     * @return mixed Function output
     */
    public function __call($name, $params)
    {
        // First load Smarty
        $this->loadSmarty();

        return call_user_func_array(array($this->smartyInstance, $name), $params);
    }
}