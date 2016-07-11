<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;

class AuthenticationAdapterDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * A factory that creates delegates of a given service
     *
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  callable           $callback
     * @param  null|array         $options
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = NULL)
    {
        /** @var DefaultAuthenticationListener $listener */
        $listener = $callback();

        $config = $container->get('Config');
        if (! isset($config['zf-mvc-auth']['authentication']['adapters'])
            || ! is_array($config['zf-mvc-auth']['authentication']['adapters'])
        ) {
            return $listener;
        }

        foreach ($config['zf-mvc-auth']['authentication']['adapters'] as $type => $data) {
            if (! isset($data['adapter']) || ! is_string($data['adapter'])) {
                continue;
            }

            switch ($data['adapter']) {
                case 'ZF\MvcAuth\Authentication\HttpAdapter':
                    $adapter = AuthenticationHttpAdapterFactory::factory($type, $data, $container);
                    break;
                case 'ZF\MvcAuth\Authentication\OAuth2Adapter':
                    $adapter = AuthenticationOAuth2AdapterFactory::factory($type, $data, $container);
                    break;
                default:
                    $adapter = false;
                    break;
            }

            if ($adapter) {
                $listener->attach($adapter);
            }
        }

        return $listener;
    }
}
