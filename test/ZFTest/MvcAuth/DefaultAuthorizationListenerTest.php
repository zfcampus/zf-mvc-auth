<?php

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Request;
use Zend\Stdlib\Response;
use ZF\MvcAuth\DefaultAuthorizationListener;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset\AuthenticationService;

class DefaultAuthorizationListenerTest extends TestCase
{
    /** @var AuthenticationService */
    protected $authentication;
    /** @var Acl */
    protected $authorization;
    /** @var array */
    protected $restControllers = array();
    /** @var DefaultAuthorizationListener */
    protected $listener;
    /** @var MvcAuthEvent */
    protected $mvcAuthEvent;

    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService;

        // authorization service
        $this->authorization = new Acl();
        $this->authorization->addRole('guest');
        $this->authorization->allow();

        // event for mvc and mvc-auth
        $routeMatch = new RouteMatch(array());
        $request    = new HttpRequest();
        $response   = new HttpResponse();
        $application = new Application(null, new ServiceManager(new Config(array('services' => array(
            'event_manager' => new EventManager(),
            'authentication' => $this->authentication,
            'authorization' => $this->authorization,
            'request' => $request,
            'response' => $response
        )))));

        $mvcEvent   = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response)
            ->setRouteMatch($routeMatch)
            ->setApplication($application);

        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);

        $this->restControllers = array(
            'ZendCon\V1\Rest\Session\Controller' => 'session_id',
        );
        $this->listener = new DefaultAuthorizationListener($this->authorization, $this->restControllers);
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
        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);

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
        $this->authorization->addResource('Foo\Bar\Controller::index');
        $this->authorization->deny('guest', 'Foo\Bar\Controller::index', 'POST');
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
