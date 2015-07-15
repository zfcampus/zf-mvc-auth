<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\MvcAuth\Factory\NamedOAuth2ServerFactory;
use ZF\MvcAuth\Factory\OAuth2ServerFactory;
use Zend\ServiceManager\ServiceManager;

class NamedOAuth2ServerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->setUpConfig(new ServiceManager());
        $this->factory  = new NamedOAuth2ServerFactory();
    }

    public function setUpConfig($services)
    {
        $services->setService('Config', [
            'zf-oauth2' => [
                'storage' => 'ZFTest\OAuth2\TestAsset\MockAdapter',
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
                        'test' => [
                            'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => [
                                'storage' => 'ZFTest\OAuth2\TestAsset\MockAdapter',
                                'route'   => 'test',
                            ],
                        ],
                        'test2' => [
                            'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => [
                                'storage' => 'ZFTest\OAuth2\TestAsset\MockAdapter',
                                'route'   => 'test2',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $oauth2StorageAdapter = $this->getMockBuilder('OAuth2\Storage\Memory')
            ->disableOriginalConstructor(true)
            ->getMock();

        $services->setService(
            'ZFTest\OAuth2\TestAsset\MockAdapter',
            $oauth2StorageAdapter
        );
        return $services;
    }

    public function testCallingReturnedFactoryMultipleTimesWithNoArgumentReturnsSameServerInstance()
    {
        $factory = $this->factory->__invoke($this->services);
        $server  = $factory();
        $this->assertSame($server, $factory());
    }

    public function testCallingReturnedFactoryMultipleTimesWithSameArgumentReturnsSameServerInstance()
    {
        $factory = $this->factory->__invoke($this->services);
        $server  = $factory('test');
        $this->assertSame($server, $factory('test'));
    }

    public function testCallingReturnedFactoryMultipleTimesWithDifferentArgumentsReturnsDifferentInstances()
    {
        $factory = $this->factory->__invoke($this->services);
        $server  = $factory('test');
        $this->assertNotSame($server, $factory());
        $this->assertNotSame($server, $factory('test2'));
    }

    public function testCallingReturnedFactoryWithUnrecognizedArgumentReturnsApplicationWideInstance()
    {
        $factory = $this->factory->__invoke($this->services);
        $server  = $factory();
        $this->assertSame($server, $factory('unknown'));
    }
}
