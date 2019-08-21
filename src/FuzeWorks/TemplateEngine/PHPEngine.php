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

/**
 * Simple Template Engine that allows for PHP templates.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 */
class PHPEngine implements TemplateEngine
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

    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    public function get($file, $assigned_variables)
    {
        // First set all the variables
        $this->assigned_variables = $assigned_variables;
        $vars = $this->assigned_variables;
        $directory = $this->directory;

        // Preset assigned variables
        foreach ($vars as $key => $val)
            $$key = $val;

        // Then run the file
        if (!is_null($file)) {
            ob_start();
            include $file;

            return ob_get_clean();
        }
    }

    public function getFileExtensions(): array
    {
        return array('php');
    }

    public function reset(): bool
    {
        $this->directory = null;
        $this->assigned_variables = array();

        return true;
    }
}