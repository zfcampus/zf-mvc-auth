<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authorization;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Response;
use ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset;

class DefaultAuthorizationPostListenerTest extends TestCase
{
    public function setUp()
    {
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setResponse($response);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->listener = new DefaultAuthorizationPostListener();
    }

    public function createMvcAuthEvent(MvcEvent $mvcEvent)
    {
        $this->authentication = new TestAsset\AuthenticationService();
        $this->authorization  = $this->getMock('ZF\MvcAuth\Authorization\AuthorizationInterface');
        return new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
    }

    public function testReturnsNullWhenEventIsAuthorized()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testResetsResponseStatusTo200WhenEventIsAuthorized()
    {
        $listener = $this->listener;
        $response = $this->mvcAuthEvent->getMvcEvent()->getResponse();
        $response->setStatusCode(401);
        $this->mvcAuthEvent->setIsAuthorized(true);
        $listener($this->mvcAuthEvent);
        $this->assertEquals(200, $response->getStatusCode());
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
