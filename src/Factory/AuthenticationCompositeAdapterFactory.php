<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\CompositeAdapter;

final class AuthenticationCompositeAdapterFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create and return an CompositeAdapter instance.
     *
     * @param string|array $type
     * @param array $config
     * @param ServiceLocatorInterface $services
     * @return CompositeAdapter
     * @throws ServiceNotCreatedException when missing details necessary to
     *     create instance and/or dependencies.
     */
    public static function factory($type, array $config, ServiceLocatorInterface $services)
    {
        if (! isset($config['adapters']) || ! is_array($config['adapters'])) {
            throw new ServiceNotCreatedException('No adapters configured for CompositeAdapters');
        }

        $adapters = array();

        foreach ($config['adapters'] as $name) {
            $adapters[] = $services->get('zf-mvc-auth-authentication-adapters-' . $name);
        }

        return new CompositeAdapter($adapters, $type);
    }
}
