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

namespace FuzeWorks\Event;

use FuzeWorks\Event;

/**
 * Event that gets loaded when a layout is loaded.
 *
 * Use this to cancel the loading of a layout, or change the file or engine of a layout
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 */
class LayoutLoadEvent extends Event
{
    /**
     * The directory of the layout to be loaded.
     *
     * @var string
     */
    public $directory;

    /**
     * The file of the layout to be loaded.
     *
     * @var string
     */
    public $file;

    /**
     * The engine the file will be loaded with.
     *
     * @var object
     */
    public $engine;

    /**
     * The assigned variables to the template.
     *
     * @var array
     */
    public $assigned_variables;

    public function init($file, $directory, $engine, $assigned_variables)
    {
        $this->file = $file;
        $this->directory = $directory;
        $this->engine = $engine;
        $this->assigned_variables = $assigned_variables;
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
}
