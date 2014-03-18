<?php

namespace ZFTest\MvcAuth\Identity;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\MvcAuth\Identity\AuthenticatedIdentity;

class AuthenticatedIdentityTest extends TestCase
{
    public function setUp()
    {
        $this->authIdentity = (object) array(
            'name' => 'foo',
        );
        $this->identity = new AuthenticatedIdentity($this->authIdentity);
    }

    public function testAuthenticatedIsAnIdentityType()
    {
        $this->assertInstanceOf('ZF\MvcAuth\Identity\IdentityInterface', $this->identity);
    }

    public function testAuthenticatedImplementsAclRole()
    {
        $this->assertInstanceOf('Zend\Permissions\Acl\Role\RoleInterface', $this->identity);
    }

    public function testAuthenticatedImplementsRbacRole()
    {
        $this->assertInstanceOf('Zend\Permissions\Rbac\RoleInterface', $this->identity);
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
