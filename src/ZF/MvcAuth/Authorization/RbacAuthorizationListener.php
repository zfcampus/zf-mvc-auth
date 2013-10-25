<?php

namespace ZF\MvcAuth\Authorization;

use Zend\Permissions\Rbac\Rbac;
use ZF\MvcAuth\MvcAuthEvent;

class RbacAuthorizationListener
{
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        // var_dump($mvcAuthEvent->getAuthenticationService()->getIdentity());
        // $mvcAuthEvent->getMvcEvent()->stopPropagation(true);

//        $rbac = new Rbac();
//        $controllers = $mvcAuthEvent->getMvcEvent()->getApplication()->getServiceManager()->get('controllerpluginmanager');
        // var_dump($controllers);

    }
}
