<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Mvc\MvcEvent;
use Zend\Authentication\AuthenticationService;

/**
 * ZF2 module
 */
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
        $app = $e->getApplication();
        $em = $app->getEventManager();

        $routeListener = new RouteListener($e);

        $em->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authentication'), 500);
        $em->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authenticationPost'), 499);
        $em->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authorization'), -500);

        $em->attach(MvcAuthEvent::EVENT_AUTHENTICATION, new Authentication\DefaultAuthenticationListener, 1);
        $em->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, new Authentication\UnauthorizedListener, 1);
        $em->attach(MvcAuthEvent::EVENT_AUTHENTICATION, new Authorization\RbacAuthorizationListener, 1);
    }

}
