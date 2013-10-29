<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Authentication\Result;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\Stdlib\Response;

class MvcRouteListener
{
    protected $mvcAuthEvent;

    protected $configuration;

    public function __construct(MvcAuthEvent $mvcAuthEvent, $configuration)
    {
        $this->mvcAuthEvent = $mvcAuthEvent;
        $this->mvcAuthEvent->setTarget($this);
    }

    public function authentication(MvcEvent $mvcEvent)
    {
        $em = $mvcEvent->getApplication()->getEventManager();

        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION, $this->mvcAuthEvent);

        $authentication = $this->mvcAuthEvent->getAuthenticationService();
        $storage = $authentication->getStorage();

        $createGuestIdentity = false;

        // determine if the listener returned an identity?
        $result = $responses->last();
        if ($result instanceof Identity\IdentityInterface) {
            $storage->write($result);
        } elseif ($result instanceof Response) {
            return $result;
        }

        // if not identity is in the authentication service, time to figure some stuff out
        if ($authentication->getIdentity() === null) {
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
        $result = $responses->last();
        // authorization: returns bool, or Response
        if (is_bool($result)) {
            // $this->mvcAuthEvent->setIsAuthorized($result); // @todo Matthew fill this in
        } elseif ($result instanceof Response) {
            return $result;
        }
    }

    public function authorizationPost(MvcEvent $event)
    {
        $em = $event->getApplication()->getEventManager();
        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $this->mvcAuthEvent);
        return $responses->last();
    }

}
