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
use ZF\MvcAuth\Authorization\DefaultResourceResolverListener;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset;

class DefaultResourceResolverListenerTest extends TestCase
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

        $this->restControllers = array(
            'ZendCon\V1\Rest\Session\Controller' => 'session_id',
        );
        $this->listener = new DefaultResourceResolverListener($this->restControllers);
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
