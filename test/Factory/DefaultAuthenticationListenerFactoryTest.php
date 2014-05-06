<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Authentication\DefaultAuthenticationListener;
use ZF\MvcAuth\Factory\DefaultAuthenticationListenerFactory;

class DefaultAuthenticationListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('ZF\MvcAuth\Authentication\AuthHttpAdapter', 'ZF\MvcAuth\Factory\DefaultAuthHttpAdapterFactory');
        $this->factory  = new DefaultAuthenticationListenerFactory();
    }

    public function testCreatingOAuth2ServerFromStorageService()
    {
        $adapter = $this->getMockBuilder('OAuth2\Storage\Pdo')->disableOriginalConstructor()->getMock();

        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', array(
            'zf-oauth2' => array(
                'storage' => 'TestAdapter'
            )
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithNoConfigServiceReturnsListenerWithNoHttpAdapter()
    {
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingMvcAuthSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array());
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAuthenticationSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array('zf-mvc-auth' => array()));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingHttpSubSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array('zf-mvc-auth' => array('authentication' => array())));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAcceptSchemesRaisesException()
    {
        $this->services->setService('config', array('zf-mvc-auth' => array('authentication' => array('http' => array()))));
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException');
        $listener = $this->factory->createService($this->services);
    }

    public function testCallingFactoryWithBasicSchemeButMissingHtpasswdValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'test',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithDigestSchemeButMissingHtdigestValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'test',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithBasicSchemeAndHtpasswdValueReturnsListenerWithHttpAdapter()
    {
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/../TestAsset/htpasswd'
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithDigestSchemeAndHtdigestValueReturnsListenerWithHttpAdapter()
    {
        $this->services->setService('config', array(
            'zf-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeInstanceOf('Zend\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }
}
