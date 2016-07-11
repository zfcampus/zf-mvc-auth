<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = NULL)
    {
        if (!$container->has('ZF\MvcAuth\Authorization\AuthorizationInterface')) {
            throw new ServiceNotCreatedException(
                'Cannot create DefaultAuthorizationListener service; '
                . 'no ZF\MvcAuth\Authorization\AuthorizationInterface service available!'
            );
        }

        return new DefaultAuthorizationListener(
            $container->get('ZF\MvcAuth\Authorization\AuthorizationInterface')
        );
    }

}
