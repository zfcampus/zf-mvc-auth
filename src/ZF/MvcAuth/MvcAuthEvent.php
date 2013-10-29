<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Authentication\Result;
use Zend\EventManager\Event;
use Zend\Mvc\MvcEvent;
use ZF\MvcAuth\Identity\IdentityInterface;

class MvcAuthEvent extends Event
{
    const EVENT_AUTHENTICATION = 'authentication';
    const EVENT_AUTHENTICATION_POST = 'authentication.post';
    const EVENT_AUTHORIZATION = 'authorization';
    const EVENT_AUTHORIZATION_POST = 'authorization.post';

    protected $mvcEvent;

    protected $authentication;

    /** @var Result */
    protected $authenticationResult = null;

    protected $authorization;

    public function __construct(MvcEvent $mvcEvent, $authentication, $authorization)
    {
        $this->mvcEvent = $mvcEvent;
        $this->authentication = $authentication;
        $this->authorization = $authorization;
    }

    /**
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getAuthenticationService()
    {
        return $this->authentication;
    }

    public function hasAuthenticationResult()
    {
        return ($this->authenticationResult !== null);
    }

    public function setAuthenticationResult(Result $result)
    {
        $this->authenticationResult = $result;
        return $this;
    }

    /**
     * @return null|Result
     */
    public function getAuthenticationResult()
    {
        return $this->authenticationResult;
    }

    public function getAuthorizationService()
    {
        return $this->authorization;
    }

    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

    public function getIdentity()
    {
        return $this->authentication->getIdentity();
    }

    public function setIdentity(IdentityInterface $identity)
    {
        $this->authentication->getStorage()->write($identity);
        return $this;
    }

}
