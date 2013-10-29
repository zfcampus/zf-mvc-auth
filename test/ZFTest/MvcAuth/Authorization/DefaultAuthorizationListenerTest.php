<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authorization;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Request;
use Zend\Stdlib\Response;
use ZF\MvcAuth\Authorization\AclAuthorization;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset;

class DefaultAuthorizationListenerTest extends TestCase
{
    public function setUp()
    {
        $routeMatch = new RouteMatch(array());
        $request    = new HttpRequest();
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response)
            ->setRouteMatch($routeMatch);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->acl = new AclAuthorization();
        $this->acl->addRole('guest');
        $this->acl->allow();
        $this->listener = new DefaultAuthorizationListener($this->acl);
    }

    public function createMvcAuthEvent(MvcEvent $mvcEvent)
    {
        $this->authentication = new TestAsset\AuthenticationService();

        $servicesMap = array(
            array('authentication', $this->authentication),
            array('authorization', (object) array()),
        );
        $services = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $services->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($servicesMap));
        $services->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap(array(array('authorization', true))));

        $application = $this->getMockBuilder('Zend\Mvc\ApplicationInterface')
            ->getMock();
        $application->expects($this->any())
            ->method('getServiceManager')
            ->will($this->returnValue($services));

        $mvcEvent->setApplication($application);
        return new MvcAuthEvent($mvcEvent);
    }

    public function testBailsEarlyOnInvalidRequest()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->setRequest(new Request());
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testBailsEarlyOnInvalidResponse()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->setResponse(new Response());
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testBailsEarlyOnMissingRouteMatch()
    {
        $listener = $this->listener;

        $request    = new HttpRequest();
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response);
        $mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->assertNull($listener($mvcAuthEvent));
    }

    public function testBailsEarlyOnMissingIdentity()
    {
        $listener = $this->listener;
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testBailsEarlyIfMvcAuthEventIsAuthorizedAlready()
    {
        $listener = $this->listener;
        // Setting identity to ensure we don't get a false positive
        $this->mvcAuthEvent->setIdentity(new GuestIdentity());
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsTrueIfIdentityPassesAcls()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->getRouteMatch()->setParam('controller', 'Foo\Bar\Controller');
        $this->mvcAuthEvent->setIdentity(new GuestIdentity());
        $this->mvcAuthEvent->setResource('Foo\Bar\Controller');
        $this->assertTrue($listener($this->mvcAuthEvent));
    }

    public function testReturnsFalseIfIdentityFailsAcls()
    {
        $listener = $this->listener;
        $this->acl->addResource('Foo\Bar\Controller::index');
        $this->acl->deny('guest', 'Foo\Bar\Controller::index', 'POST');
        $this->mvcAuthEvent->setResource('Foo\Bar\Controller::index');
        $this->mvcAuthEvent->getMvcEvent()->getRequest()->setMethod('POST');
        $this->authentication->setIdentity(new GuestIdentity());
        $this->assertFalse($listener($this->mvcAuthEvent));
    }
}
