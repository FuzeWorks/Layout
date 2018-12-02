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
 * Template Engine that exports all assigned variables as JSON.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 */
class JsonEngine implements TemplateEngine
{
    /**
     * All the currently assigned variables.
     *
     * @var array
     */
    protected $assigned_variables = array();

    /**
     * Whether the JSON data should be parsed or left as is.
     *
     * @var bool true if to be parsed
     */
    protected static $string_return = true;

    /**
     * Whether the JSON data should be parsed or left as is.
     *
     * @param true if to be parsed
     */
    public static function returnAsString($boolean = true)
    {
        self::$string_return = $boolean;
    }

    public function setDirectory($directory)
    {
        return true;
    }

    public function get($file, $assigned_variables)
    {
        // First set all the variables
        $this->assigned_variables = $assigned_variables;

        // First set up the JSON array
        $json = array();

        // Look up if a file is provided
        if (!is_null($file)) {
            // Retrieve a file
            $string = file_get_contents($file);
            $json = json_decode($string, true);
        }

        // Then assign all variables
        $json['data'] = $this->assigned_variables;

        // And return it
        if (self::$string_return) {
            return json_encode($json);
        }

        return $json;
    }

    public function getFileExtensions(): array
    {
        return array('json');
    }

    public function reset(): bool
    {
        $this->assigned_variables = array();
        self::$string_return = true;

        return true;
    }
}