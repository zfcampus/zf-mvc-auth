<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use MongoClient;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\OAuth2Adapter;

final class AuthenticationOAuth2AdapterFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create and return an OAuth2Adapter instance.
     * 
     * @param string|array $type 
     * @param array $config 
     * @param ServiceLocatorInterface $services 
     * @return OAuth2Adapter
     * @throws ServiceNotCreatedException when missing details necessary to
     *     create instance and/or dependencies.
     */
    public static function factory($type, array $config, ServiceLocatorInterface $services)
    {
        if (! isset($config['storage']) || ! is_array($config['storage'])) {
            throw new ServiceNotCreatedException('Missing storage details for OAuth2 server');
        }

        return new OAuth2Adapter(
            OAuth2ServerFactory::factory($config['storage'], $services),
            $type
        );
    }
}
