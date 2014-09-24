<?php

namespace ZFTest\MvcAuth;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\Mvc\MvcEvent;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;
use Zend\Permissions\Acl\Acl;
use PHPUnit_Framework_TestCase as TestCase;

class MvcAuthEventTest extends TestCase
{

    /**
     * @var MvcAuthEvent
     */
    protected $mvcAuthEvent = null;

    public function setup()
    {
        $mvcEvent = new MvcEvent();
        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, new AuthenticationService(), new Acl);
    }

    public function testGetAuthenticationService()
    {
        $this->assertInstanceOf(
            'Zend\Authentication\AuthenticationService',
            $this->mvcAuthEvent->getAuthenticationService()
        );
    }

    public function testHasAuthenticationResult()
    {
        $this->assertFalse($this->mvcAuthEvent->hasAuthenticationResult());
        $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'));
        $this->assertTrue($this->mvcAuthEvent->hasAuthenticationResult());
    }

    public function testSetAuthenticationResult()
    {
        $this->assertSame(
            $this->mvcAuthEvent,
            $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'))
        );
    }

    public function testGetAuthenticationResult()
    {
        $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'));
        $this->assertInstanceOf('Zend\Authentication\Result', $this->mvcAuthEvent->getAuthenticationResult());
    }

    public function testGetAuthorizationService()
    {
        $this->assertInstanceOf('Zend\Permissions\Acl\Acl', $this->mvcAuthEvent->getAuthorizationService());
    }

    public function testGetMvcEvent()
    {
        $this->assertInstanceOf('Zend\Mvc\MvcEvent', $this->mvcAuthEvent->getMvcEvent());
    }

    public function testSetIdentity()
    {
        $this->assertSame($this->mvcAuthEvent, $this->mvcAuthEvent->setIdentity(new GuestIdentity()));
    }

    public function testGetIdentity()
    {
        $this->mvcAuthEvent->setIdentity($i = new GuestIdentity());
        $this->assertSame($i, $this->mvcAuthEvent->getIdentity());
    }

    public function testResourceStringIsNullByDefault()
    {
        $this->assertNull($this->mvcAuthEvent->getResource());
    }

    /**
     * @depends testResourceStringIsNullByDefault
     */
    public function testResourceStringIsMutable()
    {
        $this->mvcAuthEvent->setResource('foo');
        $this->assertEquals('foo', $this->mvcAuthEvent->getResource());
    }

    public function testAuthorizedFlagIsFalseByDefault()
    {
        $this->assertFalse($this->mvcAuthEvent->isAuthorized());
    }

    /**
     * @depends testAuthorizedFlagIsFalseByDefault
     */
    public function testAuthorizedFlagIsMutable()
    {
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertTrue($this->mvcAuthEvent->isAuthorized());
    }
}
