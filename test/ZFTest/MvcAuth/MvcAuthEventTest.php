<?php

namespace ZFTest\MvcAuth;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\EventManager\EventManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;
use PHPUnit_Framework_TestCase as TestCase;

class MvcAuthEventTest extends TestCase
{

    /** @var MvcAuthEvent */
    protected $mvcAuthEvent = null;

    public function setup()
    {
        $mvcEvent = new MvcEvent();
        $mvcEvent->setApplication(
            new Application(
                null,
                new ServiceManager(new Config(array(
                    'services' => array(
                        'authentication' => new AuthenticationService(),
                        'event_manager' => new EventManager(),
                        'request' => new Request(),
                        'response' => new Response()
                    )
                )))
            )
        );
        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent);
    }

    public function testGetAuthenticationService()
    {
        $this->assertInstanceOf('Zend\Authentication\AuthenticationService', $this->mvcAuthEvent->getAuthenticationService());
    }

    public function testHasAuthenticationResult()
    {
        $this->assertFalse($this->mvcAuthEvent->hasAuthenticationResult());
        $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'));
        $this->assertTrue($this->mvcAuthEvent->hasAuthenticationResult());
    }

    public function testSetAuthenticationResult()
    {
        $this->assertSame($this->mvcAuthEvent, $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar')));
    }

    public function testGetAuthenticationResult()
    {
        $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'));
        $this->assertInstanceOf('Zend\Authentication\Result', $this->mvcAuthEvent->getAuthenticationResult());
    }

    public function testGetAuthorizationService()
    {
        $this->assertNull($this->mvcAuthEvent->getAuthorizationService());
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


}
 