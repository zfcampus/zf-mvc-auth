<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\Stdlib\ResponseInterface as Response;

class MvcRouteListener extends AbstractListenerAggregate
{
    /**
     * @var AuthenticationService
     */
    protected $authentication;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var MvcAuthEvent
     */
    protected $mvcAuthEvent;

    /**
     * @param MvcAuthEvent $mvcAuthEvent
     * @param EventManagerInterface $events
     * @param AuthenticationService $authentication
     */
    public function __construct(
        MvcAuthEvent $mvcAuthEvent,
        EventManagerInterface $events,
        AuthenticationService $authentication
    ) {
        $mvcAuthEvent->setTarget($this);
        $events->attach($this);

        $this->mvcAuthEvent   = $mvcAuthEvent;
        $this->events         = $events;
        $this->authentication = $authentication;
    }

    /**
     * Attach listeners
     *
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'authentication'), -25);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'authenticationPost'), -26);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'authorization'), -600);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'authorizationPost'), -601);
    }

    /**
     * Trigger the authentication event
     *
     * @param MvcEvent $mvcEvent
     * @return null|Response
     */
    public function authentication(MvcEvent $mvcEvent)
    {
        if (!$mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $mvcAuthEvent = $this->mvcAuthEvent;
        $responses    = $this->events->trigger($mvcAuthEvent::EVENT_AUTHENTICATION, $mvcAuthEvent, function ($r) {
            return ($r instanceof Identity\IdentityInterface
                || $r instanceof Result
                || $r instanceof Response
            );
        });

        $result  = $responses->last();
        $storage = $this->authentication->getStorage();

        // If we have a response, return immediately
        if ($result instanceof Response) {
            return $result;
        }

        // Determine if the listener returned an identity
        if ($result instanceof Identity\IdentityInterface) {
            $storage->write($result);
        }

        // If we have a Result, we create an AuthenticatedIdentity from it
        if ($result instanceof Result
            && $result->isValid()
        ) {
            $mvcAuthEvent->setAuthenticationResult($result);
            $mvcAuthEvent->setIdentity(new Identity\AuthenticatedIdentity($result->getIdentity()));
            return;
        }

        $identity = $this->authentication->getIdentity();
        if ($identity === null && !$mvcAuthEvent->hasAuthenticationResult()) {
            // if there is no Authentication identity or result, safe to assume we have a guest
            $mvcAuthEvent->setIdentity(new Identity\GuestIdentity());
            return;
        }

        if ($mvcAuthEvent->hasAuthenticationResult()
            && $mvcAuthEvent->getAuthenticationResult()->isValid()
        ) {
            $mvcAuthEvent->setIdentity(
                new Identity\AuthenticatedIdentity(
                    $mvcAuthEvent->getAuthenticationResult()->getIdentity()
                )
            );
        }

        if ($identity instanceof Identity\IdentityInterface) {
            $mvcAuthEvent->setIdentity($identity);
            return;
        }

        if ($identity !== null) {
            // identity found in authentication; we can assume we're authenticated
            $mvcAuthEvent->setIdentity(new Identity\AuthenticatedIdentity($identity));
            return;
        }
    }

    /**
     * Trigger the authentication.post event
     *
     * @param MvcEvent $mvcEvent
     * @return Response|mixed
     */
    public function authenticationPost(MvcEvent $mvcEvent)
    {
        if (!$mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $responses = $this->events->trigger(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $this->mvcAuthEvent,
            function ($r) {
                return ($r instanceof Response);
            }
        );
        return $responses->last();
    }

    /**
     * Trigger the authorization event
     *
     * @param MvcEvent $mvcEvent
     * @return null|Response
     */
    public function authorization(MvcEvent $mvcEvent)
    {
        if (!$mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $responses = $this->events->trigger(MvcAuthEvent::EVENT_AUTHORIZATION, $this->mvcAuthEvent, function ($r) {
            return (is_bool($r) || $r instanceof Response);
        });

        $result = $responses->last();

        if (is_bool($result)) {
            $this->mvcAuthEvent->setIsAuthorized($result);
            return;
        }

        if ($result instanceof Response) {
            return $result;
        }
    }

    /**
     * Trigger the authorization.post event
     *
     * @param MvcEvent $mvcEvent
     * @return null|Response
     */
    public function authorizationPost(MvcEvent $mvcEvent)
    {
        if (!$mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $responses = $this->events->trigger(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $this->mvcAuthEvent, function ($r) {
            return ($r instanceof Response);
        });
        return $responses->last();
    }
}
