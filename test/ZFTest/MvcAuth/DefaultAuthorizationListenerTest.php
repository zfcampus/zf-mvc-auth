<?php

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Permissions\Acl\Acl;
use Zend\Stdlib\Request;
use Zend\Stdlib\Response;
use ZF\MvcAuth\AclFactory;
use ZF\MvcAuth\DefaultAuthorizationListener;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;

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

        $this->acl = new Acl();
        $this->acl->addRole('guest');
        $this->acl->allow();
        $this->restControllers = array(
            'ZendCon\V1\Rest\Session\Controller' => 'session_id',
        );
        $this->listener = new DefaultAuthorizationListener($this->acl, $this->restControllers);
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

    public function testBailsEarlyOnMissingControllerInRouteMatch()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setIdentity(new GuestIdentity());
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsTrueIfIdentityPassesAcls()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->getRouteMatch()->setParam('controller', 'Foo\Bar\Controller');
        $this->mvcAuthEvent->setIdentity(new GuestIdentity());
        $this->assertTrue($listener($this->mvcAuthEvent));
    }

    public function testReturnsForbiddenResponseIfIdentityFailsAcls()
    {
        $listener = $this->listener;
        $this->acl->addResource('Foo\Bar\Controller::index');
        $this->acl->deny('guest', 'Foo\Bar\Controller::index', 'POST');
        $this->mvcAuthEvent->getMvcEvent()->getRouteMatch()->setParam('controller', 'Foo\Bar\Controller');
        $this->mvcAuthEvent->getMvcEvent()->getRouteMatch()->setParam('action', 'index');
        $this->mvcAuthEvent->getMvcEvent()->getRequest()->setMethod('POST');
        $this->authentication->setIdentity(new GuestIdentity());
        $result = $listener($this->mvcAuthEvent);
        $this->assertSame($this->mvcAuthEvent->getMvcEvent()->getResponse(), $result);
        $this->assertEquals(403, $result->getStatusCode());
        $this->assertEquals('Forbidden', $result->getReasonPhrase());
    }

    public function testBuildResourceStringReturnsFalseIfControllerIsMissing()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $request    = $mvcEvent->getRequest();
        $this->assertFalse($this->listener->buildResourceString($routeMatch, $request));
    }

    public function testBuildResourceStringReturnsControllerActionFormattedStringForNonRestController()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'Foo\Bar\Controller');
        $routeMatch->setParam('action', 'foo');
        $request    = $mvcEvent->getRequest();
        $this->assertEquals('Foo\Bar\Controller::foo', $this->listener->buildResourceString($routeMatch, $request));
    }

    public function testBuildResourceStringReturnsControllerNameAndCollectionIfNoIdentifierAvailable()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'ZendCon\V1\Rest\Session\Controller');
        $request    = $mvcEvent->getRequest();
        $this->assertEquals('ZendCon\V1\Rest\Session\Controller::collection', $this->listener->buildResourceString($routeMatch, $request));
    }

    public function testBuildResourceStringReturnsControllerNameAndResourceIfIdentifierInRouteMatch()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'ZendCon\V1\Rest\Session\Controller');
        $routeMatch->setParam('session_id', 'foo');
        $request    = $mvcEvent->getRequest();
        $this->assertEquals('ZendCon\V1\Rest\Session\Controller::resource', $this->listener->buildResourceString($routeMatch, $request));
    }

    public function testBuildResourceStringReturnsControllerNameAndResourceIfIdentifierInQueryString()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'ZendCon\V1\Rest\Session\Controller');
        $request    = $mvcEvent->getRequest();
        $request->getQuery()->set('session_id', 'bar');
        $this->assertEquals('ZendCon\V1\Rest\Session\Controller::resource', $this->listener->buildResourceString($routeMatch, $request));
    }
}
