<?php

namespace ZFTest\MvcAuth\Identity;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\MvcAuth\Identity\GuestIdentity;

class GuestIdentityTest extends TestCase
{
    public function setUp()
    {
        $this->identity = new GuestIdentity();
    }

    public function testGuestIsAnIdentityType()
    {
        $this->assertInstanceOf('ZF\MvcAuth\Identity\IdentityInterface', $this->identity);
    }

    public function testGuestImplementsAclRole()
    {
        $this->assertInstanceOf('Zend\Permissions\Acl\Role\RoleInterface', $this->identity);
    }

    public function testGuestImplementsRbacRole()
    {
        $this->assertInstanceOf('Zend\Permissions\Rbac\RoleInterface', $this->identity);
    }

    public function testGuestRoleIdIsGuest()
    {
        $this->assertEquals('guest', $this->identity->getRoleId());
    }

    public function testGuestRoleNameIsGuest()
    {
        $this->assertEquals('guest', $this->identity->getName());
    }

    public function testGuestDoesNotComposeAuthenticationIdentity()
    {
        $this->assertNull($this->identity->getAuthenticationIdentity());
    }
}
