<?php

namespace ZF\MvcAuth\Identity;

use Zend\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Zend\Permissions\Rbac\RoleInterface as RbacRoleInterface;

interface IdentityInterface extends
    AclRoleInterface,
    RbacRoleInterface
{
    public function getAuthenticationIdentity();
}
