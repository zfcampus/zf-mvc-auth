<?php

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use ZF\MvcAuth\MvcRouteListener;

class MvcRouteListenerTest extends TestCase
{
    public function setUp()
    {
        $this->events = new EventManager;
        $this->auth   = $this
            ->getMockBuilder('Zend\Authentication\AuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event  = $this
            ->getMockBuilder('ZF\MvcAuth\MvcAuthEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MvcRouteListener(
            $this->event,
            $this->events,
            $this->auth
        );
    }

    public function assertListenerAtPriority($priority, $expectedCallback, $listeners, $message = '')
    {
        $found = false;
        foreach ($listeners as $listener) {
            $this->assertInstanceOf('Zend\Stdlib\CallbackHandler', $listener);
            if ($listener->getMetadatum('priority') !== $priority) {
                continue;
            }

            if ($listener->getCallback() === $expectedCallback) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, $message);
    }

    public function testRegistersAuthenticationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -25,
            array($this->listener, 'authentication'),
            $this->events->getListeners('route')
        );
    }

    public function testRegistersPostAuthenticationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -26,
            array($this->listener, 'authenticationPost'),
            $this->events->getListeners('route')
        );
    }

    public function testRegistersAuthorizationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -600,
            array($this->listener, 'authorization'),
            $this->events->getListeners('route')
        );
    }

    public function testRegistersPostAuthorizationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -601,
            array($this->listener, 'authorizationPost'),
            $this->events->getListeners('route')
        );
    }
}
