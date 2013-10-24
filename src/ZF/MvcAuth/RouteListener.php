<?php

namespace ZF\MvcAuth;

use Zend\Mvc\MvcEvent;

class RouteListener
{
    protected $mvcAuthEvent;

    public function __construct(MvcEvent $event)
    {
        $this->mvcAuthEvent = new MvcAuthEvent($event);
        $this->mvcAuthEvent->setTarget($this);
    }

    public function authentication(MvcEvent $event)
    {
        $em = $event->getApplication()->getEventManager();
        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION, $this->mvcAuthEvent);

        $identity = $responses->last();
        $this->mvcAuthEvent->setIdentity($identity);

        $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $this->mvcAuthEvent);
    }

    public function authorization(MvcEvent $event)
    {
        $sm = $event->getApplication()->getServiceManager();
        if (!$sm->has('authorization')) {
            return;
        }

        $em = $event->getApplication()->getEventManager();
        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHORIZATION, $this->mvcAuthEvent);
        if ($responses->last() === false) {
            $em->trigger(MvcAuthEvent::EVENT_AUTHORIZATION_DENIED, $this->mvcAuthEvent);
        }
    }

} 