<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApacheResolverFactory implements FactoryInterface
{
    /**
     * Create and return an ApacheResolver instance.
     *
     * If appropriate configuration is not found, returns boolean false.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return false|ApacheResolver
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (false === $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['zf-mvc-auth']['authentication']['http']['htpasswd'])) {
            return false;
        }

        $htpasswd = $config['zf-mvc-auth']['authentication']['http']['htpasswd'];

        return new ApacheResolver($htpasswd);
    }

    /**
     * Create and return an ApacheResolve instance (v2).
     *
     * Exists for backwards compatibility only; proxies to __invoke().
     *
     * @param  ServiceLocatorInterface $container
     * @return false|ApacheResolver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ApacheResolver::class);
    }
}
