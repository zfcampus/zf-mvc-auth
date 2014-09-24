<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Identity;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\InjectApplicationEventInterface;

class IdentityPlugin extends AbstractPlugin
{
    public function __invoke()
    {
        $controller = $this->getController();
        if (! $controller instanceof InjectApplicationEventInterface) {
            return new GuestIdentity();
        }

        $event    = $controller->getEvent();
        $identity = $event->getParam(__NAMESPACE__);

        if (! $identity instanceof IdentityInterface) {
            return new GuestIdentity();
        }

        return $identity;
    }
}
