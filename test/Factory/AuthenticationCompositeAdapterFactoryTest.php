<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Factory\AuthenticationCompositeAdapterFactory;

class AuthenticationCompositeAdapterFactoryTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface|MockObject
     */
    protected $serviceLocator;

    public function setUp()
    {
        $this->serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
    }

    public function invalidConfiguration()
    {
        return array(
            'empty'  => array(array()),
            'null'   => array(array('adapters' => null)),
            'bool'   => array(array('adapters' => true)),
            'int'    => array(array('adapters' => 1)),
            'float'  => array(array('adapters' => 1.1)),
            'string' => array(array('adapters' => 'options')),
            'object' => array(array('adapters' => (object) array('storage'))),
        );
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionForMissingOrInvalidStorage(array $config)
    {
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException', 'No adapters configured');
        AuthenticationCompositeAdapterFactory::factory('foo', $config, $this->serviceLocator);
    }

    public function testCreatesInstanceFromValidConfiguration()
    {
        $config = array(
            'adapters' => array('foo', 'bar'),
        );

        $fooAdapter = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $barAdapter = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $fooAdapter
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue(array('foo')))
        ;
        $barAdapter
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue(array('bar')))
        ;

        $this->serviceLocator->expects($this->any())
            ->method('get')
            ->with($this->logicalOr(
                'zf-mvc-auth-authentication-adapters-foo',
                'zf-mvc-auth-authentication-adapters-bar'
            ))
            ->will($this->returnCallback(function($name) use ($fooAdapter, $barAdapter) {
                switch($name) {
                    case 'zf-mvc-auth-authentication-adapters-foo' :
                        return $fooAdapter;
                    case 'zf-mvc-auth-authentication-adapters-bar' :
                        return $barAdapter;
                }
            }));

        $adapter = AuthenticationCompositeAdapterFactory::factory('foobar', $config, $this->serviceLocator);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\CompositeAdapter', $adapter);
        $this->assertEquals(array('foo', 'bar', 'foobar'), $adapter->provides());
    }
}
