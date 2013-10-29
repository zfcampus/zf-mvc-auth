<?php

namespace ZFTest\MvcAuth;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Config\Config as Configuration;
use Zend\EventManager\EventManager;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\DefaultAuthenticationListener;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthenticationListenerTest extends TestCase
{
    /** @var HttpRequest */
    protected $request;
    /** @var HttpResponse */
    protected $response;

    /** @var AuthenticationService */
    protected $authentication;
    /** @var Acl */
    protected $authorization;
    /** @var array */
    protected $restControllers = array();
    /** @var DefaultAuthenticationListener */
    protected $listener;
    /** @var MvcAuthEvent */
    protected $mvcAuthEvent;

    /** @var \Zend\Config\Config */
    protected $configuration;

    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService(new NonPersistent());

        // authorization service
        $this->authorization = new Acl();
        $this->authorization->addRole('guest');
        $this->authorization->allow();

        $this->configuration = new Configuration(array());

        // event for mvc and mvc-auth
        $routeMatch = new RouteMatch(array());
        $this->request    = new HttpRequest();
        $this->response   = new HttpResponse();
        $application = new Application($this->configuration, new ServiceManager(new ServiceConfig(array('services' => array(
            'event_manager' => new EventManager(),
            'authentication' => $this->authentication,
            'authorization' => $this->authorization,
            'request' => $this->request,
            'response' => $this->response,
            'configuration' => $this->configuration
        )))));

        $mvcEvent   = new MvcEvent();
        $mvcEvent->setRequest($this->request)
            ->setResponse($this->response)
            ->setRouteMatch($routeMatch)
            ->setApplication($application);

        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);

        $this->restControllers = array(
            'ZendCon\V1\Rest\Session\Controller' => 'session_id',
        );
        $this->listener = new DefaultAuthenticationListener($this->authorization, $this->restControllers);
    }

    public function testInvokeReturnsEarlyWhenNotHttpRequest()
    {
        $this->mvcAuthEvent->getMvcEvent()->setRequest(new \Zend\Stdlib\Request());
        $this->assertNull($this->listener->__invoke($this->mvcAuthEvent));
    }

    public function testInvokeForBasicAuthAddsAuthorizationHeader()
    {
        $this->configuration->merge(new Configuration(array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/TestAsset/htpasswd'
                    )
                )
            )
        )));

        $this->listener->__invoke($this->mvcAuthEvent);
        /** @var \ZF\MvcAuth\Identity\AuthenticatedIdentity $identity */

        $authHeaders = $this->response->getHeaders()->get('WWW-Authenticate');
        $authHeader = $authHeaders[0];
        $this->assertEquals('Basic realm="My Web Site"', $authHeader->getFieldValue());
    }

    public function testInvokeForBasicAuthSetsIdentityWhenValid()
    {
        $this->configuration->merge(new Configuration(array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/TestAsset/htpasswd'
                    )
                )
            )
        )));

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
        $this->listener->__invoke($this->mvcAuthEvent);
        /** @var \ZF\MvcAuth\Identity\AuthenticatedIdentity $identity */
        $identity = $this->mvcAuthEvent->getIdentity();
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
        $this->assertEquals('user', $identity->getRoleId());
    }

    public function testInvokeForBasicAuthHasNoIdentityWhenNotValid()
    {
        $this->configuration->merge(new Configuration(array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/TestAsset/htpasswd'
                    )
                )
            )
        )));

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic xxxxxxxxx');
        $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertNull($this->mvcAuthEvent->getIdentity());
    }


    public function testInvokeForDigestAuthAddsAuthorizationHeader()
    {
        $this->configuration->merge(new Configuration(array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/TestAsset/htdigest'
                    )
                )
            )
        )));

        $this->listener->__invoke($this->mvcAuthEvent);
        /** @var \ZF\MvcAuth\Identity\AuthenticatedIdentity $identity */

        $authHeaders = $this->response->getHeaders()->get('WWW-Authenticate');
        $authHeader = $authHeaders[0];
        $this->assertEquals('Digest realm="User Area", domain="/", nonce="1d5a29a623bb4350962d2ea62e741aca", opaque="e66aa41ca5bf6992a5479102cc787bc9", algorithm="MD5", qop="auth"', $authHeader->getFieldValue());
    }

}