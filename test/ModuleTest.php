<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
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

    protected function createApplication(ServiceManager $services, EventManagerInterface $events)
    {
        $r = new ReflectionMethod(Application::class, '__construct');
        if ($r->getNumberOfRequiredParameters() === 2) {
            // zend-mvc v2
            return new Application([], $services, $events);
        }

        // zend-mvc v3
        return new Application($services, $events);
    }

    protected function createServiceManager(array $config)
    {
        if (method_exists(ServiceManager::class, 'configure')) { // v3
            // zend-servicemanager v3
            return new ServiceManager($config['service_manager']);
        }

        // zend-servicemanager v2
        $servicesConfig = new ServiceManagerConfig($config['service_manager']);
        return new ServiceManager($servicesConfig);
    }

    public function testOnBootstrapReturnsEarlyForNonHttpEvents()
    {
        $mvcEvent = $this->prophesize(MvcEvent::class);
        $module = new Module();

        $request = $this->prophesize(RequestInterface::class)->reveal();
        $mvcEvent->getRequest()->willReturn($request);
        $module->onBootstrap($mvcEvent->reveal());

        $this->assertAttributeEmpty('container', $module);
    }

    public function expectedListeners()
    {
        $module = new Module();
        $config = $module->getConfig();
        $request = $this->prophesize(Request::class)->reveal();
        $response = $this->prophesize(Response::class)->reveal();

        $services = $this->createServiceManager($config);
        $services->setService('Request', $request);
        $services->setService('Response', $response);
        $services->setService('config', $config);

        $events = new EventManager();

        $application = $this->createApplication($services, $events);

        $mvcEvent = new MvcEvent(MvcEvent::EVENT_BOOTSTRAP);
        $mvcEvent->setApplication($application);
        $mvcEvent->setRequest($request);
        $mvcEvent->setResponse($response);

        $module->onBootstrap($mvcEvent);

        // @codingStandardsIgnoreStart
        return [
            'mvc-route-authentication'         => [[$module->getMvcRouteListener(), 'authentication'],        -50, MvcEvent::EVENT_ROUTE,                   $events],
            'mvc-route-authentication-post'    => [[$module->getMvcRouteListener(), 'authenticationPost'],    -51, MvcEvent::EVENT_ROUTE,                   $events],
            'mvc-route-authorization'          => [[$module->getMvcRouteListener(), 'authorization'],        -600, MvcEvent::EVENT_ROUTE,                   $events],
            'mvc-route-authorization-post'     => [[$module->getMvcRouteListener(), 'authorizationPost'],    -601, MvcEvent::EVENT_ROUTE,                   $events],
            'authentication'                   => [$services->get(DefaultAuthenticationListener::class),        1, MvcAuthEvent::EVENT_AUTHENTICATION,      $events],
            'authentication-post'              => [$services->get(DefaultAuthenticationPostListener::class),    1, MvcAuthEvent::EVENT_AUTHENTICATION_POST, $events],
            'resource-resoolver-authorization' => [$services->get(DefaultResourceResolverListener::class),   1000, MvcAuthEvent::EVENT_AUTHORIZATION,       $events],
            'authorization'                    => [$services->get(DefaultAuthorizationListener::class),         1, MvcAuthEvent::EVENT_AUTHORIZATION,       $events],
            'authorization-post'               => [$services->get(DefaultAuthorizationPostListener::class),     1, MvcAuthEvent::EVENT_AUTHORIZATION_POST,  $events],
            'module-authentication-post'       => [[$module, 'onAuthenticationPost'],                          -1, MvcAuthEvent::EVENT_AUTHENTICATION_POST, $events],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider expectedListeners
     */
    public function testOnBootstrapAttachesListeners(callable $listener, $priority, $event, EventManager $events)
    {
        $this->assertListenerAtPriority(
            $listener,
            $priority,
            $event,
            $events
        );
    }
}
