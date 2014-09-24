<?php

namespace ZFTest\MvcAuth\Identity;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\MvcEvent;
use ZF\MvcAuth\Identity\AuthenticatedIdentity;
use ZF\MvcAuth\Identity\IdentityPlugin;

class IdentityPluginTest extends TestCase
{
    public function setUp()
    {
        $this->event = $event = new MvcEvent();

        $controller = $this->getMock('Zend\Mvc\Controller\AbstractController');
        $controller->expects($this->any())
            ->method('getEvent')
            ->will($this->returnCallback(function () use ($event) {
                return $event;
            }));

        $this->plugin = new IdentityPlugin();
        $this->plugin->setController($controller);
    }

    public function testMissingIdentityParamInEventCausesPluginToYieldGuestIdentity()
    {
        $this->assertInstanceOf(
            'ZF\MvcAuth\Identity\GuestIdentity',
            $this->plugin->__invoke()
        );
    }

    public function testInvalidTypeInEventIdentityParamCausesPluginToYieldGuestIdentity()
    {
        $this->event->setParam('ZF\MvcAuth\Identity', (object) array('foo' => 'bar'));
        $this->assertInstanceOf(
            'ZF\MvcAuth\Identity\GuestIdentity',
            $this->plugin->__invoke()
        );
    }

    public function testValidIdentityInEventIsReturnedByPlugin()
    {
        $identity = new AuthenticatedIdentity('mwop');
        $this->event->setParam('ZF\MvcAuth\Identity', $identity);
        $this->assertSame($identity, $this->plugin->__invoke());
    }
}
