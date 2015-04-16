<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory;

class AuthenticationAdapterDelegatorFactoryTest extends TestCase
{
    public function setUp()
    {
        // Actual service manager instance, as multiple services may be
        // requested; simplifies testing.
        $this->services = new ServiceManager();
        $this->factory  = new AuthenticationAdapterDelegatorFactory();
        $this->listener = $listener = new DefaultAuthenticationListener();
        $this->callback = function () use ($listener) {
            return $listener;
        };
    }

    public function testReturnsListenerWithNoAdaptersWhenNoAdaptersAreInConfiguration()
    {
        $config = array();
        $this->services->setService('Config', $config);

        $listener = $this->factory->createDelegatorWithName(
            $this->services,
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener',
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener',
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals(array(), $listener->getAuthenticationTypes());
    }


    public function testReturnsListenerWithConfiguredAdapters()
    {
        $config = array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'adapters' => array(
                        'foo' => array(
                            'adapter' => 'ZF\MvcAuth\Authentication\HttpAdapter',
                            'options' => array(
                                'accept_schemes' => array('basic'),
                                'realm' => 'api',
                                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                            ),
                        ),
                        'bar' => array(
                            'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => array(
                                'adapter' => 'pdo',
                                'dsn' => 'sqlite::memory:',
                            ),
                        ),
                        'baz' => array(
                            'adapter' => 'UNKNOWN',
                        ),
                        'bat' => array(
                            // intentionally empty
                        ),
                    ),
                ),
            ),
        );
        $this->services->setService('Config', $config);
        $this->services->setService('authentication', $this->getMock('Zend\Authentication\AuthenticationService'));

        $listener = $this->factory->createDelegatorWithName(
            $this->services,
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener',
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener',
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals(array(
            'foo-basic',
            'bar'
        ), $listener->getAuthenticationTypes());
    }
}
