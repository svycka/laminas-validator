<?php

/**
 * @see       https://github.com/laminas/laminas-validator for the canonical source repository
 * @copyright https://github.com/laminas/laminas-validator/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-validator/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Validator;

use Laminas\I18n\Validator\Alpha;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Between;
use Laminas\Validator\StaticValidator;
use Laminas\Validator\ValidatorPluginManager;

/**
 * @category   Laminas
 * @package    Laminas_Validator
 * @subpackage UnitTests
 * @group      Laminas_Validator
 */
class StaticValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Alpha */
    public $validator;

    /**
     * Whether an error occurred
     *
     * @var bool
     */
    protected $errorOccurred = false;

    /**
     * Creates a new validation object for each test method
     *
     * @return void
     */
    public function setUp()
    {
        AbstractValidator::setDefaultTranslator(null);
        StaticValidator::setPluginManager(null);
        $this->validator = new Alpha();
    }

    public function tearDown()
    {
        AbstractValidator::setDefaultTranslator(null);
        AbstractValidator::setMessageLength(-1);
    }

    /**
     * Ignores a raised PHP error when in effect, but throws a flag to indicate an error occurred
     *
     * @param  integer $errno
     * @param  string  $errstr
     * @param  string  $errfile
     * @param  integer $errline
     * @param  array   $errcontext
     * @return void
     */
    public function errorHandlerIgnore($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        $this->errorOccurred = true;
    }

    public function testCanSetGlobalDefaultTranslator()
    {
        $translator = new TestAsset\Translator();
        AbstractValidator::setDefaultTranslator($translator);
        $this->assertSame($translator, AbstractValidator::getDefaultTranslator());
    }

    public function testGlobalDefaultTranslatorUsedWhenNoLocalTranslatorSet()
    {
        $this->testCanSetGlobalDefaultTranslator();
        $this->assertSame(AbstractValidator::getDefaultTranslator(), $this->validator->getTranslator());
    }

    public function testLocalTranslatorPreferredOverGlobalTranslator()
    {
        $this->testCanSetGlobalDefaultTranslator();
        $translator = new TestAsset\Translator();
        $this->validator->setTranslator($translator);
        $this->assertNotSame(AbstractValidator::getDefaultTranslator(), $this->validator->getTranslator());
    }

    public function testMaximumErrorMessageLength()
    {
        $this->assertEquals(-1, AbstractValidator::getMessageLength());
        AbstractValidator::setMessageLength(10);
        $this->assertEquals(10, AbstractValidator::getMessageLength());

        $loader = new TestAsset\ArrayTranslator();
        $loader->translations = array(
            Alpha::INVALID => 'This is the translated message for %value%',
        );
        $translator = new TestAsset\Translator();
        $translator->getPluginManager()->setService('default', $loader);
        $translator->addTranslationFile('default', null);

        $this->validator->setTranslator($translator);
        $this->assertFalse($this->validator->isValid(123));
        $messages = $this->validator->getMessages();
        $this->assertTrue(array_key_exists(Alpha::INVALID, $messages));
        $this->assertEquals('This is...', $messages[Alpha::INVALID]);
    }

    public function testSetGetMessageLengthLimitation()
    {
        AbstractValidator::setMessageLength(5);
        $this->assertEquals(5, AbstractValidator::getMessageLength());

        $valid = new Between(1, 10);
        $this->assertFalse($valid->isValid(24));
        $message = current($valid->getMessages());
        $this->assertTrue(strlen($message) <= 5);
    }

    public function testSetGetDefaultTranslator()
    {
        $translator = new TestAsset\Translator();
        AbstractValidator::setDefaultTranslator($translator);
        $this->assertSame($translator, AbstractValidator::getDefaultTranslator());
    }

    /* plugin loading */

    public function testLazyLoadsValidatorPluginManagerByDefault()
    {
        $plugins = StaticValidator::getPluginManager();
        $this->assertInstanceOf('Laminas\Validator\ValidatorPluginManager', $plugins);
    }

    public function testCanSetCustomPluginManager()
    {
        $plugins = new ValidatorPluginManager();
        StaticValidator::setPluginManager($plugins);
        $this->assertSame($plugins, StaticValidator::getPluginManager());
    }

    public function testPassingNullWhenSettingPluginManagerResetsPluginManager()
    {
        $plugins = new ValidatorPluginManager();
        StaticValidator::setPluginManager($plugins);
        $this->assertSame($plugins, StaticValidator::getPluginManager());
        StaticValidator::setPluginManager(null);
        $this->assertNotSame($plugins, StaticValidator::getPluginManager());
    }

    public function testExecuteValidWithParameters()
    {
        $this->assertTrue(StaticValidator::execute(5, 'Between', array(1, 10)));
        $this->assertTrue(StaticValidator::execute(5, 'Between', array('min' => 1, 'max' => 10)));
    }
}
