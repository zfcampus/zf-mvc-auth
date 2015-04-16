<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use OAuth2\Server as OAuth2Server;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\AuthorizationCode;
use RuntimeException;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
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
     * @param ServiceLocatorInterface $services
     * @return DefaultAuthenticationListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $listener = new DefaultAuthenticationListener();

        $httpAdapter = $this->retrieveHttpAdapter($services);
        if ($httpAdapter) {
            $listener->attach($httpAdapter);
        }

        $oauth2Server = $this->createOAuth2Server($services);
        if ($oauth2Server) {
            $listener->attach($oauth2Server);
        }

        $authenticationTypes = $this->getAuthenticationTypes($services);
        if ($authenticationTypes) {
            $listener->addAuthenticationTypes($authenticationTypes);
        }

        $listener->setAuthMap($this->getAuthenticationMap($services));

        return $listener;
    }

    /**
     * @param  ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return false|HttpAdapter
     */
    protected function retrieveHttpAdapter(ServiceLocatorInterface $services)
    {
        // Allow applications to provide their own AuthHttpAdapter service; if none provided,
        // or no HTTP adapter configuration provided to zf-mvc-auth, we can stop early.
        $httpAdapter = $services->get('ZF\MvcAuth\Authentication\AuthHttpAdapter');
        if ($httpAdapter === false) {
            return false;
        }

        // We must abort if no resolver was provided
        if (! $httpAdapter->getBasicResolver()
            && ! $httpAdapter->getDigestResolver()
        ) {
            return false;
        }

        $authService = $services->get('authentication');
        return new HttpAdapter($httpAdapter, $authService);
    }

    /**
     * Create an OAuth2 server by introspecting the config service
     *
     * @param  ServiceLocatorInterface $services
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @return false|OAuth2Adapter
     */
    protected function createOAuth2Server(ServiceLocatorInterface $services)
    {
        if (! $services->has('Config')) {
            // If we don't have configuration, we cannot create an OAuth2 server.
            return false;
        }

        $config = $services->get('config');
        if (!isset($config['zf-oauth2']['storage'])
            || !is_string($config['zf-oauth2']['storage'])
            || !$services->has($config['zf-oauth2']['storage'])) {
              return false;
        }

        if ($services->has('ZF\OAuth2\Service\OAuth2Server')) {
            // If the service locator already has a pre-configured OAuth2 server, use it.
            return new OAuth2Adapter($services->get('ZF\OAuth2\Service\OAuth2Server'));
        }

        $factory = new ZFOAuth2ServerFactory();

        try {
            $server = $factory->createService($services);
        } catch (RuntimeException $e) {
            // These are exceptions specifically thrown from the
            // ZF\OAuth2\Factory\OAuth2ServerFactory when essential
            // configuration is missing.
            switch (true) {
                case strpos($e->getMessage(), 'missing'):
                    return false;
                case strpos($e->getMessage(), 'string or array'):
                    return false;
                default:
                    // Any other RuntimeException at this point we don't know
                    // about and need to re-throw.
                    throw $e;
            }
        }

        return new OAuth2Adapter($server);
    }

    /**
     * Retrieve custom authentication types
     *
     * @param ServiceLocatorInterface $services
     * @return false|array
     */
    protected function getAuthenticationTypes(ServiceLocatorInterface $services)
    {
        if (! $services->has('config')) {
            return false;
        }

        $config = $services->get('config');
        if (! isset($config['zf-mvc-auth']['authentication']['types'])
            || ! is_array($config['zf-mvc-auth']['authentication']['types'])
        ) {
            return false;
        }

        return $config['zf-mvc-auth']['authentication']['types'];
    }

    protected function getAuthenticationMap(ServiceLocatorInterface $services)
    {
        if (! $services->has('config')) {
            return array();
        }

        $config = $services->get('config');
        if (! isset($config['zf-mvc-auth']['authentication']['map'])
            || ! is_array($config['zf-mvc-auth']['authentication']['map'])
        ) {
            return array();
        }

        return $config['zf-mvc-auth']['authentication']['map'];
    }
}
