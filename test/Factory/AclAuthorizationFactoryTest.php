<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Factory\AclAuthorizationFactory;

class AclAuthorizationFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new AclAuthorizationFactory();
    }

    public function testCreatingOAuth2ServerFromStorageService()
    {
        $config = array('zf-mvc-auth' => array('authorization' => array(
            'Foo\Bar\RestController' => array(
                'entity' => array(
                    'GET'    => false,
                    'POST'   => false,
                    'PUT'    => true,
                    'PATCH'  => true,
                    'DELETE' => true,
                ),
                'collection' => array(
                    'GET'    => false,
                    'POST'   => true,
                    'PUT'    => false,
                    'PATCH'  => false,
                    'DELETE' => false,
                ),
            ),
            'Foo\Bar\RpcController' => array(
                'actions' => array(
                    'do' => array(
                        'GET'    => false,
                        'POST'   => true,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ),
                ),
            ),
        )));
        $this->services->setService('config', $config);

        $acl = $this->factory->createService($this->services);

        $this->assertInstanceOf('ZF\MvcAuth\Authorization\AclAuthorization', $acl);

        $authorizations = $config['zf-mvc-auth']['authorization'];

        foreach ($authorizations as $resource => $rules) {
            switch (true) {
                case (array_key_exists('entity', $rules)):
                    foreach ($rules['entity'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::entity', $method));
                    }
                case (array_key_exists('collection', $rules)):
                    foreach ($rules['collection'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::collection', $method));
                    }
                    break;
                case (array_key_exists('actions', $rules)):
                    foreach ($rules['actions'] as $action => $actionRules) {
                        foreach ($actionRules as $method => $expected) {
                            $assertion = 'assert' . ($expected ? 'False' : 'True');
                            $this->$assertion($acl->isAllowed('guest', $resource . '::' . $action, $method));
                        }
                    }
                    break;
            }
        }
    }
}
