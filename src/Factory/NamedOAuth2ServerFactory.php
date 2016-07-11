<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use RuntimeException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF\OAuth2\Factory\OAuth2ServerInstanceFactory;

/**
 * Override factory for the ZF\OAuth2\Service\OAuth2Server service.
 *
 * This factory returns a factory that will allow retrieving a named
 * OAuth2\Server instance. It delegates to
 * ZF\OAuth2\Factory\OAuth2ServerInstanceFactory after first marshaling the
 * correct configuration from zf-mvc-auth.authentication.adapters.
 */
class NamedOAuth2ServerFactory implements FactoryInterface
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
        $config = $container->get('Config');

        $oauth2Config  = isset($config['zf-oauth2']) ? $config['zf-oauth2'] : [];
        $mvcAuthConfig = isset($config['zf-mvc-auth']['authentication']['adapters'])
            ? $config['zf-mvc-auth']['authentication']['adapters']
            : [];

        $servers = (object) ['application' => null, 'api' => []];
        return function ($type = null) use ($oauth2Config, $mvcAuthConfig, $container, $servers) {
            // Empty type == legacy configuration.
            if (empty($type)) {
                if ($servers->application) {
                    return $servers->application;
                }
                $factory = new OAuth2ServerInstanceFactory($oauth2Config, $container);
                return $servers->application = $factory();
            }

            if (isset($servers->api[$type])) {
                return $servers->api[$type];
            }

            foreach ($mvcAuthConfig as $name => $adapterConfig) {
                if (! isset($adapterConfig['storage']['route'])) {
                    // Not a zf-oauth2 config
                    continue;
                }

                if ($type !== $adapterConfig['storage']['route']) {
                    continue;
                }

                // Found!
                return $servers->api[$type] = OAuth2ServerFactory::factory(
                    $adapterConfig['storage'],
                    $container
                );
            }

            // At this point, a $type was specified, but no matching adapter
            // was found. Attempt to pull a global OAuth2 instance; if none is
            // present, this will raise an exception anyways.
            if ($servers->application) {
                return $servers->application;
            }
            $factory = new OAuth2ServerInstanceFactory($oauth2Config, $container);
            return $servers->application = $factory();
        };
    }


}
