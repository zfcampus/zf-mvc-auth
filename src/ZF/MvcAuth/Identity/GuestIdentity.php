<?php

namespace ZF\MvcAuth\Identity;

use Zend\Permissions\Rbac\AbstractRole as AbstractRbacRole;

class GuestIdentity extends AbstractRbacRole implements IdentityInterface
{
    protected static $identity = 'guest';

    public function __construct()
    {
        $this->name = static::$identity;
    }

    public function getRoleId()
    {
        return static::$identity;
    }

    public function getAuthenticationIdentity()
    {
        return null;
    }
}
