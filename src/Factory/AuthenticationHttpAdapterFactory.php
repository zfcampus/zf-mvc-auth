<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
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
     * Create an instance of HttpAdapter based on the configuration provided
     * and the registered AuthenticationService.
     *
     * @param string $type The base "type" the adapter will provide
     * @param array $config
     * @param ContainerInterface $container
     * @return HttpAdapter
     */
    public static function factory($type, array $config, ContainerInterface $container)
    {
        if (! $container->has('authentication')) {
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
            HttpAdapterFactory::factory($config['options'], $container),
            $container->get('authentication'),
            $type
        );
    }
}
