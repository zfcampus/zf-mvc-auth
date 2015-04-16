<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Factory\DefaultAuthenticationListenerFactory;

class DefaultAuthenticationListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory(
            'ZF\MvcAuth\Authentication\AuthHttpAdapter',
            'ZF\MvcAuth\Factory\DefaultAuthHttpAdapterFactory'
        );
        $this->services->setFactory(
            'ZF\MvcAuth\ApacheResolver',
            'ZF\MvcAuth\Factory\ApacheResolverFactory'
        );
        $this->services->setFactory(
            'ZF\MvcAuth\FileResolver',
            'ZF\MvcAuth\Factory\FileResolverFactory'
        );
        $this->factory  = new DefaultAuthenticationListenerFactory();
    }

    public function testCreatingOAuth2ServerFromStorageService()
    {
        $adapter = $this->getMockBuilder('OAuth2\Storage\Pdo')->disableOriginalConstructor()->getMock();

        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', array(
            'zf-oauth2' => array(
                'storage' => 'TestAdapter'
            )
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithNoConfigServiceReturnsListenerWithNoHttpAdapter()
    {
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingMvcAuthSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array());
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAuthenticationSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array('zf-mvc-auth' => array()));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingHttpSubSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array('zf-mvc-auth' => array('authentication' => array())));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAcceptSchemesRaisesException()
    {
        $this->services->setService(
            'config',
            array(
                'zf-mvc-auth' => array(
                    'authentication' => array(
                        'http' => array(),
                    ),
                ),
            )
        );
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException');
        $listener = $this->factory->createService($this->services);
    }

    public function testCallingFactoryWithBasicSchemeButMissingHtpasswdValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'test',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithDigestSchemeButMissingHtdigestValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'test',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithBasicSchemeAndHtpasswdValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/../TestAsset/htpasswd'
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertContains('basic', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithDigestSchemeAndHtdigestValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertContains('digest', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithCustomAuthenticationTypesReturnsListenerComposingThem()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ),
                    'types' => array(
                        'token',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertEquals(array('digest', 'token'), $listener->getAuthenticationTypes());
    }

    public function testFactoryWillUsePreconfiguredOAuth2ServerInstanceProvidedByZfOAuth2()
    {
        // Configure mock OAuth2 Server
        $oauth2Server = $this->getMockBuilder('OAuth2\Server')->disableOriginalConstructor()->getMock();
        $this->services->setService('ZF\OAuth2\Service\OAuth2Server', $oauth2Server);
        
        // Configure mock OAuth2 Server storage adapter
        $adapter = $this->getMockBuilder('OAuth2\Storage\Pdo')->disableOriginalConstructor()->getMock();
        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', array(
            'zf-oauth2' => array(
                'storage' => 'TestAdapter'
            )
        ));
        
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);

        $r = new ReflectionProperty($listener, 'adapters');
        $r->setAccessible(true);
        $adapters = $r->getValue($listener);
        $adapter = array_shift($adapters);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\OAuth2Adapter', $adapter);
        $this->assertAttributeSame($oauth2Server, 'oauth2Server', $adapter);
    }

    public function testCallingFactoryWithAuthenticationMapReturnsListenerComposingMap()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'map' => array(
                        'Testing\V1' => 'oauth2',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeEquals(array('Testing\V1' => 'oauth2'), 'authMap', $listener);
    }
}
