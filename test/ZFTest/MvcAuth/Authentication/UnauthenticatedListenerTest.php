<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authentication;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Response;
use ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener;
use ZF\MvcAuth\MvcAuthEvent;
use ZFTest\MvcAuth\TestAsset;

class DefaultAuthenticationPostListenerTest extends TestCase
{
    public function setUp()
    {
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setResponse($response);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->listener = new DefaultAuthenticationPostListener();
    }

    public function createMvcAuthEvent(MvcEvent $mvcEvent)
    {
        $this->authentication = new TestAsset\AuthenticationService();
        $this->authorization  = $this->getMock('ZF\MvcAuth\Authorization\AuthorizationInterface');
        return new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
    }

    public function testReturnsNullWhenEventDoesNotHaveAuthenticationResult()
    {
        $listener = $this->listener;
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsNullWhenAuthenticationResultIsValid()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setAuthenticationResult(new AuthenticationResult(1, 'foo'));
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsComposedEventResponseWhenNotAuthorizedButNotAnHttpResponse()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setAuthenticationResult(new AuthenticationResult(0, 'foo'));
        $response = new Response;
        $this->mvcAuthEvent->getMvcEvent()->setResponse($response);
        $this->assertSame($response, $listener($this->mvcAuthEvent));
    }

    public function testReturns401ResponseWhenNotAuthorizedAndHttpResponseComposed()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setAuthenticationResult(new AuthenticationResult(0, 'foo'));
        $response = $this->mvcAuthEvent->getMvcEvent()->getResponse();
        $this->assertSame($response, $listener($this->mvcAuthEvent));
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getReasonPhrase());
    }
}
