<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Http\Request;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF\MvcAuth\Authorization\DefaultResourceResolverListener;

/**
 * Factory for creating the DefaultResourceResolverListener from configuration
 */
class DefaultResourceResolverListenerFactory implements FactoryInterface
{
    protected $httpMethods = [
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    ];

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
        $config = [];
        if ($container->has('config')) {
            $config = $container->get('config');
        }

        return new DefaultResourceResolverListener(
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
        $restServices = [];
        if (!isset($config['zf-rest'])) {
            return $restServices;
        }

        foreach ($config['zf-rest'] as $controllerService => $restConfig) {
            if (!isset($restConfig['route_identifier_name'])) {
                continue;
            }
            $restServices[$controllerService] = $restConfig['route_identifier_name'];
        }

        return $restServices;
    }
}
