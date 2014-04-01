<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authentication;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Request;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
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
        $this->assertRegexp('#^Digest realm="User Area", domain="/", nonce="[a-f0-9]{32}", opaque="e66aa41ca5bf6992a5479102cc787bc9", algorithm="MD5", qop="auth"$#', $authHeader->getFieldValue());
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
        $httpAuth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($resultIdentity));

        $this->listener->setHttpAdapter($httpAuth);
        $this->request->getHeaders()->addHeaderLine('Authorization: Digest username="user", realm="User Area", nonce="AB10BC99", uri="/", qop="auth", nc="AB10BC99", cnonce="AB10BC99", response="b19adb0300f4bd21baef59b0b4814898", opaque=""');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
        $this->assertEquals('user', $identity->getRoleId());
    }

    public function testListenerInjectsOAuthIdentityIntoAuthenticatedIdentityOnSuccess()
    {
        $server = $this->getMockBuilder('OAuth2\Server')
            ->disableOriginalConstructor()
            ->getMock();
        $server->expects($this->any())
            ->method('verifyResourceRequest')
            ->will($this->returnValue(true));
        $token = array(
            'user_id' => 'test',
        );
        $server->expects($this->any())
            ->method('getAccessTokenData')
            ->will($this->returnValue($token));
        $this->listener->setOauth2Server($server);


        $this->request->getHeaders()->addHeaderLine('Authorization: Bearer this-is-the-token');
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $identity);
        $this->assertEquals($token['user_id'], $identity->getRoleId());
        $this->assertEquals($token, $identity->getAuthenticationIdentity());
    }
}
