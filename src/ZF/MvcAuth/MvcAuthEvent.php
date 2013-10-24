<?php
/**
 * Created by PhpStorm.
 * User: ralphschindler
 * Date: 10/22/13
 * Time: 5:07 PM
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

    public function __construct(MvcEvent $mvcEvent)
    {
        $this->mvcEvent = $mvcEvent;
    }

    public function getAuthenticationService()
    {
        return $this->mvcEvent->getApplication()->getServiceManager()->get('mvc-auth-authentication');
    }

    public function getAuthorizationService()
    {
        return $this->mvcEvent->getApplication()->getServiceManager()->get('mvc-auth-authorization');
    }

    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

}
