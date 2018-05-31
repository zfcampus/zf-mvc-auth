<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use MongoClient;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\OpenID\GrantType\AuthorizationCode as OpenIDAuthorizationCodeGrantType;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\JwtBearer;
use OAuth2\Server as OAuth2Server;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use ZF\OAuth2\Adapter\MongoAdapter;
use ZF\OAuth2\Adapter\PdoAdapter;

final class OAuth2ServerFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create and return a fully configured OAuth2 server instance.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return \OAuth2\Server
     */
    public static function factory(array $config, ContainerInterface $container)
    {
        $allConfig    = $container->get('config');
        $oauth2Config = isset($allConfig['zf-oauth2']) ? $allConfig['zf-oauth2'] : [];
        $options      = self::marshalOptions($oauth2Config);

        $oauth2Server = new OAuth2Server(
            self::createStorage(array_merge($oauth2Config, $config), $container),
            $options
        );

        return self::injectGrantTypes($oauth2Server, $oauth2Config['grant_types'], $options);
    }

    /**
     * Create and return an OAuth2 storage adapter instance.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return array|MongoAdapter|PdoAdapter A PdoAdapter, MongoAdapter, or array of storage instances.
     */
    private static function createStorage(array $config, ContainerInterface $container)
    {
        if (isset($config['adapter']) && is_string($config['adapter'])) {
            return self::createStorageFromAdapter($config['adapter'], $config, $container);
        }

        if (isset($config['storage'])
            && (is_string($config['storage']) || is_array($config['storage']))
        ) {
            return self::createStorageFromServices($config['storage'], $container);
        }

        throw new ServiceNotCreatedException('Missing or invalid storage adapter information for OAuth2');
    }

    /**
     * Create an OAuth2 storage instance based on the adapter specified.
     *
     * @param string $adapter One of "pdo" or "mongo".
     * @param array $config
     * @param ContainerInterface $container
     * @return MongoAdapter|PdoAdapter
     */
    private static function createStorageFromAdapter($adapter, array $config, ContainerInterface $container)
    {
        switch (strtolower($adapter)) {
            case 'pdo':
                return self::createPdoAdapter($config);
            case 'mongo':
                return self::createMongoAdapter($config, $container);
            default:
                throw new ServiceNotCreatedException('Invalid storage adapter type for OAuth2');
        }
    }

    /**
     * Creates the OAuth2 storage from services.
     *
     * @param string|string[] $storage A string or an array of strings; each MUST be a valid service.
     * @param ContainerInterface $container
     * @return array
     */
    private static function createStorageFromServices($storage, ContainerInterface $container)
    {
        $storageServices = [];

        if (is_string($storage)) {
            $storageServices[] = $storage;
        }

        if (is_array($storage)) {
            $storageServices = $storage;
        }

        $storage = [];
        foreach ($storageServices as $key => $service) {
            $storage[$key] = $container->get($service);
        }
        return $storage;
    }

    /**
     * Create and return an OAuth2 PDO adapter.
     *
     * @param array $config
     * @return PdoAdapter
     */
    private static function createPdoAdapter(array $config)
    {
        return new PdoAdapter(
            self::createPdoConfig($config),
            self::getOAuth2ServerConfig($config)
        );
    }

    /**
     * Create and return an OAuth2 Mongo adapter.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return MongoAdapter
     */
    private static function createMongoAdapter(array $config, ContainerInterface $container)
    {
        return new MongoAdapter(
            self::createMongoDatabase($config, $container),
            self::getOAuth2ServerConfig($config)
        );
    }

    /**
     * Create and return the configuration needed to create a PDO instance.
     *
     * @param array $config
     * @return array
     */
    private static function createPdoConfig(array $config)
    {
        if (! isset($config['dsn'])) {
            throw new ServiceNotCreatedException(
                'Missing DSN for OAuth2 PDO adapter creation'
            );
        }

        $username = isset($config['username']) ? $config['username'] : null;
        $password = isset($config['password']) ? $config['password'] : null;
        $options  = isset($config['options']) ? $config['options'] : [];

        return [
            'dsn'      => $config['dsn'],
            'username' => $username,
            'password' => $password,
            'options'  => $options,
        ];
    }

    /**
     * Create and return a Mongo database instance.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return \MongoDB
     */
    private static function createMongoDatabase(array $config, ContainerInterface $container)
    {
        $dbLocatorName = isset($config['locator_name'])
            ? $config['locator_name']
            : 'MongoDB';

        if ($container->has($dbLocatorName)) {
            return $container->get($dbLocatorName);
        }

        if (! isset($config['database'])) {
            throw new ServiceNotCreatedException(
                'Missing OAuth2 Mongo database configuration'
            );
        }

        $options = isset($config['options']) ? $config['options'] : [];
        $options['connect'] = false;
        $server  = isset($config['dsn']) ? $config['dsn'] : null;
        $mongo   = new MongoClient($server, $options);
        return $mongo->{$config['database']};
    }

    /**
     * Retrieve oauth2-server-php storage settings configuration.
     *
     * @param $config
     *
     * @return array
     */
    private static function getOAuth2ServerConfig($config)
    {
        $oauth2ServerConfig = [];
        if (isset($config['storage_settings']) && is_array($config['storage_settings'])) {
            $oauth2ServerConfig = $config['storage_settings'];
        }

        return $oauth2ServerConfig;
    }

    /**
     * Marshal OAuth2\Server options from zf-oauth2 configuration.
     *
     * @param array $config
     * @return array
     */
    private static function marshalOptions(array $config)
    {
        $enforceState = array_key_exists('enforce_state', $config)
            ? $config['enforce_state']
            : true;
        $allowImplicit = isset($config['allow_implicit'])
            ? $config['allow_implicit']
            : false;
        $accessLifetime = isset($config['access_lifetime'])
            ? $config['access_lifetime']
            : 3600;
        $audience = isset($config['audience'])
            ? $config['audience']
            : '';
        $options = isset($config['options'])
            ? $config['options']
            : [];

        return  array_merge([
            'access_lifetime' => $accessLifetime,
            'allow_implicit'  => $allowImplicit,
            'audience'        => $audience,
            'enforce_state'   => $enforceState,
        ], $options);
    }

    /**
     * Inject grant types into the OAuth2\Server instance, based on zf-oauth2
     * configuration.
     *
     * @param OAuth2Server $server
     * @param array $availableGrantTypes
     * @param array $options
     * @return OAuth2Server
     */
    private static function injectGrantTypes(OAuth2Server $server, array $availableGrantTypes, array $options)
    {
        if (array_key_exists('client_credentials', $availableGrantTypes)
            && $availableGrantTypes['client_credentials'] === true
        ) {
            $clientOptions = [];
            if (isset($options['allow_credentials_in_request_body'])) {
                $clientOptions['allow_credentials_in_request_body'] = $options['allow_credentials_in_request_body'];
            }

            // Add the "Client Credentials" grant type (it is the simplest of the grant types)
            $server->addGrantType(new ClientCredentials($server->getStorage('client_credentials'), $clientOptions));
        }

        if (array_key_exists('authorization_code', $availableGrantTypes)
            && $availableGrantTypes['authorization_code'] === true
        ) {
            $authCodeClass = array_key_exists('use_openid_connect', $options) && $options['use_openid_connect'] === true
                ? OpenIDAuthorizationCodeGrantType::class
                : AuthorizationCode::class;

            // Add the "Authorization Code" grant type (this is where the oauth magic happens)
            $server->addGrantType(new $authCodeClass($server->getStorage('authorization_code')));
        }

        if (array_key_exists('password', $availableGrantTypes) && $availableGrantTypes['password'] === true) {
            // Add the "User Credentials" grant type
            $server->addGrantType(new UserCredentials($server->getStorage('user_credentials')));
        }

        if (array_key_exists('jwt', $availableGrantTypes) && $availableGrantTypes['jwt'] === true) {
            // Add the "JWT Bearer" grant type
            $server->addGrantType(new JwtBearer($server->getStorage('jwt_bearer'), $options['audience']));
        }

        if (array_key_exists('refresh_token', $availableGrantTypes) && $availableGrantTypes['refresh_token'] === true) {
            $refreshOptions = [];
            if (isset($options['always_issue_new_refresh_token'])) {
                $refreshOptions['always_issue_new_refresh_token'] = $options['always_issue_new_refresh_token'];
            }
            if (isset($options['refresh_token_lifetime'])) {
                $refreshOptions['refresh_token_lifetime'] = $options['refresh_token_lifetime'];
            }
            if (isset($options['unset_refresh_token_after_use'])) {
                $refreshOptions['unset_refresh_token_after_use'] = $options['unset_refresh_token_after_use'];
            }

            // Add the "Refresh Token" grant type
            $server->addGrantType(new RefreshToken($server->getStorage('refresh_token'), $refreshOptions));
        }

        return $server;
    }
}
