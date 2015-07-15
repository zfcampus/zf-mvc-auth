<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use ZF\MvcAuth\Module;
use ZF\MvcAuth\MvcAuthEvent;

class ModuleTest extends TestCase
{
    public function setUp()
    {
        $this->mvcEvent = $mvcEvent = $this->prophesize('Zend\Mvc\MvcEvent');
        $this->module = new Module();
    }

    public function setUpApplication()
    {
        $services = $this->setUpServices();
        $events   = $this->setUpEvents();

        $application = $this->prophesize('Zend\Mvc\Application');
        $application->getEventManager()->will([$events, 'reveal']);
        $application->getServiceManager()->will([$services, 'reveal']);

        return $application;
    }

    public function setUpServices()
    {
        $authentication = $this->prophesize('Zend\Authentication\AuthenticationService');
        $authorization  = $this->prophesize('ZF\MvcAuth\Authorization\AuthorizationInterface');
        $defaultAuthenticationListener = $this->prophesize(
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener'
        );
        $defaultAuthenticationPostListener = $this->prophesize(
            'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener'
        );
        $defaultResourceResolverListener = $this->prophesize(
            'ZF\MvcAuth\Authorization\DefaultResourceResolverListener'
        );
        $defaultAuthorizationListener = $this->prophesize(
            'ZF\MvcAuth\Authorization\DefaultAuthorizationListener'
        );
        $defaultAuthorizationPostListener = $this->prophesize(
            'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener'
        );

        $services = $this->prophesize('Zend\ServiceManager\ServiceLocatorInterface');
        $services->get('authentication')->will([$authentication, 'reveal']);
        $services->get('authorization')->will([$authorization, 'reveal']);
        $services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationListener')
            ->will([$defaultAuthenticationListener, 'reveal']);
        $services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener')
            ->will([$defaultAuthenticationPostListener, 'reveal']);
        $services->get('ZF\MvcAuth\Authorization\DefaultResourceResolverListener')
            ->will([$defaultResourceResolverListener, 'reveal']);
        $services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationListener')
            ->will([$defaultAuthorizationListener, 'reveal']);
        $services->get('ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener')
            ->will([$defaultAuthorizationPostListener, 'reveal']);

        return $services;
    }

    public function setUpEvents()
    {
        $events = $this->prophesize('Zend\EventManager\EventManagerInterface');

        $events->attach(Argument::type('ZF\MvcAuth\MvcRouteListener'));

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION,
            Argument::type('ZF\MvcAuth\Authentication\DefaultAuthenticationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            Argument::type('ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            Argument::type('ZF\MvcAuth\Authorization\DefaultResourceResolverListener'),
            1000
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            Argument::type('ZF\MvcAuth\Authorization\DefaultAuthorizationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            Argument::type('ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            Argument::is([$this->module, 'onAuthenticationPost']),
            -1
        );

        return $events;
    }

    public function testOnBootstrapReturnsEarlyForNonHttpEvents()
    {
        $request = $this->prophesize('Zend\Stdlib\RequestInterface');
        $this->mvcEvent->getRequest()->will([$request, 'reveal']);
        $this->module->onBootstrap($this->mvcEvent->reveal());
    }

    public function testOnBootstrapAttachesListeners()
    {
        $mvcEvent    = $this->mvcEvent;
        $request     = $this->prophesize('Zend\Http\Request');
        $application = $this->setUpApplication();
        $mvcEvent->getRequest()->will([$request, 'reveal']);
        $mvcEvent->getApplication()->will([$application, 'reveal']);
        $this->module->onBootstrap($mvcEvent->reveal());
    }
}
