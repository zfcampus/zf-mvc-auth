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

    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $app      = $mvcEvent->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, $services->get('authentication'), null); //$services->get('authorization'));

        $configuration = $services->get('Config');

        $routeListener = new MvcRouteListener($mvcAuthEvent, $configuration);

        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authentication'), 500);
        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authenticationPost'), 499);
        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authorization'), -600);
        $events->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authorizationPost'), -601);

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION, new DefaultAuthenticationListener);
        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, new DefaultAuthenticationPostListener);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('ZF\MvcAuth\DefaultResourceResolverListener'), 1000);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('ZF\MvcAuth\DefaultAuthorizationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, new DefaultAuthorizationPostListener);
    }
}
