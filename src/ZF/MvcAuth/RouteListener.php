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
        $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION, $this->mvcAuthEvent);
    }

    public function authorization(MvcEvent $event)
    {
        $em = $event->getApplication()->getEventManager();
        $em->trigger(MvcAuthEvent::EVENT_AUTHORIZATION, $this->mvcAuthEvent);
    }

} 