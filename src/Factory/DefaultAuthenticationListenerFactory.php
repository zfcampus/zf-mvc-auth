<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use RuntimeException;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Authentication\HttpAdapter;
use ZF\MvcAuth\Authentication\OAuth2Adapter;
use ZF\OAuth2\Factory\OAuth2ServerFactory as ZFOAuth2ServerFactory;

/**
 * Factory for creating the DefaultAuthenticationListener from configuration
 */
class DefaultAuthenticationListenerFactory implements FactoryInterface
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
        $listener = new DefaultAuthenticationListener();

        $httpAdapter = $this->retrieveHttpAdapter($container);
        if ($httpAdapter) {
            $listener->attach($httpAdapter);
        }

        $oauth2Server = $this->createOAuth2Server($container);
        if ($oauth2Server) {
            $listener->attach($oauth2Server);
        }

        $authenticationTypes = $this->getAuthenticationTypes($container);
        if ($authenticationTypes) {
            $listener->addAuthenticationTypes($authenticationTypes);
        }

        $listener->setAuthMap($this->getAuthenticationMap($container));

        return $listener;
    }


    /**
     * @param \Interop\Container\ContainerInterface $services
     *
     * @return false|\ZF\MvcAuth\Authentication\HttpAdapter
     */
    protected function retrieveHttpAdapter(ContainerInterface $services)
    {
        // Allow applications to provide their own AuthHttpAdapter service; if none provided,
        // or no HTTP adapter configuration provided to zf-mvc-auth, we can stop early.

        $httpAdapter = $services->get('ZF\MvcAuth\Authentication\AuthHttpAdapter');
        
        if ($httpAdapter === FALSE) {
            return FALSE;
        }

        // We must abort if no resolver was provided
        if (!$httpAdapter->getBasicResolver()
            && !$httpAdapter->getDigestResolver()
        ) {
            return FALSE;
        }

        $authService = $services->get('authentication');

        return new HttpAdapter($httpAdapter, $authService);
    }

    /**
     * Create an OAuth2 server by introspecting the config service
     *
     * @param \Interop\Container\ContainerInterface $services
     *
     * @return false|\ZF\MvcAuth\Authentication\OAuth2Adapter
     */
    protected function createOAuth2Server(ContainerInterface $services)
    {
        if (!$services->has('Config')) {
            // If we don't have configuration, we cannot create an OAuth2 server.
            return FALSE;
        }

        $config = $services->get('config');
        if (!isset($config['zf-oauth2']['storage'])
            || !is_string($config['zf-oauth2']['storage'])
            || !$services->has($config['zf-oauth2']['storage'])
        ) {
            return FALSE;
        }

        if ($services->has('ZF\OAuth2\Service\OAuth2Server')) {
            // If the service locator already has a pre-configured OAuth2 server, use it.
            $factory = $services->get('ZF\OAuth2\Service\OAuth2Server');

            return new OAuth2Adapter($factory());
        }

        $factory = new ZFOAuth2ServerFactory();

        try {
            $serverFactory = $factory($services);
        } catch (RuntimeException $e) {
            // These are exceptions specifically thrown from the
            // ZF\OAuth2\Factory\OAuth2ServerFactory when essential
            // configuration is missing.
            switch (TRUE) {
                case strpos($e->getMessage(), 'missing'):
                    return FALSE;
                case strpos($e->getMessage(), 'string or array'):
                    return FALSE;
                default:
                    // Any other RuntimeException at this point we don't know
                    // about and need to re-throw.
                    throw $e;
            }
        }

        return new OAuth2Adapter($serverFactory(NULL));
    }

    /**
     * Retrieve custom authentication types
     *
     * @param \Interop\Container\ContainerInterface $services
     *
     * @return array|false
     */
    protected function getAuthenticationTypes(ContainerInterface $services)
    {
        if (!$services->has('config')) {
            return FALSE;
        }

        $config = $services->get('config');
        if (!isset($config['zf-mvc-auth']['authentication']['types'])
            || !is_array($config['zf-mvc-auth']['authentication']['types'])
        ) {
            return FALSE;
        }

        return $config['zf-mvc-auth']['authentication']['types'];
    }

    /**
     * @param \Interop\Container\ContainerInterface $services
     *
     * @return array
     */
    protected function getAuthenticationMap(ContainerInterface $services)
    {
        if (!$services->has('config')) {
            return [];
        }

        $config = $services->get('config');
        if (!isset($config['zf-mvc-auth']['authentication']['map'])
            || !is_array($config['zf-mvc-auth']['authentication']['map'])
        ) {
            return [];
        }

        return $config['zf-mvc-auth']['authentication']['map'];
    }
}
