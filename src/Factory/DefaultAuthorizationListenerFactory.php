<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authorization\AuthorizationInterface;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration.
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create and return the default authorization listener.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     * @return DefaultAuthorizationListenerFactory
     * @throws ServiceNotCreatedException if the AuthorizationInterface service is missing.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (! $container->has(AuthorizationInterface::class)) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service; no %s service available!',
                DefaultAuthorizationListener::class,
                AuthorizationInterface::class
            ));
        }

        return new DefaultAuthorizationListener($container->get(AuthorizationInterface::class));
    }

    /**
     * Create and return the default authorization listener (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DefaultAuthorizationListenerFactory
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DefaultAuthorizationListener::class);
    }
}
