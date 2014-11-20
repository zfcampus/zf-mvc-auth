<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Authentication\Adapter\Http\ApacheResolver;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApacheResolverFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ApacheResolver|false
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator->has('config')) {
            return false;
        }

        $config = $serviceLocator->get('config');

        if (!isset($config['zf-mvc-auth']['authentication']['http']['htpasswd'])) {
            return false;
        }

        $htpasswd = $config['zf-mvc-auth']['authentication']['http']['htpasswd'];

        return new ApacheResolver($htpasswd);
    }
}
