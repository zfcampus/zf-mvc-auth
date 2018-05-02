<?php

namespace ZFTest\MvcAuth;

use PHPUnit\Framework\TestCase;
use Zend\Authentication\AuthenticationService;
use Zend\EventManager\EventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Mvc\MvcEvent;
use ZF\MvcAuth\MvcAuthEvent;
use ZF\MvcAuth\MvcRouteListener;

class MvcRouteListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    private $listener;

    public function setUp()
    {
        $this->events = new EventManager;
        $this->auth   = $this
            ->getMockBuilder(AuthenticationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event  = $this
            ->getMockBuilder(MvcAuthEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MvcRouteListener(
            $this->event,
            $this->events,
            $this->auth
        );
    }

    public function testRegistersAuthenticationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authentication'],
            -50,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }

    public function testRegistersPostAuthenticationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authenticationPost'],
            -51,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }

    public function testRegistersAuthorizationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authorization'],
            -600,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }

    public function testRegistersPostAuthorizationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authorizationPost'],
            -601,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }
}
