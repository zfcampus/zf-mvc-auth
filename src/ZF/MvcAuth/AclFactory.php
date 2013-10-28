<?php

namespace ZF\MvcAuth;

use Zend\Permissions\Acl\Acl;

abstract class AclFactory
{
    public static function factory(array $config)
    {
        // By default, create an open ACL
        $acl = new Acl;
        $acl->addRole('guest');
        $acl->allow();

        foreach ($config as $set) {
            if (!isset($set['resource'])) {
                continue;
            }

            // Add new resource to ACL
            $resource = $set['resource'];
            $acl->addResource($set['resource']);

            // Deny guest specified privileges to resource
            $rights   = isset($set['rights']) ? $set['rights'] : null;
            $acl->deny('guest', $resource, $rights);
        }

        return $acl;
    }
}
