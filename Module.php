<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Mvc\MvcEvent;

class Module
{
    protected $services;

    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Zend\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/',
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
        $events   = $app->getEventManager();
        $this->services = $app->getServiceManager();

        $authentication = $this->services->get('authentication');
        $mvcAuthEvent   = new MvcAuthEvent(
            $mvcEvent,
            $authentication,
            $this->services->get('authorization')
        );
        $routeListener  = new MvcRouteListener(
            $mvcAuthEvent,
            $events,
            $authentication
        );

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION,
            $this->services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $this->services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            $this->services->get('ZF\MvcAuth\Authorization\DefaultResourceResolverListener'),
            1000
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            $this->services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $this->services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener')
        );

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            array($this, 'onAuthenticationPost'),
            -1
        );
    }

    public function onAuthenticationPost(MvcAuthEvent $e)
    {
        if ($this->services->has('api-identity')) {
            return;
        }

        $this->services->setService('api-identity', $e->getIdentity());
    }
}
