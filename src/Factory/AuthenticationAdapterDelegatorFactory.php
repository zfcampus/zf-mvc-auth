<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace ZF\MvcAuth\Factory;

use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\Exception\ExceptionInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AuthenticationAdapterDelegatorFactory implements DelegatorFactoryInterface
{
    public function createDelegatorWithName(
        ServiceLocatorInterface $services,
        $name,
        $requestedName,
        $callback
    ) {
        $listener = $callback();

        $config = $services->get('Config');
        if (! isset($config['zf-mvc-auth']['authentication']['adapters'])
            || ! is_array($config['zf-mvc-auth']['authentication']['adapters'])
        ) {
            return $listener;
        }

        foreach ($config['zf-mvc-auth']['authentication']['adapters'] as $name => $spec) {
            try {
                $adapter = $services->get('zf-mvc-auth-authentication-adapters-' . $name);
            } catch (ExceptionInterface $e) {
                continue;
            }
            $listener->attach($adapter);
        }

        return $listener;
    }
}
