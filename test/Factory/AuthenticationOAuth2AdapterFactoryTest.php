<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\OAuth2Adapter;
use ZF\MvcAuth\Factory\AuthenticationOAuth2AdapterFactory;

class AuthenticationOAuth2AdapterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
    }


    public function invalidConfiguration()
    {
        return [
            'empty'  => [[]],
            'null'   => [['storage' => null]],
            'bool'   => [['storage' => true]],
            'int'    => [['storage' => 1]],
            'float'  => [['storage' => 1.1]],
            'string' => [['storage' => 'options']],
            'object' => [['storage' => (object) ['storage']]],
        ];
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionForMissingOrInvalidStorage(array $config)
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('Missing storage');
        AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
    }

    public function testCreatesInstanceFromValidConfiguration()
    {
        $config = [
            'adapter' => 'pdo',
            'storage' => [
                'adapter' => 'pdo',
                'dsn' => 'sqlite::memory:',
            ],
        ];

        $this->services->expects($this->any())
            ->method('get')
            ->with($this->stringContains('Config'))
            ->will($this->returnValue([
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
            ]));

        $adapter = AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
        $this->assertInstanceOf(OAuth2Adapter::class, $adapter);
        $this->assertEquals(['foo'], $adapter->provides());
    }
}
