<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\RequestInterface;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener;
use ZF\MvcAuth\Authorization\DefaultAuthorizationListener;
use ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener;
use ZF\MvcAuth\Authorization\DefaultResourceResolverListener;
use ZF\MvcAuth\Module;
use ZF\MvcAuth\MvcAuthEvent;

class ModuleTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    public function testOnBootstrapReturnsEarlyForNonHttpEvents()
    {
        $mvcEvent = $this->prophesize(MvcEvent::class);
        $module = new Module();

        $request = $this->prophesize(RequestInterface::class);
        $mvcEvent->getRequest()->will([$request, 'reveal']);
        $module->onBootstrap($mvcEvent->reveal());

        $this->assertNull($module->getMvcRouteListener());
    }

    public function testOnBootstrapAttachesListeners()
    {
        $module = new Module();
        $config = $module->getConfig();
        $request = $this->prophesize(Request::class);
        $response = $this->prophesize(Response::class);

        if (method_exists(ServiceManager::class, 'configure')) { // v3
            $services = new ServiceManager($config['service_manager']);
        } else { // v2
            $servicesConfig = new ServiceManagerConfig($config['service_manager']);
            $services = new ServiceManager($servicesConfig);
        }
        $services->setService('Request', $request->reveal());
        $services->setService('Response', $response->reveal());
        $services->setService('config', $config);

        $events = new EventManager();

        $application = $this->createApplication($services, $events);

        $mvcEvent = new MvcEvent(MvcEvent::EVENT_BOOTSTRAP);
        $mvcEvent->setApplication($application);
        $mvcEvent->setRequest($services->get('Request'));
        $mvcEvent->setResponse($services->get('Response'));

        $module->onBootstrap($mvcEvent);

        $listeners = [
            [[$module->getMvcRouteListener(), 'authentication'], -50, MvcEvent::EVENT_ROUTE],
            [[$module->getMvcRouteListener(), 'authenticationPost'], -51, MvcEvent::EVENT_ROUTE],
            [[$module->getMvcRouteListener(), 'authorization'], -600, MvcEvent::EVENT_ROUTE],
            [[$module->getMvcRouteListener(), 'authorizationPost'], -601, MvcEvent::EVENT_ROUTE],
            [$services->get(DefaultAuthenticationListener::class), 1, MvcAuthEvent::EVENT_AUTHENTICATION],
            [$services->get(DefaultAuthenticationPostListener::class), 1, MvcAuthEvent::EVENT_AUTHENTICATION_POST],
            [$services->get(DefaultResourceResolverListener::class), 1000, MvcAuthEvent::EVENT_AUTHORIZATION],
            [$services->get(DefaultAuthorizationListener::class), 1, MvcAuthEvent::EVENT_AUTHORIZATION],
            [$services->get(DefaultAuthorizationPostListener::class), 1, MvcAuthEvent::EVENT_AUTHORIZATION_POST],
            [[$module, 'onAuthenticationPost'], -1, MvcAuthEvent::EVENT_AUTHENTICATION_POST],
        ];

        foreach ($listeners as $listener) {
            $this->assertListenerAtPriority(
                $listener[0],
                $listener[1],
                $listener[2],
                $events
            );
        }
    }

    protected function createApplication(ServiceManager $services, EventManagerInterface $events)
    {
        $r = new \ReflectionMethod(Application::class, '__construct');
        if ($r->getNumberOfRequiredParameters() === 2) {
            // v2
            return new Application([], $services, $events);
        }

        return new Application($services, $events);
    }
}
