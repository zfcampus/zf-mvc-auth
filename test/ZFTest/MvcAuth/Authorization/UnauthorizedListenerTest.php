<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authorization;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Response;
use ZF\MvcAuth\Authorization\UnauthorizedListener;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset;

class UnauthorizedListenerTest extends TestCase
{
    public function setUp()
    {
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setResponse($response);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->listener = new UnauthorizedListener();
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

    public function testReturnsNullWhenEventIsAuthorized()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsComposedEventResponseWhenNotAuthorizedButNotAnHttpResponse()
    {
        $listener = $this->listener;
        $response = new Response;
        $this->mvcAuthEvent->getMvcEvent()->setResponse($response);
        $this->assertSame($response, $listener($this->mvcAuthEvent));
    }

    public function testReturns403ResponseWhenNotAuthorizedAndHttpResponseComposed()
    {
        $listener = $this->listener;
        $response = $this->mvcAuthEvent->getMvcEvent()->getResponse();
        $this->assertSame($response, $listener($this->mvcAuthEvent));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Forbidden', $response->getReasonPhrase());
    }
}
