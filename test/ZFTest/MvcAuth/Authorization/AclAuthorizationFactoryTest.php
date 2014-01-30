<?php

namespace ZFTest\MvcAuth\Authorization;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\MvcAuth\Authorization\AclAuthorization;
use ZF\MvcAuth\Authorization\AclAuthorizationFactory;

class AclAuthorizationFactoryTest extends TestCase
{
    public function testIfRolesAreGrantedPrivilegesByDenyByDefault()
    {
        $aclConfig = array(
            'deny_by_default' => true,
            0 => array(
                'resource' => 'TestController::index',
                'privileges' => array(
                    'GET',
                ),
                'role' => 'guest'
            ),
            1 => array(
                'resource' => 'TestController::index',
                'privileges' => array(
                    'GET',
                    'POST',
                ),
                'role' => 'admin'
            ),
            2 => array(
                'resource' => 'TestController2::index',
                'role' => 'guest'
            ),
            3 => array(
                'resource' => 'TestController2::index',
                'privileges' => array(
                    'GET',
                ),
                'role' => 'admin'
            ),
            4 => array(
                'resource' => 'TestController3::index',
                'privileges' => array(
                    'GET',
                ),
            ),
        );

        /** @var AclAuthorization $acl */
        $acl = AclAuthorizationFactory::factory($aclConfig);

        // Test all configured privileges
        $this->assertTrue($acl->isAllowed('guest', 'TestController::index', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController::index', 'POST'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController2::index', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController2::index', 'POST'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController3::index', 'GET'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController::index', 'GET'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController::index', 'POST'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController2::index', 'GET'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController3::index', 'GET'));

        // Test if not configured privileges are denied
        $this->assertFalse($acl->isAllowed('guest', 'TestController::index', 'PUT'));
        $this->assertFalse($acl->isAllowed('guest', 'TestController::index', 'PATCH'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController::index', 'PATCH'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController::index', 'PUT'));
    }

    public function testIfRolesAreGrantedPrivilegesByAllowByDefault()
    {
        $aclConfig = array(
            'deny_by_default' => false,
            0 => array(
                'resource' => 'TestController::index',
                'privileges' => array(
                    'GET',
                ),
                'role' => 'guest'
            ),
            1 => array(
                'resource' => 'TestController::index',
                'privileges' => array(
                    'GET',
                    'POST',
                ),
                'role' => 'admin'
            ),
            2 => array(
                'resource' => 'TestController2::index',
                'role' => 'guest'
            ),
            3 => array(
                'resource' => 'TestController2::index',
                'privileges' => array(
                    'GET',
                ),
                'role' => 'admin'
            ),
            4 => array(
                'resource' => 'TestController3::index',
                'privileges' => array(
                    'GET',
                ),
            ),
        );

        /** @var AclAuthorization $acl */
        $acl = AclAuthorizationFactory::factory($aclConfig);

        // Test all configured privileges
        $this->assertFalse($acl->isAllowed('guest', 'TestController::index', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'TestController::index', 'POST'));
        $this->assertTrue($acl->isAllowed('guest', 'TestController2::index', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'TestController2::index', 'POST'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController::index', 'GET'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController::index', 'POST'));
        $this->assertFalse($acl->isAllowed('admin', 'TestController2::index', 'GET'));

        // Test if not configured privileges are denied
        $this->assertTrue($acl->isAllowed('guest', 'TestController::index', 'PUT'));
        $this->assertTrue($acl->isAllowed('guest', 'TestController::index', 'PATCH'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController::index', 'PATCH'));
        $this->assertTrue($acl->isAllowed('admin', 'TestController::index', 'PUT'));
    }

    public function testIfRuleIgnoredWhenNoResourceSpecified()
    {
        $aclConfig = array(
            'deny_by_default' => false,
            0 => array(
                'privileges' => array(
                    'GET',
                ),
                'role' => 'guest'
            ),
        );

        /** @var AclAuthorization $acl */
        $acl = AclAuthorizationFactory::factory($aclConfig);
        $this->assertCount(0, $acl->getResources());
        $this->assertCount(1, $acl->getRoles());
    }
}
