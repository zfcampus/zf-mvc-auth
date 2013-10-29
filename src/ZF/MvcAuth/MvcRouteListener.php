<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Authentication\Result;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;

class MvcRouteListener
{
    protected $mvcAuthEvent;

    /** @var \Zend\Authentication\AuthenticationService */
    protected $authentication;

    protected $configuration;

    public function __construct(MvcEvent $mvcEvent)
    {
        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent);
        $this->mvcAuthEvent->setTarget($this);

        $sm = $mvcEvent->getApplication()->getServiceManager();

        /** @var \Zend\Authentication\AuthenticationService $auth */
        $auth = $sm->get('authentication');

        $this->authentication = $auth;
        $this->configuration = $sm->get('Config');
    }

    public function authentication(MvcEvent $mvcEvent)
    {
        $em = $mvcEvent->getApplication()->getEventManager();

        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION, $this->mvcAuthEvent);

        $storage = $this->authentication->getStorage();

        $createGuestIdentity = false;

        // determine if the listener returned an identity?
        $result = $responses->last();
        if ($result instanceof Identity\IdentityInterface) {
            $storage->write($result);
        }

        // if not identity is in the authentication service, time to figure some stuff out
        if ($this->authentication->getIdentity() === null) {
            if (!$this->mvcAuthEvent->hasAuthenticationResult()) {
                // if there is no Authentication result, safe to assume we have a guest
                $createGuestIdentity = true;
            }
        }

        if ($createGuestIdentity) {
            $this->mvcAuthEvent->setIdentity(new Identity\GuestIdentity());
        }
    }

    public function authenticationPost(MvcEvent $mvcEvent)
    {
        $em = $mvcEvent->getApplication()->getEventManager();
        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $this->mvcAuthEvent);
        return $responses->last();
    }

    public function authorization(MvcEvent $event)
    {
        $em = $event->getApplication()->getEventManager();
        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHORIZATION, $this->mvcAuthEvent);
        if ($responses->last() === false) {
            $em->trigger(MvcAuthEvent::EVENT_AUTHORIZATION_DENIED, $this->mvcAuthEvent);
        }
    }

}
