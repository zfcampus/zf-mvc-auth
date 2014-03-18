<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Identity;

use Zend\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Zend\Permissions\Rbac\RoleInterface as RbacRoleInterface;

interface IdentityInterface extends
    AclRoleInterface,
    RbacRoleInterface
{
    public function getAuthenticationIdentity();
}
