<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DefaultAuthHttpAdapterFactory from configuration.
 */
class DefaultAuthHttpAdapterFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     * @return HttpAuth
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // If no configuration present, nothing to create
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        // If no HTTP adapter configuration present, nothing to create
        if (! isset($config['zf-mvc-auth']['authentication']['http'])) {
            return false;
        }

        return HttpAdapterFactory::factory($config['zf-mvc-auth']['authentication']['http'], $container);
    }

    /**
     * Create and return an HTTP authentication adapter instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return HttpAuth
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, HttpAuth::class);
    }
}
