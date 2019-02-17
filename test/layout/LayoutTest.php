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
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

use FuzeWorks\Factory;
use FuzeWorks\Layout;
use FuzeWorks\Events;
use FuzeWorks\Priority;

/**
 * Class LayoutTest.
 *
 * This test will test the layout manager and the default TemplateEngines
 */
class LayoutTest extends LayoutTestAbstract
{

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var Layout
     */
    protected $layout;

    public function setUp()
    {
        // Load the factory first
        $this->factory = Factory::getInstance();
        $this->layout = Factory::getInstance()->layouts;
        $this->layout->reset();
    }

    /**
     * @covers \FuzeWorks\Layout::init
     * @covers \FuzeWorks\LayoutComponent::getClasses
     */
    public function testComponent()
    {
        // Load the component
        $component = new FuzeWorks\LayoutComponent();

        // Prepare container
        $configurator = new \FuzeWorks\Configurator();
        $configurator->addComponent($component);
        $configurator->setTempDirectory(dirname(__DIR__) . '/temp');
        $configurator->setLogDirectory(dirname(__DIR__) . '/temp');


        // Create container
        $container = $configurator->createContainer();

        // Init container;
        $this->assertTrue(property_exists($container, 'layouts'));
        $this->assertInstanceOf('FuzeWorks\Layout', $container->layouts);
    }

    /**
     * @covers \FuzeWorks\Layout::setFile
     * @covers \FuzeWorks\Layout::getFile
     * @covers \FuzeWorks\Layout::setDirectory
     * @covers \FuzeWorks\Layout::getDirectory
     */
    public function testFileAndDirectory()
    {
        // File test
        $file = 'test.php';
        $this->layout->setFile($file);
        $this->assertEquals($file, $this->layout->getFile());

        // Directory test
        $directory = 'test'.DS.'templates'.DS.'testFileAndDirectory';
        $this->layout->addComponentPath($directory);
        $this->assertEquals([$directory], $this->layout->getComponentPaths());
    }

    /**
     * @covers \FuzeWorks\Layout::getExtensionFromFile
     */
    public function testGetFileExtensions()
    {
        // Test getting php files
        $this->assertEquals('php', $this->layout->getExtensionFromFile('class.test.php'));
        $this->assertEquals('php', $this->layout->getExtensionFromFile('class.test.org.php'));
        $this->assertEquals('random', $this->layout->getExtensionFromFile('class.test.something.random'));
    }

    /**
     * @depends testGetFileExtensions
     * @covers \FuzeWorks\Layout::setFileFromString
     * @covers \FuzeWorks\Layout::getFileFromString
     */
    public function testGetFilePath()
    {
        // Extensions to be used in this test
        $extensions = array('php', 'json');

        // Prepare variables
        $directories = [3 => ['test'.DS.'templates'.DS.'testGetFilePath']];

        // Basic path
        $this->layout->setFileFromString('test', $directories, $extensions);
        $this->assertEquals('test'.DS.'templates'.DS.'testGetFilePath'.DS.'layout.test.php', $this->layout->getFile());

        // Alternate file extension
        $this->layout->setFileFromString('JSON', $directories, $extensions);
        $this->assertEquals('test'.DS.'templates'.DS.'testGetFilePath'.DS.'layout.JSON.json', $this->layout->getFile());

        // Complex deeper path
        $this->layout->setFileFromString('Deeper/test', $directories, $extensions);
        $this->assertEquals('test'.DS.'templates'.DS.'testGetFilePath'.DS.'Deeper'.DS.'layout.test.php', $this->layout->getFile());
    }

    /**
     * @depends testGetFilePath
     * @expectedException FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::setFileFromString
     * @covers \FuzeWorks\Layout::getFileFromString
     */
    public function testMalformedPaths()
    {
        // Extensions to be used in this test
        $extensions = array('php', 'json');

        $this->layout->setFileFromString('test?\/<>', [3=>['test|?/*<>']], $extensions);
    }

    /**
     * @expectedException FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::setFileFromString
     * @covers \FuzeWorks\Layout::getFileFromString
     */
    public function testMissingDirectory()
    {
        // Directory that does not exist
        $this->layout->setFileFromString('test', [3=>['test'.DS.'templates'.DS.'doesNotExist']], array('php'));
    }

    /**
     * @expectedException FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::setFileFromString
     * @covers \FuzeWorks\Layout::getFileFromString
     */
    public function testMissingFile()
    {
        $this->layout->setFileFromString('test', [3=>['test'.DS.'templates'.DS.'testMissingFile']], array('php'));
    }

    /**
     * @expectedException FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::setFileFromString
     * @covers \FuzeWorks\Layout::getFileFromString
     */
    public function testUnknownFileExtension()
    {
        $this->layout->setFileFromString('test', [3=>['test'.DS.'templates'.DS.'testUnknownFileExtension']], array('php'));
    }

    /**
     * @covers \FuzeWorks\Layout::get
     */
    public function testLayoutGet()
    {
        // Directory of these tests
        $directories = ['test'.DS.'templates'.DS.'testLayoutGet'];

        $this->assertEquals('Retrieved Data', $this->layout->get('test', $directories));
    }

    /**
     * @covers \FuzeWorks\Layout::get
     */
    public function testLayoutGetRepeat()
    {
        $directories = ['test'.DS.'templates'.DS.'testLayoutGetRepeat'];
        $this->assertEquals('First Data', $this->layout->get('first', $directories));
        $this->assertEquals('Second Data', $this->layout->get('second', $directories));
    }

    /**
     * @covers \FuzeWorks\Layout::get
     * @covers \FuzeWorks\Event\LayoutLoadEvent::init
     */
    public function testLayoutGetCancelledEvent()
    {
        $directories = ['test'.DS.'templates'.DS.'testLayoutGetCancelledEvent'];
        Events::addListener(function($event){
            $event->setCancelled(true);
        }, 'layoutLoadEvent', Priority::NORMAL);
        $this->assertEquals('cancelled', $this->layout->get('test', $directories));
    }

    /**
     * @expectedException FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::get
     * @covers \FuzeWorks\Event\LayoutLoadEvent::init
     */
    public function testLayoutGetEventWrongFile()
    {
        $directories = ['test'.DS.'templates'.DS.'testLayoutGetEventWrongFile'];
        Events::addListener(function($event){
            $event->file = 'does_not_exist';
        }, 'layoutLoadEvent', Priority::NORMAL);
        $this->layout->get('test', $directories);
    }

    /**
     * @covers \FuzeWorks\Layout::display
     * @covers \FuzeWorks\Event\LayoutDisplayEvent::init
     */
    public function testLayoutDisplayEventAndDisplay()
    {
        // Directory of these tests
        $directories = ['test'.DS.'templates'.DS.'testLayoutGet'];
        Events::addListener(function($event){
            $this->assertEquals('Retrieved Data', $event->contents);
        }, 'layoutDisplayEvent', Priority::NORMAL);

        ob_start();
        $this->layout->display('test', $directories);
        $this->assertEquals('Retrieved Data', ob_get_contents());
        ob_end_clean();
    }

    /**
     * @covers \FuzeWorks\Layout::reset
     * @covers \FuzeWorks\Layout::setTitle
     * @covers \FuzeWorks\Layout::getTitle
     */
    public function testReset()
    {
        $this->layout->setDirectories([3=>['test'.DS.'templates'.DS.'testLayoutGet']]);

        // First the the variables
        $this->layout->setTitle('Test Title');

        // Test if they are actually set
        $this->assertEquals('Test Title', $this->layout->getTitle());
        $this->assertEquals(['test'.DS.'templates'.DS.'testLayoutGet'], $this->layout->getComponentPaths());

        // Reset the layout system
        $this->layout->reset();

        // Test for default values
        $this->assertEquals(['test'.DS.'templates'.DS.'testLayoutGet'], $this->layout->getComponentPaths());
    }

    /**
     * @covers \FuzeWorks\Layout::getEngineFromExtension
     */
    public function testGetEngineFromExtension()
    {
        $this->layout->loadTemplateEngines();

        // Test all the default engines
        $this->assertInstanceOf('FuzeWorks\TemplateEngine\PHPEngine', $this->layout->getEngineFromExtension('php'));
        $this->assertInstanceOf('FuzeWorks\TemplateEngine\JsonEngine', $this->layout->getEngineFromExtension('json'));
        $this->assertInstanceOf('FuzeWorks\TemplateEngine\SmartyEngine', $this->layout->getEngineFromExtension('tpl'));
    }

    /**
     * @depends testGetEngineFromExtension
     * @expectedException FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::getEngineFromExtension
     */
    public function testGetEngineFromExtensionFail()
    {
        $this->layout->getEngineFromExtension('faulty');
    }

    /**
     * @covers \FuzeWorks\Layout::loadTemplateEngines
     */
    public function testLoadTemplateEngines()
    {
        // Load first try
        $this->assertTrue($this->layout->loadTemplateEngines());

        // Try second time
        $this->assertFalse($this->layout->loadTemplateEngines());

        // Reset
        $this->layout->reset();
        $this->assertTrue($this->layout->loadTemplateEngines());
    }

    /**
     * @covers \FuzeWorks\Layout::loadTemplateEngines
     * @expectedException \FuzeWorks\Exception\LayoutException
     */
    public function testLoadLoadEngineEvent()
    {
        Events::addListener(function($event){
            $this->assertInstanceOf('\FuzeWorks\Event\NotifierEvent', $event);
            throw new \FuzeWorks\Exception\EventException('Forcing failure in loadTemplateEngines()');
        }, 'layoutLoadEngineEvent', Priority::NORMAL);

        $this->layout->loadTemplateEngines();
    }

    /**
     * @depends testGetEngineFromExtension
     * @covers \FuzeWorks\Layout::registerEngine
     */
    public function testCustomEngine()
    {
        // Create the engine
        $mock = $this->getMockBuilder('FuzeWorks\TemplateEngine\TemplateEngine')->getMock();

        // Add the methods
        $mock->method('get')->willReturn('output');

        // And listen for usage
        $mock->expects($this->once())->method('get')->with('test'.DS.'templates'.DS.'testCustomEngine'.DS.'layout.test.test');

        // Register the engine
        $this->layout->registerEngine($mock, 'Custom', array('test'));

        // And run the engine
        $this->assertEquals('output', $this->layout->get('test', ['test'.DS.'templates'.DS.'testCustomEngine']));
    }


    /**
     * @depends testCustomEngine
     * @expectedException \FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::registerEngine
     */
    public function testExistingCustomEngine()
    {
        // Create mock
        $mock = $this->getMockBuilder('FuzeWorks\TemplateEngine\TemplateEngine')->getMock();
        $mock->method('get')->willReturn('output');

        // And register
        $this->assertTrue($this->layout->registerEngine($mock, 'Custom', ['test']));

        // And re-register
        $this->layout->registerEngine($mock, 'Custom', ['othertest']);
    }

    /**
     * @depends testCustomEngine
     * @expectedException \FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::registerEngine
     */
    public function testCustomEngineWithExistingExtensions()
    {
        // Create mock
        $mock = $this->getMockBuilder('FuzeWorks\TemplateEngine\TemplateEngine')->getMock();
        $mock->method('get')->willReturn('output');

        // Register initial
        $this->assertTrue($this->layout->registerEngine($mock, 'Custom', ['test']));

        // Register failing
        $this->layout->registerEngine($mock, 'other', ['test']);
    }

    /**
     * @depends testCustomEngine
     * @covers \FuzeWorks\Layout::setEngine
     * @covers \FuzeWorks\Layout::getEngine
     */
    public function testSetEngine()
    {
        // Create mocks
        $mock = $this->getMockBuilder('FuzeWorks\TemplateEngine\TemplateEngine')->getMock();
        $mock2 = $this->getMockBuilder('FuzeWorks\TemplateEngine\TemplateEngine')->getMock();
        $mock->method('get')->willReturn('output');
        $mock2->method('get')->willReturn('output2');

        // Register custom engine
        $this->assertTrue($this->layout->registerEngine($mock, 'custom', ['test']));
        $this->assertTrue($this->layout->registerEngine($mock2, 'custom2', ['test2']));

        // Test getEngine
        $this->assertInstanceOf(get_class($mock), $this->layout->getEngine('custom'));
        $this->assertInstanceOf(get_class($mock2), $this->layout->getEngine('custom2'));

        // Test setEngine1
        $this->assertTrue($this->layout->setEngine('custom'));
        $this->assertTrue($this->layout->setEngine('custom2'));
    }

    /**
     * @depends testSetEngine
     * @expectedException \FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::setEngine
     */
    public function testSetInvalidEngine()
    {
        $this->layout->setEngine('invalid');
    }

    /**
     * @depends testSetEngine
     * @expectedException \FuzeWorks\Exception\LayoutException
     * @covers \FuzeWorks\Layout::getEngine
     */
    public function testGetInvalidEngine()
    {
        $this->layout->getEngine('invalid');
    }

    /**
     * @covers \FuzeWorks\Layout::registerEngine
     */
    public function testEnginesLoadLayout()
    {
        // Directory of these tests
        $directories = ['test'.DS.'templates'.DS.'testEngines'];

        // First the PHP Engine
        $this->assertEquals('PHP Template Check', $this->layout->get('php', $directories));
        $this->layout->reset();

        // Then the JSON Engine
        $this->assertEquals('JSON Template Check', json_decode($this->layout->get('json', $directories), true)[0]);
        $this->layout->reset();

        // And the Smarty Engine
        $this->assertEquals('Smarty Template Check', $this->layout->get('smarty', $directories));
    }

    /**
     * @covers \FuzeWorks\Layout::assign
     */
    public function testEngineVariables()
    {
        // Directory of these tests
        $directories = ['test'.DS.'templates'.DS.'testEngineVariables'];

        // First the PHP Engine
        $this->layout->assign('key', 'value');
        $this->assertEquals('value', $this->layout->get('php', $directories));
        $this->layout->reset();

        // Then the JSON Engine
        $this->layout->assign('key', 'value');
        $this->assertEquals('value', json_decode($this->layout->get('json', $directories), true)['data']['key']);
        $this->layout->reset();

        // And the Smarty Engine
        $this->layout->assign('key', 'value');
        $this->assertEquals('value', $this->layout->get('smarty', $directories));
    }
}

class MockEngine {

}
