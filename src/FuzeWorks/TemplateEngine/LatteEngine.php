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

use FuzeWorks\Exception\LayoutException;
use FuzeWorks\Core;
use Latte\Engine as Latte;

/**
 * Wrapper for the Latte Engine from Nette Framework.
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 */
class LatteEngine implements TemplateEngine
{

    /**
     * Instance of the Latte Engine
     * 
     * @var Latte\Engine The Latte Engine to be used
     */
    protected $latte;

    /**
     * Set the directory of the current template.
     *
     * @param string $directory Template Directory
     * @throws LayoutException
     */
    public function setDirectory($directory)
    {
        if (class_exists('\Latte\Engine', true))
        {
            // If possible, load Latte\Engine
            $this->latte = new Latte;
            $this->latte->setTempDirectory(realpath(Core::$tempDir));
        }
        else
        {
            throw new LayoutException("Could not load LatteEngine. Is it installed or Composer not loaded?", 1);
        }
    }

    /**
     * Handle and retrieve a template file.
     *
     * @param string $file               Template File
     * @param array  $assigned_variables All the variables used in this layout
     *
     * @return string Output of the template
     */
    public function get($file, $assigned_variables)
    {
        return $this->latte->renderToString($file, $assigned_variables);
    }

    /**
     * Retrieve the file extensions that this template engine uses.
     *
     * @return array All used extensions. eg: array('php')
     */
    public function getFileExtensions(): array
    {
        return array('latte');
    }

    /**
     * Reset the template engine to its default state, so it can be used again clean.
     */
    public function reset(): bool
    {
        // If possible, load Latte\Engine
        $this->latte = null;

        return true;
    }
}