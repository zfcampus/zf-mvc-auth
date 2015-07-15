<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use RuntimeException;
use ZF\OAuth2\Factory\OAuth2ServerInstanceFactory;

/**
 * Override factory for the ZF\OAuth2\Service\OAuth2Server service.
 *
 * This factory returns a factory that will allow retrieving a named
 * OAuth2\Server instance. It delegates to
 * ZF\OAuth2\Factory\OAuth2ServerInstanceFactory after first marshaling the
 * correct configuration from zf-mvc-auth.authentication.adapters.
 */
class NamedOAuth2ServerFactory
{
    public function __invoke($services)
    {
        $config = $services->get('Config');

        $oauth2Config  = isset($config['zf-oauth2']) ? $config['zf-oauth2'] : [];
        $mvcAuthConfig = isset($config['zf-mvc-auth']['authentication']['adapters'])
            ? $config['zf-mvc-auth']['authentication']['adapters']
            : [];

        $servers = (object) ['application' => null, 'api' => []];
        return function ($type = null) use ($oauth2Config, $mvcAuthConfig, $services, $servers) {
            // Empty type == legacy configuration.
            if (empty($type)) {
                if ($servers->application) {
                    return $servers->application;
                }
                $factory = new OAuth2ServerInstanceFactory($oauth2Config, $services);
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
                    $services
                );
            }

            // At this point, a $type was specified, but no matching adapter
            // was found. Attempt to pull a global OAuth2 instance; if none is
            // present, this will raise an exception anyways.
            if ($servers->application) {
                return $servers->application;
            }
            $factory = new OAuth2ServerInstanceFactory($oauth2Config, $services);
            return $servers->application = $factory();
        };
    }
}
