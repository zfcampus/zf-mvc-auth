<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create the DefaultAuthorizationListener
     *
     * @param ServiceLocatorInterface $services
     * @return DefaultAuthorizationListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        if (!$services->has('ZF\MvcAuth\Authorization\AuthorizationInterface')) {
            throw new ServiceNotCreatedException(
                'Cannot create DefaultAuthorizationListener service; '
                . 'no ZF\MvcAuth\Authorization\AuthorizationInterface service available!'
            );
        }

        return new DefaultAuthorizationListener(
            $services->get('ZF\MvcAuth\Authorization\AuthorizationInterface')
        );
    }
}
