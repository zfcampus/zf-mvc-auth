<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\Adapter\Http\ApacheResolver;
use Zend\Authentication\Adapter\Http\FileResolver;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

/**
 * Create and return a Zend\Authentication\Adapter\Http instance based on the
 * configuration provided.
 */
final class HttpAdapterFactory
{
    /**
     * Only defined in order to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Create an HttpAuth instance based on the configuration passed.
     *
     * @param array $config
     * @param null|ContainerInterface $container
     * @return HttpAuth
     */
    public static function factory(array $config, ContainerInterface $container = null)
    {
        if (! isset($config['accept_schemes']) || ! is_array($config['accept_schemes'])) {
            throw new ServiceNotCreatedException(
                '"accept_schemes" is required when configuring an HTTP authentication adapter'
            );
        }

        if (! isset($config['realm'])) {
            throw new ServiceNotCreatedException(
                '"realm" is required when configuring an HTTP authentication adapter'
            );
        }

        if (in_array('digest', $config['accept_schemes'])) {
            if (! isset($config['digest_domains'])
                || ! isset($config['nonce_timeout'])
            ) {
                throw new ServiceNotCreatedException(
                    'Both "digest_domains" and "nonce_timeout" are required '
                    . 'when configuring an HTTP digest authentication adapter'
                );
            }
        }

        $httpAdapter = new HttpAuth(array_merge(
            $config,
            [
                'accept_schemes' => implode(' ', $config['accept_schemes'])
            ]
        ));

        if (in_array('basic', $config['accept_schemes'])) {
            if (isset($config['basic_resolver_factory'])
                && self::containerHasKey($container, $config['basic_resolver_factory'])
            ) {
                $httpAdapter->setBasicResolver($container->get($config['basic_resolver_factory']));
            } elseif (isset($config['htpasswd'])) {
                $httpAdapter->setBasicResolver(new ApacheResolver($config['htpasswd']));
            }
        }

        if (in_array('digest', $config['accept_schemes'])) {
            if (isset($config['digest_resolver_factory'])
                && self::containerHasKey($container, $config['digest_resolver_factory'])
            ) {
                $httpAdapter->setDigestResolver($container->get($config['digest_resolver_factory']));
            } elseif (isset($config['htdigest'])) {
                $httpAdapter->setDigestResolver(new FileResolver($config['htdigest']));
            }
        }

        return $httpAdapter;
    }

    /**
     * @param ContainerInterface $container
     * @param null $key
     * @return bool
     */
    private static function containerHasKey(ContainerInterface $container = null, $key = null)
    {
        if (! $container instanceof ContainerInterface) {
            return false;
        }
        if (! is_string($key)) {
            return false;
        }
        return $container->has($key);
    }
}
