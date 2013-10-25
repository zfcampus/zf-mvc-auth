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
                'realm' => 'My Web Site',
                'digest_domains' => '/members_only /my_account',
                'nonce_timeout' => 3600,
                'file' => APPLICATION_PATH . '/data/htpasswd'
            )
        ),
        'authorization' => array(
        )
    )
);