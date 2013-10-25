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
            'basic' => array(
                'accept_schemes' => 'basic',
                'realm' => 'Test',
                'file' => APPLICATION_PATH . '/data/htpasswd'
            ),
            'digest' => array(
                'digest_domains' => '/',
                'accept_schemes' => 'digest',
                'nonce_timeout' => 3600,
                'realm' => 'Test',
                'file' => APPLICATION_PATH . '/data/htdigest'    
            )
        ),
        'authorization' => array(
        )
    )
);
