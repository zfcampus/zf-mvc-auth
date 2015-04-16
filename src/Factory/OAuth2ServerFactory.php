<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use MongoClient;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\Server as OAuth2Server;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
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
     * @param ServiceLocatorInterface $services 
     * @return OAuth2Server
     * @throws ServiceNotCreatedException when missing details necessary to
     *     create instance and/or dependencies.
     */
    public static function factory(array $config, ServiceLocatorInterface $services)
    {
        $storage = self::createStorage($config, $services);

        $oauth2Server = new OAuth2Server(self::createStorage($config, $services));

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $oauth2Server->addGrantType(new ClientCredentials($storage));

        // Add the "Authorization Code" grant type
        $oauth2Server->addGrantType(new AuthorizationCode($storage));

        return $oauth2Server;
    }

    /**
     * Create and return an OAuth2 storage adapter instance.
     * 
     * @param array $config 
     * @param ServiceLocatorInterface $services 
     * @return PdoAdapter|MongoAdapter
     */
    private static function createStorage(array $config, ServiceLocatorInterface $services)
    {
        if (! isset($config['adapter']) || ! is_string($config['adapter'])) {
            throw new ServiceNotCreatedException(
                'Missing or invalid storage adapter information for OAuth2'
            );
        }

        switch (strtolower($config['adapter'])) {
            case 'pdo':
                return self::createPdoAdapter($config);
            case 'mongo':
                return self::createMongoAdapter($config, $services);
            default:
                throw new ServiceNotCreatedException('Invalid storage adapter type for OAuth2');
        }
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
     * @param ServiceLocatorInterface $services 
     * @return MongoAdapter
     */
    private static function createMongoAdapter(array $config, ServiceLocatorInterface $services)
    {
        return new MongoAdapter(
            self::createMongoDatabase($config, $services),
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
        $options  = isset($config['options'])  ? $config['options'] : array();

        return array(
            'dsn'      => $config['dsn'],
            'username' => $username,
            'password' => $password,
            'options'  => $options,
        );
    }

    /**
     * Create and return a Mongo database instance.
     * 
     * @param array $config 
     * @param ServiceLocatorInterface $services 
     * @return \MongoDB
     */
    private static function createMongoDatabase(array $config, ServiceLocatorInterface $services)
    {
        $dbLocatorName = isset($config['locator_name'])
            ? $config['locator_name']
            : 'MongoDB';

        if ($services->has($dbLocatorName)) {
            return $services->get($dbLocatorName);
        }

        if (! isset($config['database'])) {
            throw new ServiceNotCreatedException(
                'Missing OAuth2 Mongo database configuration'
            );
        }

        $options = isset($config['options']) ? $config['options'] : array();
        $options['connect'] = false;
        $server  = isset($config['dsn']) ? $config['dsn'] : null;
        $mongo   = new MongoClient($server, $options);
        return $mongo->{$config['database']};
    }

    /**
     * Retrieve oauth2-server-php storage settings configuration.
     *
     * @return array
     */
    private static function getOAuth2ServerConfig($config)
    {
        $oauth2ServerConfig = array();
        if (isset($config['storage_settings']) && is_array($config['storage_settings'])) {
            $oauth2ServerConfig = $config['storage_settings'];
        }

        return $oauth2ServerConfig;
    }
}
