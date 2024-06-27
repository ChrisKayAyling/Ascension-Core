<?php

namespace test;

use Ascension\Core;
use Ascension\HTTP;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{

    /**
     * @return void
     */
    public function setUp(): void
    {

    }

    /**
     * testCoreHasPrivateTwigEnvironment
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateTwigEnvironment()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("TwigEnvironment");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasPrivateUserTwigEnvironment
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateUserTwigEnvironment()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("UserTwigEnvironment");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasPrivateUserTwigEnvironment
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateTwigCustomTemplating()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("TwigCustomTemplating");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasPrivateTwigCustomTemplatingHasKeys
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateTwigCustomTemplatingHasKeys()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $property = $reflectionCoreClass->getProperty("TwigCustomTemplating");

        $this->assertArrayHasKey("Header", $property->getValue());
        $this->assertArrayHasKey("Navigation", $property->getValue());
        $this->assertArrayHasKey("Footer", $property->getValue());

    }

    /**
     * testCoreHasPublicResourceArrayProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPublicResourceArrayProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("Resources");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasPrivateTwigTemplatesProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateTwigTemplatesProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("TwigTemplates");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasPrivateViewDataProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateViewDataProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("ViewData");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasPublicDebugProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPublicDebugProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("Debug");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasTemplateDevelopmentModeProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasTemplateDevelopmentModeProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("TemplateDevelopmentMode");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->getValue(), true);
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasPublicUserDataProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPublicUserDataProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("UserData");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->getValue(), array());
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasPrivateHTTPProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPrivateHTTPProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("HTTP");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasPublicRouteProperty
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasPublicRouteProperty()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getProperty("Route");

        $propertyVal = $properties->getValue();

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($propertyVal['controller'], 'Home');
        $this->assertEquals($propertyVal['method'], 'main');
        $this->assertEquals($propertyVal['id'], 0);
        $this->assertEquals($propertyVal['content'], 'plain');
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasMethodAscend
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodAscend()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("ascend");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodRequestHandler
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodRequestHandler()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("requestHandler");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodAddDataStorageObjects
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodAddDataStorageObjects()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("addDataStorageObjects");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethod__saneSys
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__saneSys()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__saneSys");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethod__setupSys
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__setupSys()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__setupSys");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethod__loadSettings
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__loadSettings()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__loadSettings");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasMethod__loader
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__loader()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__loader");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethod__output
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__output()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__output");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodcreate_rmq_worker
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodcreate_rmq_worker()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("create_rmq_worker");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodtelemetry
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodtelemetry()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("telemetry");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodGetCommon
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodGetCommon()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("getCommon");

        $this->assertEquals($properties->isPrivate(), true);
        $this->assertEquals($properties->isStatic(), true);
    }


    /**
     * testCoreHasMethodGetCommon
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__injectResource()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__injectResource");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodGetCommon
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethod__removeResource()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("__removeResource");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /**
     * testCoreHasMethodGetCommon
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreHasMethodAddCustomTemplate()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $properties = $reflectionCoreClass->getMethod("addCustomTemplate");

        $this->assertEquals($properties->isPublic(), true);
        $this->assertEquals($properties->isStatic(), true);
    }

    /* Test Request Handler Route identification */

    /**
     * testCoreHasMethodGetCommon
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreRequestHandler()
    {

        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $reflectionClass = new \ReflectionClass('Ascension\Core');

        $reflectionMethod = $reflectionClass->getMethod('requestHandler');

        $reflectionMethod->invoke($reflectionClass, '');

        $property = $reflectionClass->getProperty('Route');

        $values = $property->getValue();

        $this->assertEquals($values['content'], 'json');

    }

    /**
     * Test upper case for controller when lower case specified in request URI
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testRequestHandler() {

        define("ROOT", 'tests/Mock');
        define("DS", "/");

        $_SERVER['REQUEST_URI'] = "/test/method";

        $reflectionClass = new \ReflectionClass('Ascension\Core');

        $reflectionMethod = $reflectionClass->getMethod('requestHandler');

        $reflectionMethod->invoke($reflectionClass, '');

        $property = $reflectionClass->getProperty('Route');

        $values = $property->getValue();

        $this->assertEquals($values['controller'], "Test");
        $this->assertEquals($values['method'], "method");
        $this->assertEquals($values['id'], 0);
    }


    /**
     * test Common data has agreed values
     *
     * Check property accessor has been set to correct value.
     */
    public function testCoreCommonPropertiesHasValues()
    {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $reflectionMethod = $reflectionCoreClass->getMethod("getCommon");

        $values = $reflectionMethod->invoke($reflectionCoreClass, '');

        $this->assertArrayHasKey('Server', $values);

        $this->assertArrayHasKey('SERVER_ADDR', $values['Server']);
        $this->assertArrayHasKey('REMOTE_ADDR', $values['Server']);
        $this->assertArrayHasKey('HTTP_USER_AGENT', $values['Server']);
        $this->assertArrayHasKey('SESSION_ID', $values['Server']);

        $this->assertArrayHasKey('General', $values);

        $this->assertArrayHasKey('DayShort', $values['General']);
        $this->assertArrayHasKey('Day', $values['General']);
        $this->assertArrayHasKey('DayNumber', $values['General']);
        $this->assertArrayHasKey('MonthShort', $values['General']);
        $this->assertArrayHasKey('MonthNumber', $values['General']);
        $this->assertArrayHasKey('Year', $values['General']);
    }


    /* Resource Injectors *********************/

    /**
     * Test resource injector sets value in static self::Resources
     * @return void
     * @throws \ReflectionException
     */
    public function testCore__injectResource() {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        $reflectionMethod = $reflectionCoreClass->getMethod("__injectResource");

        $reflectionMethod->invoke($reflectionCoreClass, "Test", array("TestKey" => "TestValue"));

        $a = $reflectionCoreClass->getProperty("Resources");
        $b = $a->getValue();

        $this->assertArrayHasKey("Test", $b);
    }

    /**
     * Remove resource from self::$Resources by Name.
     * @return void
     * @throws \ReflectionException
     */
    public function testCore__removeResource() {
        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        /* Add Resource */
        $reflectionMethodAdd = $reflectionCoreClass->getMethod("__injectResource");
        $reflectionMethodAdd->invoke($reflectionCoreClass, "Test", array("TestKey" => "TestValue"));

        $a = $reflectionCoreClass->getProperty("Resources");
        $b = $a->getValue();

        $this->assertArrayHasKey("Test", $b);

        /* Remove Resource */

        $reflectionMethodRemove = $reflectionCoreClass->getMethod("__removeResource");
        $reflectionMethodRemove->invoke($reflectionCoreClass, "Test");

        $a = $reflectionCoreClass->getProperty("Resources");
        $b = $a->getValue();

        $this->assertArrayNotHasKey("Test", $b);
    }

    public function testCore__loader() {

        /* include test classes */
        include_once('Mock/lib/Test/Repository/Repository.php');
        include_once('Mock/lib/Test/Controller/Controller.php');

        $reflectionCoreClass = new \ReflectionClass('Ascension\Core');

        /* Mock our route */
        $reflectionCoreClass->setStaticPropertyValue("Route", array(
            'controller' => 'Test',
            'method' => 'main'
        ));

        $reflectionMethod = $reflectionCoreClass->getMethod("__loader");

        $reflectionMethod->invoke($reflectionCoreClass);

        $dataValues = $reflectionCoreClass->getProperty("Accessor");
        $templateValues = $reflectionCoreClass->getProperty("templates");

        /*@todo complete test */
        $this->assertEquals(true, true);
    }

}