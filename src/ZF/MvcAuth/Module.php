<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Mvc\MvcEvent;

class Module
{
    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Zend\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__,
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $routeListener = new MvcRouteListener($e);

        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authentication'), 500);
        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authenticationPost'), 499);
        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authorization'), -500);

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION, new DefaultAuthenticationListener);
        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $services->get('ZF\MvcAuth\Authentication\UnauthenticatedListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('ZF\MvcAuth\Authorization\DefaultResourceResolverListener'), 1000);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $services->get('ZF\MvcAuth\Authorization\UnauthorizedListener'));
    }
}
