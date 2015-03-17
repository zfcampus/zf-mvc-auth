<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DefaultAuthHttpAdapterFactory from configuration
 */
class DefaultAuthHttpAdapterFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return false|HttpAuth
     */
    public function createService(ServiceLocatorInterface $services)
    {
        // If no configuration present, nothing to create
        if (!$services->has('config')) {
            return false;
        }

        $config = $services->get('config');

        // If no HTTP adapter configuration present, nothing to create
        if (!isset($config['zf-mvc-auth']['authentication']['http'])) {
            return false;
        }

        return HttpAdapterFactory::factory($config['zf-mvc-auth']['authentication']['http']);
    }
}
