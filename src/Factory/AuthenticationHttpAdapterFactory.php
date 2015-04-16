<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\HttpAdapter;

final class AuthenticationHttpAdapterFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create an instance of ZF\MvcAuth\Authentication\HttpAdapter based on
     * the configuration provided and the registered AuthenticationService.
     * 
     * @param string $type The base "type" the adapter will provide
     * @param array $config 
     * @param ServiceLocatorInterface $services 
     * @return HttpAdapter
     */
    public static function factory($type, array $config, ServiceLocatorInterface $services)
    {
        if (! $services->has('authentication')) {
            throw new ServiceNotCreatedException(
                'Cannot create HTTP authentication adapter; missing AuthenticationService'
            );
        }

        if (! isset($config['options']) || ! is_array($config['options'])) {
            throw new ServiceNotCreatedException(
                'Cannot create HTTP authentication adapter; missing options'
            );
        }

        return new HttpAdapter(
            HttpAdapterFactory::factory($config['options']),
            $services->get('authentication'),
            $type
        );
    }
}
