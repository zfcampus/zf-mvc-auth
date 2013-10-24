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

    protected $authenticateIdentity = false;

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

    public function authenticationPreRoute(MvcEvent $event)
    {
        $request = $event->getRequest();
        if (!$request instanceof HttpRequest) {
            return;
        }

        if ($request->getHeader('Authorization') === false) {
            return;
        }

        $this->authenticateIdentity = true;

        $em = $event->getApplication()->getEventManager();

        try {
            $responses = $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION, $this->mvcAuthEvent);

            $storage = $this->authentication->getStorage();

            // determine if the listener returned an identity?
            $identity = $responses->last();
            if ($identity instanceof IdentityInterface) {
                $storage->write($identity);
            }
        } catch (\Exception $e) {

        }

        $em->trigger(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $this->mvcAuthEvent);
    }

    public function authenticationPostRoute(MvcEvent $event)
    {
        $storage = $this->authentication->getStorage();

        if ($this->authenticateIdentity) {
            if ($storage->isEmpty()) {
                /**
                 * @todo By default, when we get an authentication failure, we should allow a
                 *       a configured controller to run so that consumers can override this behavior
                 *       for example, Apigility might want to return an ApiProblem
                 *       (ApiProblem is not a dependency of zf-mvc-auth)
                 */

                /** @var \Zend\Mvc\Router\RouteMatch $routeMatch */
                $routeMatch = $event->getRouteMatch();

                if (isset($this->configuration['zf-mvc-auth']['controller'])) {
                    $controller = $this->configuration['zf-mvc-auth']['controller'];
                } else {
                    $controller = 'ZF\MvcAuth\Auth';
                }

                $routeMatch->setParam('controller', $controller); // @todo This should come from config
                $routeMatch->setParam('action', 'authenticationFailure');
            }
        } else {
            if ($storage->isEmpty()) {
                $storage->write(new Identity); // default Guest Identity
            }
        }
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