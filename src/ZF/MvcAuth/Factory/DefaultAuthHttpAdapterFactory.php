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

/**
 * Factory for creating the DefaultAuthHttpAdapterFactory from configuration
 */
class DefaultAuthHttpAdapterFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return HttpAuthAdapter
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = $services->get('config');
        if (isset($config['zf-mvc-auth']['authentication']['http'])) {
            $httpConfig = $config['zf-mvc-auth']['authentication']['http'];
        }
        else {
            return false;
        }

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

        return new HttpAuth(array_merge($httpConfig, array('accept_schemes' => implode(' ', $httpConfig['accept_schemes']))));
    }
}
