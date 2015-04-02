<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
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

    /**
     * @var MvcEvent
     */
    protected $mvcEvent;

    /**
     * @var mixed
     */
    protected $authentication;

    /**
     * @var Result
     */
    protected $authenticationResult = null;

    /**
     * @var mixed
     */
    protected $authorization;

    /**
     * Whether or not authorization has completed/succeeded
     * @var bool
     */
    protected $authorized = false;

    /**
     * The resource used for authorization queries
     *
     * @var mixed
     */
    protected $resource;

    /**
     * @param MvcEvent $mvcEvent
     * @param mixed    $authentication
     * @param mixed    $authorization
     */
    public function __construct(MvcEvent $mvcEvent, $authentication, $authorization)
    {
        $this->mvcEvent       = $mvcEvent;
        $this->authentication = $authentication;
        $this->authorization  = $authorization;
    }

    /**
     * @return mixed
     */
    public function getAuthenticationService()
    {
        return $this->authentication;
    }

    /**
     * @return bool
     */
    public function hasAuthenticationResult()
    {
        return ($this->authenticationResult !== null);
    }

    /**
     * @param  Result $result
     * @return self
     */
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

    /**
     * @return mixed
     */
    public function getAuthorizationService()
    {
        return $this->authorization;
    }

    /**
     * @return MvcEvent
     */
    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

    /**
     * @return mixed|null
     */
    public function getIdentity()
    {
        return $this->authentication->getIdentity();
    }

    /**
     * @param IdentityInterface $identity
     * @return $this
     */
    public function setIdentity(IdentityInterface $identity)
    {
        $this->authentication->getStorage()->write($identity);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param  mixed $resource
     * @return self
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

    /**
     * @param  bool $flag
     * @return self
     */
    public function setIsAuthorized($flag)
    {
        $this->authorized = (bool) $flag;
        return $this;
    }
}
