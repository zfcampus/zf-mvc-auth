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
    private $factory;
    private $services;

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
        $this->services->setService('config', [
            'zf-oauth2' => [
                'storage' => 'TestAdapter',
                'grant_types' => [
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ],
                'api_problem_error_response' => true,
            ]
        ]);

        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithNoConfigServiceReturnsListenerWithNoHttpAdapter()
    {
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingMvcAuthSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', []);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAuthenticationSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', ['zf-mvc-auth' => []]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingHttpSubSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', ['zf-mvc-auth' => ['authentication' => []]]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAcceptSchemesRaisesException()
    {
        $this->services->setService(
            'config',
            [
                'zf-mvc-auth' => [
                    'authentication' => [
                        'http' => [],
                    ],
                ],
            ]
        );
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException');
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
    }

    public function testCallingFactoryWithBasicSchemeButMissingHtpasswdValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', [
            'zf-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['basic'],
                        'realm' => 'test',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;


        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithDigestSchemeButMissingHtdigestValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', [
            'zf-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['digest'],
                        'realm' => 'test',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithBasicSchemeAndHtpasswdValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'zf-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['basic'],
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/../TestAsset/htpasswd'
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertContains('basic', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithDigestSchemeAndHtdigestValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'zf-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['digest'],
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertContains('digest', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithCustomAuthenticationTypesReturnsListenerComposingThem()
    {
        $authenticationService = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'zf-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['digest'],
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ],
                    'types' => [
                        'token',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertEquals(['digest', 'token'], $listener->getAuthenticationTypes());
    }

    public function testFactoryWillUsePreconfiguredOAuth2ServerInstanceProvidedByZfOAuth2()
    {
        // Configure mock OAuth2 Server
        $oauth2Server = $this->getMockBuilder('OAuth2\Server')->disableOriginalConstructor()->getMock();
        // Wrap it in a factory
        $this->services->setService('ZF\OAuth2\Service\OAuth2Server', function () use ($oauth2Server) {
            return $oauth2Server;
        });

        // Configure mock OAuth2 Server storage adapter
        $adapter = $this->getMockBuilder('OAuth2\Storage\Pdo')->disableOriginalConstructor()->getMock();

        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', [
            'zf-oauth2' => [
                'storage' => 'TestAdapter'
            ]
        ]);

        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
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
        $this->services->setService('config', [
            'zf-mvc-auth' => [
                'authentication' => [
                    'map' => [
                        'Testing\V1' => 'oauth2',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeEquals(['Testing\V1' => 'oauth2'], 'authMap', $listener);
    }
}
