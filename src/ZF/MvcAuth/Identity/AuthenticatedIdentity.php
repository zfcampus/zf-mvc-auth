<?php

namespace ZF\MvcAuth\Identity;

use Zend\Permissions\Rbac\AbstractRole as AbstractRbacRole;

class AuthenticatedIdentity extends AbstractRbacRole implements IdentityInterface
{
    protected $identity;

    public function __construct($identity)
    {
        $this->identity = $identity;
    }

    public function getRoleId()
    {
        return $this->name;
    }

    public function getAuthenticationIdentity()
    {
        return $this->identity;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
