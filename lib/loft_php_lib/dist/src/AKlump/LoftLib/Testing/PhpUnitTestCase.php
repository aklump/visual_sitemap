<?php

namespace AKlump\LoftLib\Testing;

/**
 * Class PhpUnitTestCase
 *
 * To utilize this class add the following to the top of your test classes:
 *
 * @code
 *   use \AKlump\LoftLib\Testing\PhpUnitTestCase;
 *   use \AKlump\Money\Money;
 *
 *   class MoneyTest extends PhpUnitTestCase {
 *   ...
 * @endcode
 *
 * You MUST create a method called createObj() that looks something like this:
 * @code
 *   protected function createObj() {
 *     list($config, $db, $data, $dir) = $this->objArgs;
 *     $this->obj = new Money($config, $db, $data, $dir);
 *   }
 * @endcode
 *
 * And setUp() MUST do something like this:
 * @code
 *   $this->objArgs = [
 *     Yaml::parse(file_get_contents($this->dir . '/config.yml')),
 *     null,
 *     new Data();
 *     dirname(__FILE__) . '/../../testdataset';
 *   ];
 *   $this->createObj();
 * @endcode
 *
 * @package AKlump\LoftLib\Testing
 */
class PhpUnitTestCase extends \PHPUnit_Framework_TestCase {

    static $db;
    static $dbInstaller;

    public function assertProtectedPropertySame($control, $instance, $property)
    {
        $reflection = new \ReflectionClass($instance);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $this->assertSame($control, $property->getValue($instance));
    }

    public function assertXMLEquals($control, $xml)
    {
        if (is_array($control)) {
            $subject = (array) $xml;
        }
        else {
            $subject = (string) $xml;
        }

        return $this->assertEquals($control, $subject);
    }

    public function assertXMLHasAttribute($attribute, $xml)
    {
        $attr = (array) $xml->attributes();
        if (!array_key_exists('@attributes', $attr)) {
            $this->fail('No attributes exist.');
        }
        $this->assertArrayHasKey($attribute, $attr['@attributes']);
    }

    public function assertXMLHasChild($child, $xml)
    {
        $this->assertArrayHasKey($child, (array) $xml->children());
    }

    /**
     * Call a non-public method on $this->obj
     *
     * @param string $method The non-public method on $this->obj to call
     * @param... Additional args will be sent to the method.
     *
     * @return mixed
     */
    protected function callAsPublic($method)
    {
        $args = func_get_args();
        $method = array_shift($args);
        $reflector = new \ReflectionClass(get_class($this->obj));
        $method = $reflector->getMethod($method);
        $method->setAccessible('public');

        return $method->invokeArgs($this->obj, $args);
    }

    /**
     * Call a non-public method using arguments passed by reference.
     *
     * @param string $method
     * @param array  $args An array of arguments, the elements of which must be references (see code example).
     *
     * @return mixed
     *
     * @code
     *   // where the private method is like this:
     *   private function walk($method, &$collection)
     *   ...
     *   // write your test like this:
     *   $collection = [[], []];
     *   $args = ['stampWithDateTime', &$collection];
     *   $this->callAsPublicByReference('walk', $args);
     * @endcode
     */
    protected function callAsPublicByReference($method, &$args)
    {
        $reflection = new \ReflectionClass(get_class($this->obj));
        $method = $reflection->getMethod($method);
        $method->setAccessible('public');

        return $method->invokeArgs($this->obj, $args);
    }

    /**
     * Return a non-public property of $this->obj
     *
     * @param string $property
     *
     * @return mixed
     */
    protected function getAsPublic($property)
    {
        $reflection = new \ReflectionClass(get_class($this->obj));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($this->obj);
    }

    /**
     * Return $this->obj, mocked with $method set to return $return.
     *
     * In order to use this you must create do the following in your setUp method.
     *
     * @param $method
     * @param $return  This can be an array or a scalar or you can pass it one of:
     *                 - $this->returnValueMap();
     *                 - $this->returnCallback();
     *                 - $this->returnArgument();
     *                 - $this->returnSelf();
     *                 - $this->returnOnConsecutiveCalls();
     *
     * @code
     *   $this->objArgs = [$do, $re, $mi];
     *   $this->obj = new SomeObject($do, $re, $mi);
     * @endcode
     */
    protected function getMockedObj($method, $return)
    {
        $obj = $this->getMock(get_class($this->obj), [$method], $this->objArgs);
        $return = $this->wrapReturnValueAsNecessary($return);
        $obj->expects($this->any())
            ->method($method)
            ->will($return);

        return $obj;
    }

    /**
     * Return a Mockable instance with only $method mocked to return $return.
     *
     * This can be used for dependencies passed to $this->obj
     *
     * @param string $method
     * @param mixed  $return
     *
     * @return mixed
     *
     * Often you will create a Mocked dependency of $this->obj, this following code shows how this is done, and then
     * reinjected into $this->obj.
     * @code
     *   // Replace one of Money dependencies with a mocked $db.
     *   $this->objArgs[1] = $this->getMockable('fetchAll', [
     *      // History
     *      ['target_key' => 'value', 'target_id' => 'checking', 'value' => "500"],
     *   ]);
     *
     *   // Now refresh $this->obj with the new db Mock.
     *   $this->createObj();
     *   ... the $db object in Money is a mocked db with the method fetchAll ready to return our history array.
     * @endcode
     */
    protected function getMockable($method, $return)
    {
        $mock = $this->getMock('Mockable', [$method]);
        $return = $this->wrapReturnValueAsNecessary($return);
        $mock->method($method)->will($return);

        return $mock;
    }

    /**
     * Destroy then create anew a sandbox directory for temporary file test operations.
     *
     * Put this in your setUp() method usually and use $this->sb as directory to the sandbox.
     *
     * @return $this
     */
    protected function createSandbox()
    {
        $this->sb = sys_get_temp_dir() . '/' . str_replace('\\', '_', get_class($this)) . '/sb/';
        $this->sb = rtrim($this->sb, '/') . '/';
        $this->destroySandbox();
        $this->assertNotSame(false, mkdir($this->sb, 0700, true));

        return $this;
    }

    /**
     * Place this in tearDown();
     */
    protected function destroySandbox()
    {
        if ($this->sb) {
            $this->assertNotSame(false, system("test -e $this->sb && chmod -R u=rwx $this->sb && rm -r $this->sb"));
        }
    }

    /**
     * Empty the sandbox if it exists, otherwise do nothing.
     *
     * @return $this
     */
    protected function emptySandbox()
    {
        if (isset($this->sb)) {
            $this->createSandbox($this->sb);
        }

        return $this;
    }

    private function wrapReturnValueAsNecessary($return)
    {
        if (is_callable($return)) {
            return $this->returnCallback($return);
        }
        elseif (!is_object($return)) {
            return $this->returnValue($return);
        }

        return $return;
    }

    public function useDatabase()
    {
        static::$dbInstaller->destroy()->install();
    }
}


/**
 * Class Mockable
 *
 * @see     PhpUnitTestCase::getMockable().
 *
 * @package AKlump\LoftLib\Testing
 */
class Mockable {

}

