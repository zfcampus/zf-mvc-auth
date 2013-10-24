<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\EventManager\Event;
use Zend\Mvc\MvcEvent;

class MvcAuthEvent extends Event
{
    const EVENT_AUTHENTICATION = 'authentication';
    const EVENT_AUTHENTICATION_POST = 'authentication.post';
    const EVENT_AUTHORIZATION = 'authorization';
    const EVENT_AUTHORIZATION_DENIED = 'authorization.denied';

    protected $authentication;
    protected $authorization;

    public function __construct(MvcEvent $mvcEvent)
    {
        $this->mvcEvent = $mvcEvent;
        /** @var \Zend\ServiceManager\ServiceManager $sm */
        $sm = $this->mvcEvent->getApplication()->getServiceManager();
        $this->authentication = $sm->get('authentication');
        if ($sm->has('authorization')) {
            $this->authorization = $sm->get('authorization');
        }
    }

    public function getAuthenticationService()
    {
        return $this->authentication;
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
        $this->authentication->getIdentity();
    }

    public function setIdentity(IdentityInterface $identity)
    {
        $this->authentication->getStorage()->write($identity);
    }

}
