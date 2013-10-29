<?php

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Authentication\AuthenticationService;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use ZF\MvcAuth\DefaultAuthenticationPostListener;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthenticationPostListenerTest extends TestCase
{
    public function testInvokeReturnsResponseWhenIdentityNull()
    {
        $listener = new DefaultAuthenticationPostListener();
        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($originalResponse = new Response());
        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, new AuthenticationService(), null);
        $response = $listener->__invoke($mvcAuthEvent);

        $this->assertSame($originalResponse, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getReasonPhrase());
    }
}