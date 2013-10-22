<?php
/**
 * Created by PhpStorm.
 * User: ralphschindler
 * Date: 10/22/13
 * Time: 5:07 PM
 */

namespace ZF\MvcAuth;

use Zend\EventManager\Event;
use Zend\Mvc\MvcEvent;

class MvcAuthEvent extends Event
{
    const EVENT_AUTHENTICATION = 'authentication';
    const EVENT_AUTHORIZATION = 'authorization';

    protected $mvcEvent;

    public function __construct(MvcEvent $mvcEvent)
    {
        $this->mvcEvent = $mvcEvent;
    }

    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

} 