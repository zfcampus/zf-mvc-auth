<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit\Framework\TestCase;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Authentication\HttpAdapter;
use ZF\MvcAuth\Authentication\OAuth2Adapter;
use ZF\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory;

class AuthenticationAdapterDelegatorFactoryTest extends TestCase
{
    /**
     * @var AuthenticationAdapterDelegatorFactory
     */
    private $factory;
    /**
     * @var ServiceManager
     */
    private $services;

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
        $config = [];
        $this->services->setService('config', $config);

        $factory = $this->factory;

        $listener = $factory(
            $this->services,
            DefaultAuthenticationListener::class,
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals([], $listener->getAuthenticationTypes());
    }


    public function testReturnsListenerWithConfiguredAdapters()
    {
        $config = [
            // ensure top-level zf-oauth2 are available
            'zf-oauth2' => [
                'grant_types' => [
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ],
                'api_problem_error_response' => true,
            ],
            'zf-mvc-auth' => [
                'authentication' => [
                    'adapters' => [
                        'foo' => [
                            'adapter' => HttpAdapter::class,
                            'options' => [
                                'accept_schemes' => ['basic'],
                                'realm' => 'api',
                                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                            ],
                        ],
                        'bar' => [
                            'adapter' => OAuth2Adapter::class,
                            'storage' => [
                                'adapter' => 'pdo',
                                'dsn' => 'sqlite::memory:',
                            ],
                        ],
                        'baz' => [
                            'adapter' => 'UNKNOWN',
                        ],
                        'bat' => [
                            // intentionally empty
                        ],
                    ],
                ],
            ],
        ];
        $this->services->setService('config', $config);
        $this->services->setService(
            'authentication',
            $this->getMockBuilder(AuthenticationService::class)->getMock()
        );

        $factory = $this->factory;

        $listener = $factory(
            $this->services,
            DefaultAuthenticationListener::class,
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals([
            'foo-basic',
            'bar'
        ], $listener->getAuthenticationTypes());
    }
}
