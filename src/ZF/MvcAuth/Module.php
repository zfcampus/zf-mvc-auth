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

        /** @var AuthenticationService $auth */
        // $auth = $app->getServiceManager()->get('mvc-auth-authentication');

        $routeListener = new RouteListener($e);

        $em->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authenticationPreRoute'), 1000);
        $em->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authenticationPostRoute'), -999);
        $em->attach(MvcEvent::EVENT_ROUTE, array($routeListener, 'authorization'), -1000);

    }

}
