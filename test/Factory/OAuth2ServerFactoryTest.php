<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use MongoDB;
use OAuth2\GrantType;
use OAuth2\OpenID\GrantType\AuthorizationCode as OpenIDAuthorizationCodeGrantType;
use OAuth2\Server as OAuth2Server;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ZF\MvcAuth\Factory\OAuth2ServerFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceManager;

class OAuth2ServerFactoryTest extends TestCase
{
    protected function getOAuth2Options()
    {
        return [
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
        ];
    }

    protected function mockConfig($services)
    {
        $services->setService('config', $this->getOAuth2Options());
        return $services;
    }

    public function testRaisesExceptionIfAdapterIsMissing()
    {
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'dsn' => 'sqlite::memory:',
        ];

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('storage adapter');

        OAuth2ServerFactory::factory($config, $services);
    }

    public function testRaisesExceptionCreatingPdoBackedServerIfDsnIsMissing()
    {
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'pdo',
            'username' => 'username',
            'password' => 'password',
        ];

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('Missing DSN');

        OAuth2ServerFactory::factory($config, $services);
    }

    public function testCanCreatePdoAdapterBackedServer()
    {
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf(OAuth2Server::class, $server);
    }

    public function testCanCreateMongoBackedServerUsingMongoFromServices()
    {
        if (! class_exists(MongoDB::class)) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $services = $this->mockConfig(new ServiceManager());
        $mongoClient = $this->getMockBuilder(MongoDB::class)
            ->disableOriginalConstructor(true)
            ->getMock();
        $services->setService('MongoService', $mongoClient);

        $config = [
            'adapter' => 'mongo',
            'locator_name' => 'MongoService',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf(OAuth2Server::class, $server);
    }

    public function testRaisesExceptionCreatingMongoBackedServerIfDatabaseIsMissing()
    {
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'mongo',
        ];

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('database');

        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testCanCreateMongoAdapterBackedServer()
    {
        if (! class_exists(MongoDB::class)) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'mongo',
            'database' => 'zf-mvc-auth-test',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf(OAuth2Server::class, $server);
    }

    public function disableGrantType()
    {
        return [
            'client_credentials' => ['client_credentials'],
            'authorization_code' => ['authorization_code'],
            'password'           => ['password'],
            'refresh_token'      => ['refresh_token'],
            'jwt'                => ['jwt'],
        ];
    }

    /**
     * @dataProvider disableGrantType
     * @group 77
     */
    public function testServerCreatedHasDefaultGrantTypesAsDefinedByOAuth2Module($disable)
    {
        $options  = $this->getOAuth2Options();
        $options['zf-oauth2']['grant_types'][$disable] = false;
        $options['zf-oauth2']['storage_settings'] = [
            'client_table'        => 'CLIENTS',
            'code_table'          => 'AUTHORIZATION_CODES',
            'user_table'          => 'USERS',
            'refresh_token_table' => 'REFRESH_TOKENS',
            'jwt_table'           => 'JWT',
        ];

        $services = new ServiceManager();
        $services->setService('config', $options);

        $config = [
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf(OAuth2Server::class, $server);

        $grantTypes = $server->getGrantTypes();
        foreach ($options['zf-oauth2']['grant_types'] as $type => $enabled) {
            // jwt is hinted differently in OAuth2\Server
            if ($type === 'jwt') {
                $type = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
            }

            // If the grant type is not enabled, it should not be present in
            // the returned grant types.
            if (! $enabled) {
                $this->assertArrayNotHasKey($type, $grantTypes);
                continue;
            }

            // If it *is* enabled, it MUST be present.
            $this->assertArrayHasKey($type, $grantTypes);

            switch ($type) {
                case 'client_credentials':
                    $class = GrantType\ClientCredentials::class;
                    break;
                case 'authorization_code':
                    $class = GrantType\AuthorizationCode::class;
                    break;
                case 'password':
                    $class = GrantType\UserCredentials::class;
                    break;
                case 'urn:ietf:params:oauth:grant-type:jwt-bearer':
                    $class = GrantType\JwtBearer::class;
                    break;
                case 'refresh_token':
                    $class = GrantType\RefreshToken::class;
                    break;
                default:
                    $this->fail(sprintf('Unknown grant type: %s!', $type));
                    break;
            }

            // and have an instance of the appropriate class.
            $this->assertInstanceOf($class, $grantTypes[$type]);
        }

        // Now verify that storage settings are also merged in, which was the
        // original issue.
        $storage = $server->getStorage('scope');
        $r = new ReflectionProperty($storage, 'config');
        $r->setAccessible(true);
        $storageConfig = $r->getValue($storage);
        foreach ($options['zf-oauth2']['storage_settings'] as $key => $value) {
            $this->assertArrayHasKey($key, $storageConfig);
            $this->assertEquals($value, $storageConfig[$key]);
        }
    }

    public function testAllowsUsingOpenIDConnectGrantTypeViaConfiguration()
    {
        $options  = $this->getOAuth2Options();
        $options['zf-oauth2']['options']['use_openid_connect'] = true;
        $options['zf-oauth2']['storage_settings'] = [
            'client_table'        => 'CLIENTS',
            'code_table'          => 'AUTHORIZATION_CODES',
            'user_table'          => 'USERS',
            'refresh_token_table' => 'REFRESH_TOKENS',
            'jwt_table'           => 'JWT',
        ];

        $services = new ServiceManager();
        $services->setService('config', $options);

        $config = [
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf(OAuth2Server::class, $server);

        $grantTypes = $server->getGrantTypes();
        $this->assertArrayHasKey('authorization_code', $grantTypes);
        $this->assertInstanceOf(OpenIDAuthorizationCodeGrantType::class, $grantTypes['authorization_code']);
    }
}
