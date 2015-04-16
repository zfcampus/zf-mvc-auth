<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\MvcAuth\Factory\AuthenticationOAuth2AdapterFactory;

class AuthenticationOAuth2AdapterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
    }


    public function invalidConfiguration()
    {
        return array(
            'empty'  => array(array()),
            'null'   => array(array('storage' => null)),
            'bool'   => array(array('storage' => true)),
            'int'    => array(array('storage' => 1)),
            'float'  => array(array('storage' => 1.1)),
            'string' => array(array('storage' => 'options')),
            'object' => array(array('storage' => (object) array('storage'))),
        );
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionForMissingOrInvalidStorage(array $config)
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException', 'Missing storage');
        AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
    }

    public function testCreatesInstanceFromValidConfiguration()
    {
        $config = array(
            'adapter' => 'pdo',
            'storage' => array(
                'adapter' => 'pdo',
                'dsn' => 'sqlite::memory:',
            ),
        );
        $adapter = AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\OAuth2Adapter', $adapter);
        $this->assertEquals(array('foo'), $adapter->provides());
    }
}
