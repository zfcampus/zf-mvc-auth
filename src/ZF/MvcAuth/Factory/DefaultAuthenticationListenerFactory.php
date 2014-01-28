<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use OAuth2\Server as OAuth2Server;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\AuthorizationCode;

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

        $httpAdapter  = false;
        $oauth2Server = false;
        if ($services->has('config')) {
            $httpAdapter  = $this->createHttpAdapterFromConfig($services);
            $oauth2Server = $this->createOauth2ServerFromConfig($services);
        }

        if ($httpAdapter) {
            $listener->setHttpAdapter($httpAdapter);
        }

        if ($oauth2Server) {
            $listener->setOauth2Server($oauth2Server);
        }

        return $listener;
    }

    /**
     * @param  ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return false|HttpAuth
     */
    protected function createHttpAdapterFromConfig(ServiceLocatorInterface $services)
    {
        // Apps could provide theirs own resolvers while creating its custom AuthHttpAdapter but...
        $httpAdapter = $services->get('ZF\MvcAuth\Authentication\AuthHttpAdapter');
        if ($httpAdapter === false) {
            return false;
        }

        // ZF\MvcAuth configuration can overwrite custom resolvers and...
        $config = $services->get('config');
        if (isset($config['zf-mvc-auth']['authentication']['http']['accept_schemes'])
            && is_array($config['zf-mvc-auth']['authentication']['http']['accept_schemes'])
        ) {
            $httpConfig = $config['zf-mvc-auth']['authentication']['http'];
            if (in_array('basic', $httpConfig['accept_schemes']) && isset($httpConfig['htpasswd'])) {
                $httpAdapter->setBasicResolver(new HttpAuth\ApacheResolver($httpConfig['htpasswd']));
            }
            if (in_array('digest', $httpConfig['accept_schemes']) && isset($httpConfig['htdigest'])) {
                $httpAdapter->setDigestResolver(new HttpAuth\FileResolver($httpConfig['htdigest']));
            }
        }

        // we must abort if no resolver was provided
        if (!$httpAdapter->getBasicResolver()
            && !$httpAdapter->getDigestResolver()
        ) {
            return false;
        }

        return $httpAdapter;
    }

    /**
     * Create an OAuth2 server from configuration
     *
     * @param  ServiceLocatorInterface $services
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @return null|OAuth2Server
     */
    protected function createOauth2ServerFromConfig(ServiceLocatorInterface $services)
    {
        $config = $services->get('config');
        if (!isset($config['zf-oauth2']['storage'])
            || !is_string($config['zf-oauth2']['storage'])
            || !$services->has($config['zf-oauth2']['storage'])
        ) {
            return null;
        }

        $storage = $services->get($config['zf-oauth2']['storage']);

        // Pass a storage object or array of storage objects to the OAuth2 server class
        $oauth2Server = new OAuth2Server($storage);

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $oauth2Server->addGrantType(new ClientCredentials($storage));

        // Add the "Authorization Code" grant type
        $oauth2Server->addGrantType(new AuthorizationCode($storage));

        return $oauth2Server;
    }
}
