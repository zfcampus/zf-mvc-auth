<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Http\Request;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authorization\AclFactory;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    protected $httpMethods = array(
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    );

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
                'Cannot create DefaultAuthorizationListener service; no ZF\MvcAuth\Authorization\AuthorizationInterface service available!'
            );
        }

        $config = array();
        if ($services->has('config')) {
            $config = $services->get('config');
        }

        return new DefaultAuthorizationListener(
            $services->get('ZF\MvcAuth\Authorization\AuthorizationInterface'),
            $this->getRestServicesFromConfig($config)
        );
    }

    /**
     * Generate the list of REST services for the listener
     *
     * Looks for zf-rest configuration, and creates a list of controller
     * service / identifier name pairs to pass to the listener.
     *
     * @param array $config
     * @return array
     */
    protected function getRestServicesFromConfig(array $config)
    {
        $restServices = array();
        if (!isset($config['zf-rest'])) {
            return $restServices;
        }

        foreach ($config['zf-rest'] as $controllerService => $restConfig) {
            if (!isset($restConfig['identifier_name'])) {
                continue;
            }
            $restServices[$controllerService] = $restConfig['identifier_name'];
        }

        return $restServices;
    }
}
