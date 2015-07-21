<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authentication;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use ZF\MvcAuth\Authentication\HttpAdapter;
use ZF\MvcAuth\MvcAuthEvent;

class HttpAdapterTest extends TestCase
{
    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService(new NonPersistent());

        $this->request  = $request  = new HttpRequest();
        $this->response = $response = new HttpResponse();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response);

        $this->event = new MvcAuthEvent(
            $mvcEvent,
            $this->authentication,
            $this->getMock('ZF\MvcAuth\Authorization\AuthorizationInterface')
        );
    }

    public function testAuthenticateReturnsGuestIdentityIfNoAuthorizationHeaderProvided()
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));

        $adapter = new HttpAdapter($httpAuth, $this->authentication);
        $result  = $adapter->authenticate($this->request, $this->response, $this->event);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $result);
    }

    public function testAuthenticateReturnsFalseIfInvalidCredentialsProvidedInAuthorizationHeader()
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));

        $adapter = new HttpAdapter($httpAuth, $this->authentication);

        $this->request->getHeaders()->addHeaderLine('Authorization', 'Bearer BOGUS TOKEN');

        $this->assertFalse($adapter->authenticate($this->request, $this->response, $this->event));
    }

    public function testAuthenticateReturnsAuthenticatedIdentityIfValidCredentialsProvidedInAuthorizationHeader()
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm' => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout' => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));

        $adapter = new HttpAdapter($httpAuth, $this->authentication);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
        $result  = $adapter->authenticate($this->request, $this->response, $this->event);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\AuthenticatedIdentity', $result);
    }
}
