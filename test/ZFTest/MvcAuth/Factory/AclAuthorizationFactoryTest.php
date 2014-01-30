<?php
/**
 * AclAuthorizationFactoryTest
 *
 * @category  AcsiApiTest\MvcAuth\Factory
 * @package   AcsiApiTest\MvcAuth\Factory
 * @copyright 2014 ACSI Holding bv (http://www.acsi.eu)
 * @version   SVN: $Id$
 */
namespace AcsiApiTest\MvcAuth\Factory;

use Zf\MvcAuth\Factory\AclAuthorizationFactory;
use ZF\MvcAuth\Authorization\AclAuthorization;

class AclAuthorizationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var  AclAuthorizationFactory */
    protected $factory;

    public function setUp()
    {
        $this->factory = new AclAuthorizationFactory();
    }

    public function testIfServiceCorrectlyCreated()
    {
        $config = array(
            'zf-mvc-auth' => array(
                'authorization' => array(
                    'deny_by_default' => true,
                    'guest' => array(
                        'TestController' => array(
                            'resource' => array(
                                'default' => false,
                                'GET' => true
                            ),
                            'collection' => array(
                                'default' => false,
                            ),
                            'actions' => array(
                                'index' => array(
                                    'default' => false,
                                    'GET' => true
                                ),
                            ),
                        ),
                        'TestController2' => array(
                            'resource' => array(
                                'default' => true,
                            ),
                            'actions' => array(
                                'index' => array(
                                    'default' => false,
                                ),
                            ),
                        ),
                    ),
                    'admin' => array(
                        'TestController' => array(
                            'resource' => array(
                                'default' => true,
                            ),
                            'actions' => array(
                                'index' => array(
                                    'default' => false,
                                    'GET' => true
                                ),
                            ),
                        ),
                        'TestController2' => array(
                            'resource' => array(
                                'default' => true,
                            ),
                            'actions' => array(
                                'index' => array(
                                    'default' => true,
                                ),
                            ),
                        ),
                    ),
                )
            )
        );

        $serviceManager = \Mockery::mock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceManager->shouldReceive('has')->with('config')->andReturn(true)->getMock();
        $serviceManager->shouldReceive('get')->with('config')->andReturn($config)->getMock();

        /** @var AclAuthorization $acl */
        $acl = $this->factory->createService($serviceManager);

        $this->assertTrue($acl->isAllowed('guest', 'TestController::index', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'TestController::resource', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController::collection', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController2::index', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'TestController2::resource', 'GET'));

        $this->assertTrue($acl->isAllowed('admin', 'TestController::index', 'GET'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController::resource', 'GET'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController::index', 'POST'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController::collection', 'GET'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController2::index', 'GET'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController2::resource', 'GET'));
    }
}
