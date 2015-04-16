<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\MvcAuth\Factory\OAuth2ServerFactory;

class OAuth2ServerFactoryTest extends TestCase
{
    public function testRaisesExceptionIfAdapterIsMissing()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException', 'storage adapter');
        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'dsn' => 'sqlite::memory:',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testRaisesExceptionCreatingPdoBackedServerIfDsnIsMissing()
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException', 'Missing DSN');
        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'pdo',
            'username' => 'username',
            'password' => 'password',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testCanCreatePdoAdapterBackedServer()
    {
        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testCanCreateMongoBackedServerUsingMongoFromServices()
    {
        if (! class_exists('MongoDB')) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $mongoClient = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor(true)
            ->getMock();
        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('MongoService'))
            ->will($this->returnValue(true));
        $services->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('MongoService'))
            ->will($this->returnValue($mongoClient));

        $config = array(
            'adapter' => 'mongo',
            'locator_name' => 'MongoService',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testRaisesExceptionCreatingMongoBackedServerIfDatabaseIsMissing()
    {
        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'mongo',
        );

        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException', 'database');
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testCanCreateMongoAdapterBackedServer()
    {
        if (! class_exists('MongoDB')) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'mongo',
            'database' => 'zf-mvc-auth-test',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }
}
