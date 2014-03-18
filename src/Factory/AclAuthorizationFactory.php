<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Http\Request;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authorization\AclAuthorizationFactory as AclFactory;

/**
 * Factory for creating an AclAuthorization instance from configuration
 */
class AclAuthorizationFactory implements FactoryInterface
{
    /**
     * @var array
     */
    protected $httpMethods = array(
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    );

    /**
     * Create the DefaultAuthorizationListener
     *
     * @param ServiceLocatorInterface $services
     * @return \ZF\MvcAuth\Authorization\AuthorizationInterface
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = array();
        if ($services->has('config')) {
            $config = $services->get('config');
        }

        return $this->createAclFromConfig($config);
    }

    /**
     * Generate the ACL instance based on the zf-mvc-auth "authorization" configuration
     *
     * Consumes the AclFactory in order to create the AclAuthorization instance.
     *
     * @param array $config
     * @return \ZF\MvcAuth\Authorization\AclAuthorization
     */
    protected function createAclFromConfig(array $config)
    {
        $aclConfig = array();

        if (isset($config['zf-mvc-auth'])
            && isset($config['zf-mvc-auth']['authorization'])
        ) {
            $config = $config['zf-mvc-auth']['authorization'];

            if (array_key_exists('deny_by_default', $config)) {
                $aclConfig['deny_by_default'] = (bool) $config['deny_by_default'];
                unset($config['deny_by_default']);
            }

            foreach ($config as $controllerService => $privileges) {
                $this->createAclConfigFromPrivileges($controllerService, $privileges, $aclConfig);
            }
        }

        return AclFactory::factory($aclConfig);
    }

    /**
     * Creates ACL configuration based on the privileges configured
     *
     * - Extracts a privilege per action
     * - Extracts privileges for each of "collection" and "entity" configured
     *
     * @param string $controllerService
     * @param array $privileges
     * @param array $aclConfig
     */
    protected function createAclConfigFromPrivileges($controllerService, array $privileges, &$aclConfig)
    {
        if (isset($privileges['actions'])) {
            foreach ($privileges['actions'] as $action => $methods) {
                $aclConfig[] = array(
                    'resource'   => sprintf('%s::%s', $controllerService, $action),
                    'privileges' => $this->createPrivilegesFromMethods($methods),
                );
            }
        }

        if (isset($privileges['collection'])) {
            $aclConfig[] = array(
                'resource'   => sprintf('%s::collection', $controllerService),
                'privileges' => $this->createPrivilegesFromMethods($privileges['collection']),
            );
        }

        if (isset($privileges['entity'])) {
            $aclConfig[] = array(
                'resource'   => sprintf('%s::entity', $controllerService),
                'privileges' => $this->createPrivilegesFromMethods($privileges['entity']),
            );
        }
    }

    /**
     * Create the list of HTTP methods defining privileges
     *
     * @param array $methods
     * @return array|null
     */
    protected function createPrivilegesFromMethods(array $methods)
    {
        $privileges = array();

        if (isset($methods['default']) && $methods['default']) {
            $privileges = $this->httpMethods;
            unset($methods['default']);
        }

        foreach ($methods as $method => $flag) {
            if (!$flag) {
                if (isset($privileges[$method])) {
                    unset($privileges[$method]);
                }
                continue;
            }
            $privileges[$method] = true;
        }

        if (empty($privileges)) {
            return null;
        }

        return array_keys($privileges);
    }
}
