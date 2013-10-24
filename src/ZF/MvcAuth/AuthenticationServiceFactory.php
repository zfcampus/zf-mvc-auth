<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ralphschindler
 * Date: 10/23/13
 * Time: 5:23 PM
 * To change this template use File | Settings | File Templates.
 */

namespace ZF\MvcAuth;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;



class AuthenticationServiceFactory implements FactoryInterface
{

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new AuthenticationService(new NonPersistent());
    }
}