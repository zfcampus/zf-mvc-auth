<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Authentication;

use ArrayIterator;
use OAuth2\Request as OAuth2Request;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use ZF\MvcAuth\Authentication\OAuth2Adapter;

class OAuth2AdapterTest extends TestCase
{
    public function setUp()
    {
        $this->oauthServer = $this->getMock('OAuth2\Server');
        $this->adapter = new OAuth2Adapter($this->oauthServer);
    }

    /**
     * @group 83
     */
    public function testReturns401ResponseWhenErrorOccursDuringValidation()
    {
        $oauth2Response = $this->getMockBuilder('OAuth2\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);
        $oauth2Response
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('error'))
            ->willReturn('invalid');
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn([]);

        $this->oauthServer
            ->expects($this->once())
            ->method('verifyResourceRequest')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(false);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder('ZF\MvcAuth\MvcAuthEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf('Zend\Http\Response', $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    /**
     * @group 83
     */
    public function testReturns403ResponseWhenInvalidScopeDetected()
    {
        $oauth2Response = $this->getMockBuilder('OAuth2\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(403);
        $oauth2Response
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('error'))
            ->willReturn('invalid');
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn([]);

        $this->oauthServer
            ->expects($this->once())
            ->method('verifyResourceRequest')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(false);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder('ZF\MvcAuth\MvcAuthEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf('Zend\Http\Response', $result);
        $this->assertEquals(403, $result->getStatusCode());
    }

    /**
     * @group 83
     */
    public function testReturnsGuestIdentityIfOAuth2ResponseIsNotAnError()
    {
        $oauth2Response = $this->getMockBuilder('OAuth2\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn([]);

        $this->oauthServer
            ->expects($this->once())
            ->method('verifyResourceRequest')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(false);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder('ZF\MvcAuth\MvcAuthEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf('ZF\MvcAuth\Identity\GuestIdentity', $result);
    }

    /**
     * @group 83
     */
    public function testErrorResponseIncludesOAuth2ResponseHeaders()
    {
        $expectedHeaders = [
            'WWW-Authenticate' => 'Bearer realm="example.com", '
            . 'scope="user", '
            . 'error="unauthorized", '
            . 'error_description="User has insufficient privileges"',
        ];
        $oauth2Response = $this->getMockBuilder('OAuth2\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);
        $oauth2Response
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('error'))
            ->willReturn('invalid');
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn($expectedHeaders);

        $this->oauthServer
            ->expects($this->once())
            ->method('verifyResourceRequest')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(false);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder('ZF\MvcAuth\MvcAuthEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf('Zend\Http\Response', $result);

        $headers = $result->getHeaders();
        foreach ($expectedHeaders as $name => $value) {
            $this->assertTrue($headers->has($name));
            $header = $headers->get($name);
            if ($header instanceof ArrayIterator) {
                $found = false;
                foreach ($header as $instance) {
                    if ($instance->getFieldValue() == $value) {
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, 'Expected header value not found');
                continue;
            }

            $this->assertEquals($value, $header->getFieldValue());
        }
    }
}
