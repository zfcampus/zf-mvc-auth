<?php

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use ZF\MvcAuth\MvcRouteListener;

class MvcRouteListenerTest extends TestCase
{
    private $listener;

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



}
