<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Authentication\Adapter\Http\FileResolver;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileResolverFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return FileResolver|false
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator->has('config')) {
            return false;
        }

        $config = $serviceLocator->get('config');

        if (!isset($config['zf-mvc-auth']['authentication']['http']['htdigest'])) {
            return false;
        }

        $htdigest = $config['zf-mvc-auth']['authentication']['http']['htdigest'];

        return new FileResolver($htdigest);
    }
}
