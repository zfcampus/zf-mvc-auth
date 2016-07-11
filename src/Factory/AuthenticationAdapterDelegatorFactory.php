<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Authentication\HttpAdapter;
use ZF\MvcAuth\Authentication\OAuth2Adapter;

class AuthenticationAdapterDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * Decorate the DefaultAuthenticationListener.
     *
     * Attaches adapters as listeners if present in configuration.
     *
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  callable           $callback
     * @param  null|array         $options
     * @return DefaultAuthenticationListener
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $listener = $callback();

        $config = $container->get('config');
        if (! isset($config['zf-mvc-auth']['authentication']['adapters'])
            || ! is_array($config['zf-mvc-auth']['authentication']['adapters'])
        ) {
            return $listener;
        }

        foreach ($config['zf-mvc-auth']['authentication']['adapters'] as $type => $data) {
            $this->attachAdapterOfType($type, $data, $container, $listener);
        }

        return $listener;
    }

    /**
     * Decorate the DefaultAuthenticationListener (v2)
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @param callable $callback
     * @return DefaultAuthenticationListener
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }

    /**
     * Attach an adaper to the listener as described by $type and $data.
     *
     * @param string $type
     * @param array $adapterConfig
     * @param ContainerInterface $container
     * @param DefaultAuthenticationListener $listener
     */
    private function attachAdapterOfType(
        $type,
        array $adapterConfig,
        ContainerInterface $container,
        DefaultAuthenticationListener $listener
    ) {
        if (! isset($adapterConfig['adapter'])
            || ! is_string($adapterConfig['adapter'])
        ) {
            return;
        }

        switch ($adapterConfig['adapter']) {
            case HttpAdapter::class:
                $adapter = AuthenticationHttpAdapterFactory::factory($type, $adapterConfig, $container);
                break;
            case OAuth2Adapter::class:
                $adapter = AuthenticationOAuth2AdapterFactory::factory($type, $adapterConfig, $container);
                break;
            default:
                $adapter = false;
                break;
        }

        if (! $adapter) {
            return;
        }

        $listener->attach($adapter);
    }
}
