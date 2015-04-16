<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authentication;

use PHPUnit_Framework_TestCase as TestCase;
use OAuth2\Request as OAuth2Request;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Request;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Authentication\HttpAdapter;
use ZF\MvcAuth\Authentication\OAuth2Adapter;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthenticationListenerTest extends TestCase
{
    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var AuthenticationService
     */
    protected $authentication;

    protected $authorization;

    /**
     * @var array
     */
    protected $restControllers = array();

    /**
     * @var DefaultAuthenticationListener
     */
    protected $listener;

    /**
     * @var MvcAuthEvent
     */
    protected $mvcAuthEvent;

    /**
     * @var \Zend\Config\Config
     */
    protected $configuration;

    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService(new NonPersistent());

        // authorization service
        $this->authorization = $this->getMock('ZF\MvcAuth\Authorization\AuthorizationInterface');

        // event for mvc and mvc-auth
        $this->request    = new HttpRequest();
        $this->response   = new HttpResponse();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($this->request)
            ->setResponse($this->response);

        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
        $this->listener     = new DefaultAuthenticationListener();
    }

    public function testInvokeReturnsEarlyWhenNotHttpRequest()
    {
        $this->mvcAuthEvent->getMvcEvent()->setRequest(new Request());
        $this->assertNull($this->listener->__invoke($this->mvcAuthEvent));
    }

    public function testInvokeForBasicAuthAddsAuthorizationHeader()
    {
        $httpAuth = new HttpAuth(array(
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ));
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->listener->__invoke($this->mvcAuthEvent);

        $authHeaders = $this->response->getHeaders()->get('WWW-Authenticate');
        $authHeader = $authHeaders[0];
        $this->assertInstanceOf('Zend\Http\Header\HeaderInterface', $authHeader);
        $this->assertEquals('Basic realm="My Web Site"', $authHeader->getFieldValue());
    }

    public function testInvokeForBasicAuthSetsIdentityWhenValid()
    {
        $httpAuth = new HttpAuth(array(
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ));
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
        $this->assertEquals('user', $identity->getRoleId());
        return array('identity' => $identity, 'mvc_event' => $this->mvcAuthEvent->getMvcEvent());
    }

    public function testInvokeForBasicAuthSetsGuestIdentityWhenValid()
    {
        $httpAuth = new HttpAuth(array(
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ));
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic xxxxxxxxx');
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $identity);
        $this->assertEquals('guest', $identity->getRoleId());
        return array('identity' => $identity, 'mvc_event' => $this->mvcAuthEvent->getMvcEvent());
    }

    public function testInvokeForBasicAuthHasNoIdentityWhenNotValid()
    {
        $httpAuth = new HttpAuth(array(
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ));
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic xxxxxxxxx');
        $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertNull($this->mvcAuthEvent->getIdentity());
    }

    public function testInvokeForDigestAuthAddsAuthorizationHeader()
    {
        $httpAuth = new HttpAuth(array(
            'accept_schemes' => 'digest',
            'realm' => 'User Area',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ));
        $httpAuth->setDigestResolver(new HttpAuth\FileResolver(__DIR__ . '/../TestAsset/htdigest'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->listener->__invoke($this->mvcAuthEvent);

        $authHeaders = $this->response->getHeaders()->get('WWW-Authenticate');
        $authHeader = $authHeaders[0];
        $this->assertInstanceOf('Zend\Http\Header\HeaderInterface', $authHeader);
        $this->assertRegexp(
            '#^Digest realm="User Area", domain="/", '
            . 'nonce="[a-f0-9]{32}", '
            . 'opaque="e66aa41ca5bf6992a5479102cc787bc9", '
            . 'algorithm="MD5", '
            . 'qop="auth"$#',
            $authHeader->getFieldValue()
        );
    }

    /**
     * @depends testInvokeForBasicAuthSetsIdentityWhenValid
     */
    public function testListenerInjectsDiscoveredIdentityIntoMvcEvent($params)
    {
        $identity = $params['identity'];
        $mvcEvent = $params['mvc_event'];

        $received = $mvcEvent->getParam('ZF\MvcAuth\Identity', false);
        $this->assertSame($identity, $received);
    }

    /**
     * @depends testInvokeForBasicAuthSetsGuestIdentityWhenValid
     */
    public function testListenerInjectsGuestIdentityIntoMvcEvent($params)
    {
        $identity = $params['identity'];
        $mvcEvent = $params['mvc_event'];

        $received = $mvcEvent->getParam('ZF\MvcAuth\Identity', false);
        $this->assertSame($identity, $received);
    }

    /**
     * @group 23
     */
    public function testListenerPullsDigestUsernameFromAuthenticationIdentityWhenCreatingAuthenticatedIdentityInstance()
    {
        $httpAuth = $this->getMockBuilder('Zend\Authentication\Adapter\Http')
            ->disableOriginalConstructor()
            ->getMock();
        $resultIdentity = new AuthenticationResult(AuthenticationResult::SUCCESS, array(
            'username' => 'user',
            'realm' => 'User Area',
        ));
        $httpAuth->expects($this->any())
            ->method('getBasicResolver')
            ->will($this->returnValue(false));
        $httpAuth->expects($this->any())
            ->method('getDigestResolver')
            ->will($this->returnValue(true));
        $httpAuth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($resultIdentity));

        $this->listener->setHttpAdapter($httpAuth);
        $this->request->getHeaders()->addHeaderLine(
            'Authorization: Digest username="user", '
            . 'realm="User Area", '
            . 'nonce="AB10BC99", '
            . 'uri="/", '
            . 'qop="auth", '
            . 'nc="AB10BC99", '
            . 'cnonce="AB10BC99", '
            . 'response="b19adb0300f4bd21baef59b0b4814898", '
            . 'opaque=""'
        );

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
        $this->assertEquals('user', $identity->getRoleId());
    }

    public function testBearerTypeProxiesOAuthServer()
    {
        $token = array(
            'user_id' => 'test',
        );

        $this->setupMockOAuth2Server($token);
        $this->request->getHeaders()->addHeaderLine('Authorization', 'Bearer TOKEN');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);

        $this->assertIdentityMatchesToken($token, $identity);
    }

    public function testQueryAccessTokenProxiesOAuthServer()
    {
        $token = array(
            'user_id' => 'test',
        );

        $this->setupMockOAuth2Server($token);
        $this->request->getQuery()->set('access_token', 'TOKEN');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);

        $this->assertIdentityMatchesToken($token, $identity);
    }

    public function requestMethodsWithRequestBodies()
    {
        return array(
            array('DELETE'),
            array('PATCH'),
            array('POST'),
            array('PUT'),
        );
    }

    /**
     * @dataProvider requestMethodsWithRequestBodies
     */
    public function testBodyAccessTokenProxiesOAuthServer($method)
    {
        $token = array(
            'user_id' => 'test',
        );

        $this->setupMockOAuth2Server($token);
        $this->request->setMethod($method);
        $this->request->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded');
        $this->request->getPost()->set('access_token', 'TOKEN');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);

        $this->assertIdentityMatchesToken($token, $identity);
    }

    protected function setupMockOAuth2Server($token)
    {
        $server = $this->getMockBuilder('OAuth2\Server')
            ->disableOriginalConstructor()
            ->getMock();
        $server->expects($this->atLeastOnce())
            ->method('verifyResourceRequest')
            ->will($this->returnValue(true));

        $server->expects($this->atLeastOnce())
            ->method('getAccessTokenData')
            ->will($this->returnValue($token));

        $this->listener->setOauth2Server($server);
    }

    public static function assertIdentityMatchesToken($token, $identity, $message = '')
    {
        self::assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity, $message);
        self::assertEquals($token['user_id'], $identity->getRoleId());
        self::assertEquals($token, $identity->getAuthenticationIdentity());

    }

    public function setupHttpBasicAuth()
    {
        $httpAuth = new HttpAuth(array(
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ));
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);
    }

    public function setupHttpDigestAuth()
    {
        $httpAuth = $this->getMockBuilder('Zend\Authentication\Adapter\Http')
            ->disableOriginalConstructor()
            ->getMock();
        $resultIdentity = new AuthenticationResult(AuthenticationResult::SUCCESS, array(
            'username' => 'user',
            'realm' => 'User Area',
        ));
        $httpAuth->expects($this->any())
            ->method('getDigestResolver')
            ->will($this->returnValue(true));
        $httpAuth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($resultIdentity));

        $this->listener->setHttpAdapter($httpAuth);
    }

    public function mappedAuthenticationControllers()
    {
        return array(
            'Foo\V2' => array(
                'Foo\V2\Rest\Status\StatusController',
                'oauth2',
                function () {
                    $request = new HttpRequest();
                    $request->getHeaders()->addHeaderLine('Authorization: Bearer TOKEN');
                    return $request;
                },
            ),
            'Bar\V1' => array(
                'Bar\V1\Rpc\Ping\PingController',
                'basic',
                function () {
                    $request = new HttpRequest();
                    $request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
                    return $request;
                },
            ),
            'Baz\V3' => array(
                'Baz\V3\Rest\User\UserController',
                'digest',
                function () {
                    $request = new HttpRequest();
                    $request->getHeaders()->addHeaderLine(
                        'Authorization: Digest username="user", '
                        . 'realm="User Area", '
                        . 'nonce="AB10BC99", '
                        . 'uri="/", '
                        . 'qop="auth", '
                        . 'nc="AB10BC99", '
                        . 'cnonce="AB10BC99", '
                        . 'response="b19adb0300f4bd21baef59b0b4814898", '
                        . 'opaque=""'
                    );
                    return $request;
                },
            ),
        );
    }

    public function setupMappedAuthenticatingListener($authType, $controller, $request)
    {
        switch ($authType) {
            case 'basic':
                $this->setupHttpBasicAuth();
                break;
            case 'digest':
                $this->setupHttpDigestAuth();
                break;
            case 'oauth2':
                $this->setupMockOAuth2Server(array(
                    'user_id' => 'test',
                ));
                break;
        }

        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);
        $routeMatch = new RouteMatch(array('controller' => $controller));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     */
    public function testAuthenticationUsesMapByToChooseAuthenticationMethod(
        $controller,
        $authType,
        $requestProvider
    ) {
        $this->setupMappedAuthenticatingListener($authType, $controller, $requestProvider());
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     */
    public function testGuestIdentityIsReturnedWhenNoAuthSchemesArePresent(
        $controller,
        $authType,
        $requestProvider
    ) {
        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);
        $routeMatch = new RouteMatch(array('controller' => $controller));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($requestProvider())
            ->setRouteMatch($routeMatch);
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $identity);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     */
    public function testUsesDefaultAuthenticationWhenNoAuthMapIsPresent(
        $controller,
        $authType,
        $requestProvider
    ) {
        switch ($authType) {
            case 'basic':
                $this->setupHttpBasicAuth();
                break;
            case 'digest':
                $this->setupHttpDigestAuth();
                break;
            case 'oauth2':
                $this->setupMockOAuth2Server(array(
                    'user_id' => 'test',
                ));
                break;
        }

        $routeMatch = new RouteMatch(array('controller' => $controller));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($requestProvider())
            ->setRouteMatch($routeMatch);
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     */
    public function testDoesNotPerformAuthenticationWhenNoAuthMapPresentAndMultipleAuthSchemesAreDefined(
        $controller,
        $authType,
        $requestProvider
    ) {
        $this->setupHttpBasicAuth();
        // Minimal OAuth2 server mock, as we are not expecting any method calls
        $server = $this->getMockBuilder('OAuth2\Server')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setOauth2Server($server);

        $routeMatch = new RouteMatch(array('controller' => $controller));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($requestProvider())
            ->setRouteMatch($routeMatch);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $identity);
    }

    /**
     * @group 55
     */
    public function testDoesNotPerformAuthenticationWhenMatchedControllerHasNoAuthMapEntryAndAuthSchemesAreDefined()
    {
        // Minimal HTTP adapter mock, as we are not expecting any method calls
        $httpAuth = $this->getMockBuilder('Zend\Authentication\Adapter\Http')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setHttpAdapter($httpAuth);

        // Minimal OAuth2 server mock, as we are not expecting any method calls
        $server = $this->getMockBuilder('OAuth2\Server')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setOauth2Server($server);

        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);

        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Authorization: Bearer TOKEN');

        $routeMatch = new RouteMatch(array('controller' => 'FooBarBaz\V4\Rest\Test\TestController'));

        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $identity);
    }

    /**
     * @group 55
     */
    public function testDoesNotPerformAuthenticationWhenMatchedControllerHasAuthMapEntryNotInDefinedAuthSchemes()
    {
        // Minimal HTTP adapter mock, as we are not expecting any method calls
        $httpAuth = $this->getMockBuilder('Zend\Authentication\Adapter\Http')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setHttpAdapter($httpAuth);

        // No OAuth2 server, intentionally

        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);

        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Authorization: Bearer TOKEN');

        $routeMatch = new RouteMatch(array('controller' => 'Foo\V2\Rest\Test\TestController'));

        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $identity);
    }

    public function testAllowsAttachingAdapters()
    {
        $types = array('foo');
        $adapter = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $this->listener->attach($adapter);
    }

    public function testCanRetrieveSupportedAuthenticationTypes()
    {
        $types = array('foo');
        $adapter = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $this->listener->attach($adapter);
        $this->assertEquals($types, $this->listener->getAuthenticationTypes());
    }

    public function testAdapterPreAuthIsTriggeredWhenNoTypeMatchedInRequest()
    {
        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = new RouteMatch(array('controller' => 'Foo\V1\Rest\Test\TestController'));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types = array('foo');
        $adapter = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter->expects($this->once())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue(false));
        $adapter->expects($this->once())
            ->method('preAuth')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue(null));

        $this->listener->attach($adapter);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $identity);
    }

    public function testMatchedAdapterIsAuthenticatedAgainst()
    {
        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = new RouteMatch(array('controller' => 'Foo\V2\Rest\Test\TestController'));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types = array('oauth2');
        $adapter = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('oauth2'));
        $adapter->expects($this->any())
            ->method('matches')
            ->with($this->equalTo('oauth2'))
            ->will($this->returnValue(true));
        $expected = $this->getMockBuilder('ZF\MvcAuth\Identity\AuthenticatedIdentity')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue($expected));
        $this->listener->attach($adapter);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertSame($expected, $identity);
    }

    public function testFirstAdapterProvidingTypeIsAuthenticatedAgainst()
    {
        $map = array(
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        );
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = new RouteMatch(array('controller' => 'Foo\V2\Rest\Test\TestController'));
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types = array('oauth2');
        $adapter1 = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter1->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter1->expects($this->any())
            ->method('matches')
            ->with($this->equalTo('oauth2'))
            ->will($this->returnValue(true));
        $adapter1->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('oauth2'));
        $expected = $this->getMockBuilder('ZF\MvcAuth\Identity\AuthenticatedIdentity')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter1->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue($expected));

        $adapter2 = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter2->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter2->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('oauth2'));

        $this->listener->attach($adapter1);
        $this->listener->attach($adapter2);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertSame($expected, $identity);
    }

    public function testListsProvidedNonAdapterAuthenticationTypes()
    {
        $types = array('foo');
        $this->listener->addAuthenticationTypes($types);
        $this->assertEquals($types, $this->listener->getAuthenticationTypes());
    }

    public function testListsCombinedAuthenticationTypes()
    {
        $types = array('foo');
        $customTypes = array('bar');
        $this->listener->addAuthenticationTypes($customTypes);

        $adapter = $this->getMockBuilder('ZF\MvcAuth\Authentication\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $this->listener->attach($adapter);

        // Order of merge matters, unfortunately
        $this->assertEquals(array_merge($customTypes, $types), $this->listener->getAuthenticationTypes());
    }

    public function testOauth2RequestIncludesHeaders()
    {
        $this->request->getHeaders()->addHeaderLine('Authorization', 'Bearer TOKEN');

        $server = $this->getMockBuilder('OAuth2\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $server->expects($this->atLeastOnce())
            ->method('verifyResourceRequest')
            ->with($this->callback(function (OAuth2Request $request) {
                return $request->headers('Authorization') === 'Bearer TOKEN';
            }));

        $this->listener->attach(new OAuth2Adapter($server));
        $this->listener->__invoke($this->mvcAuthEvent);
    }
}
