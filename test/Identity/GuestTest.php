<?php

namespace ZFTest\MvcAuth\Identity;

use PHPUnit\Framework\TestCase;
use Zend\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Zend\Permissions\Rbac\RoleInterface as RbacRoleInterface;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\Identity\IdentityInterface;

class GuestIdentityTest extends TestCase
{
    public function setUp()
    {
        $this->identity = new GuestIdentity();
    }

    public function testGuestIsAnIdentityType()
    {
        $this->assertInstanceOf(IdentityInterface::class, $this->identity);
    }

    public function testGuestImplementsAclRole()
    {
        $this->assertInstanceOf(AclRoleInterface::class, $this->identity);
    }

    public function testGuestImplementsRbacRole()
    {
        $this->assertInstanceOf(RbacRoleInterface::class, $this->identity);
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
