<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'authentication' => 'ZF\MvcAuth\AuthenticationServiceFactory'
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'ZF\MvcAuth\Auth' => 'ZF\MvcAuth\AuthController',
        ),
    ),
    'zf-mvc-auth' => array(
        'controller' => 'ZF\MvcAuth\Auth',
        'authentication' => array(
            'storage' => 'non-persistent',
            'first' => array(
                'type' => 'service',
                'service' => 'password-callback-service'
            )
        ),
        'authorization' => array(
        )
    )
);