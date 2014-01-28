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
            __NAMESPACE__ => __DIR__ . '/src/ZF/MvcAuth/',
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $app      = $mvcEvent->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $authentication = $services->get('authentication');
        $mvcAuthEvent   = new MvcAuthEvent(
            $mvcEvent,
            $authentication,
            $services->get('authorization')
        );
        $routeListener  = new MvcRouteListener(
            $mvcAuthEvent,
            $events,
            $authentication
        );

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION, $services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('ZF\MvcAuth\Authorization\DefaultResourceResolverListener'), 1000);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener'));
    }
}
