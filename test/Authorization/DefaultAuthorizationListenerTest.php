<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authorization;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Request;
use Zend\Stdlib\Response;
use ZF\MvcAuth\Authorization\AclAuthorization;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset\AuthenticationService;

class DefaultAuthorizationListenerTest extends TestCase
{
    /**
     * @var AuthenticationService
     */
    protected $authentication;

    /**
     * @var Acl
     */
    protected $authorization;

    /**
     * @var array
     */
    protected $restControllers = array();

    /**
     * @var DefaultAuthorizationListener
     */
    protected $listener;

    /**
     * @var MvcAuthEvent
     */
    protected $mvcAuthEvent;

    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService;

        // authorization service
        $this->authorization = new AclAuthorization();
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

        $this->listener = new DefaultAuthorizationListener($this->authorization);
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
        $this->authorization->addResource('Foo\Bar\Controller::index');
        $this->authorization->deny('guest', 'Foo\Bar\Controller::index', 'POST');
        $this->mvcAuthEvent->setResource('Foo\Bar\Controller::index');
        $this->mvcAuthEvent->getMvcEvent()->getRequest()->setMethod('POST');
        $this->authentication->setIdentity(new GuestIdentity());
        $this->assertFalse($listener($this->mvcAuthEvent));
    }
}
