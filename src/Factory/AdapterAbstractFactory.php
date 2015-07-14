<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\AdapterInterface;

class AdapterAbstractFactory implements AbstractFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $serviceLocator->get('config');

        $hasMatches = preg_match('/^zf-mvc-auth-authentication-adapters-(?P<name>\w+)$/', $requestedName, $matches);

        if (! $hasMatches) {
            return false;
        }

        $adapterName = $matches['name'];

        if (! isset($config['zf-mvc-auth']['authentication']['adapters'][$adapterName]['adapter'])) {
            return false;
        }

        $adapter = $config['zf-mvc-auth']['authentication']['adapters'][$adapterName]['adapter'];

        if (! is_string($adapter)) {
            return false;
        }

        if ($serviceLocator->has($adapter) && ! $serviceLocator->get($adapter) instanceof AdapterInterface) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $adapterName = substr($requestedName, strrpos($requestedName, '-') + 1);
        $config      = $serviceLocator->get('config');
        $adapterSpec = $config['zf-mvc-auth']['authentication']['adapters'][$adapterName];

        if (! isset($adapterSpec['adapter']) || ! is_string($adapterSpec['adapter'])) {
            return false;
        }

        switch ($adapterSpec['adapter']) {
            case 'ZF\MvcAuth\Authentication\HttpAdapter':
                $adapter = AuthenticationHttpAdapterFactory::factory($adapterName, $adapterSpec, $serviceLocator);
                break;
            case 'ZF\MvcAuth\Authentication\OAuth2Adapter':
                $adapter = AuthenticationOAuth2AdapterFactory::factory($adapterName, $adapterSpec, $serviceLocator);
                break;
            case 'ZF\MvcAuth\Authentication\CompositeAdapter':
                $adapter = AuthenticationCompositeAdapterFactory::factory($adapterName, $adapterSpec, $serviceLocator);
                break;
            default:
                $adapter = $serviceLocator->get($adapterSpec['adapter']);
                break;
        }

        return $adapter;
    }
}
