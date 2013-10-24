<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;

class RouteListener
{
    protected $mvcAuthEvent;

    /** @var \Zend\Authentication\AuthenticationService */
    protected $authentication;

    protected $configuration;

    public function __construct(MvcEvent $event)
    {
        $this->mvcAuthEvent = new MvcAuthEvent($event);
        $this->mvcAuthEvent->setTarget($this);

        $sm = $event->getApplication()->getServiceManager();

        /** @var \Zend\Authentication\AuthenticationService $auth */
        $auth = $sm->get('authentication');

        $this->authentication = $auth;
        $this->configuration = $sm->get('Config');
    }

    public function authentication(MvcEvent $event)
    {
        $em = $event->getApplication()->getEventManager();

        $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION, $this->mvcAuthEvent);

        $storage = $this->authentication->getStorage();

        // determine if the listener returned an identity?
        $identity = $responses->last();
        if ($identity instanceof IdentityInterface) {
            $storage->write($identity);
        }

        if ($storage->isEmpty()) {
            $storage->write(new Identity);
        }

        $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $this->mvcAuthEvent);
    }

    public function authorization(MvcEvent $event)
    {
        $sm = $event->getApplication()->getServiceManager();

        // currently only run if we have a authorization service (RBAC or ACL)
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