<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create and return an AuthenticationService instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return AuthenticationService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new AuthenticationService($container->get(NonPersistent::class));
    }

    /**
     * Create and return an AuthenticationService instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return AuthenticationService
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AuthenticationService::class);
    }
}
