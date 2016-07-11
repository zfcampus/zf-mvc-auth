<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\Authentication\Adapter\Http\FileResolver;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileResolverFactory implements FactoryInterface
{
    /**
     * Create and return a FileResolver instance, if configured.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     * @return false|FileResolver
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['zf-mvc-auth']['authentication']['http']['htdigest'])) {
            return false;
        }

        $htdigest = $config['zf-mvc-auth']['authentication']['http']['htdigest'];

        return new FileResolver($htdigest);
    }

    /**
     * Create and return a FileResolver instance, if configured (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return false|FileResolver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, FileResolver::class);
    }
}
