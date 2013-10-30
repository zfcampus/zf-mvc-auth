<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Http\Request;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;

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

        $httpAdapter = false;
        if ($services->has('config')) {
            $httpAdapter = $this->createHttpAdapterFromConfig($services->get('config'));
        }

        if ($httpAdapter) {
            $listener->setHttpAdapter($httpAdapter);
        }

        return $listener;
    }

    /**
     * @param array $config
     * @return false|HttpAuth
     */
    protected function createHttpAdapterFromConfig(array $config)
    {
        if (!isset($config['zf-mvc-auth']['authentication'])) {
            return false;
        }
        $authConfig = $config['zf-mvc-auth']['authentication'];

        if (!isset($authConfig['http'])) {
            return false;
        }

        $httpConfig = $authConfig['http'];

        if (!isset($httpConfig['accept_schemes']) || !is_array($httpConfig['accept_schemes'])) {
            throw new ServiceNotCreatedException('accept_schemes is required when configuring an HTTP authentication adapter');
        }

        if (!isset($httpConfig['realm'])) {
            throw new ServiceNotCreatedException('realm is required when configuring an HTTP authentication adapter');
        }

        if (in_array('digest', $httpConfig['accept_schemes'])) {
            if (!isset($httpConfig['digest_domains'])
                || !isset($httpConfig['nonce_timeout'])
            ) {
                throw new ServiceNotCreatedException('Both digest_domains and nonce_timeout are required when configuring an HTTP digest authentication adapter');
            }
        }

        $httpAdapter = new HttpAuth(array_merge($httpConfig, array('accept_schemes' => implode(' ', $httpConfig['accept_schemes']))));

        $hasFileResolver = false;

        // basic && htpasswd
        if (in_array('basic', $httpConfig['accept_schemes']) && isset($httpConfig['htpasswd'])) {
            $httpAdapter->setBasicResolver(new HttpAuth\ApacheResolver($httpConfig['htpasswd']));
            $hasFileResolver = true;
        }
        if (in_array('digest', $httpConfig['accept_schemes']) && isset($httpConfig['htdigest'])) {
            $httpAdapter->setDigestResolver(new HttpAuth\FileResolver($httpConfig['htdigest']));
            $hasFileResolver = true;
        }

        if ($hasFileResolver === false) {
            return false;
        }

        return $httpAdapter;
    }
}
