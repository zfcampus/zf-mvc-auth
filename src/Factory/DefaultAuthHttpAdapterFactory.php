<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for creating the DefaultAuthHttpAdapterFactory from configuration
 */
class DefaultAuthHttpAdapterFactory implements FactoryInterface
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
        // If no configuration present, nothing to create
        if (!$container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        // If no HTTP adapter configuration present, nothing to create
        if (!isset($config['zf-mvc-auth']['authentication']['http'])) {
            return false;
        }

        return HttpAdapterFactory::factory($config['zf-mvc-auth']['authentication']['http'], $container);
    }

}
