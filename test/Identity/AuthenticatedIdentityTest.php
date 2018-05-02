<?php

namespace ZFTest\MvcAuth\Identity;

use PHPUnit\Framework\TestCase;
use Zend\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Zend\Permissions\Rbac\RoleInterface as RbacRoleInterface;
use ZF\MvcAuth\Identity\AuthenticatedIdentity;
use ZF\MvcAuth\Identity\IdentityInterface;

class AuthenticatedIdentityTest extends TestCase
{
    public function setUp()
    {
        $this->authIdentity = (object) [
            'name' => 'foo',
        ];
        $this->identity = new AuthenticatedIdentity($this->authIdentity);
    }

    public function testAuthenticatedIsAnIdentityType()
    {
        $this->assertInstanceOf(IdentityInterface::class, $this->identity);
    }

    public function testAuthenticatedImplementsAclRole()
    {
        $this->assertInstanceOf(AclRoleInterface::class, $this->identity);
    }

    public function testAuthenticatedImplementsRbacRole()
    {
        $this->assertInstanceOf(RbacRoleInterface::class, $this->identity);
    }

    public function testAuthenticatedAllowsSettingName()
    {
        $this->identity->setName($this->authIdentity->name);
        $this->assertEquals($this->authIdentity->name, $this->identity->getName());
        $this->assertEquals($this->authIdentity->name, $this->identity->getRoleId());
    }

    public function testAuthenticatedComposesAuthenticatedIdentity()
    {
        $this->assertSame($this->authIdentity, $this->identity->getAuthenticationIdentity());
    }
}
