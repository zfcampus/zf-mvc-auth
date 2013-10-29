<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
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
     * @return DefaultAuthorizationListener
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
     * Generate the ACL instance based on the zf-mc-auth "rules" configuration
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
            && isset($config['zf-mvc-auth']['deny_by_default'])
            && $config['zf-mvc-auth']['deny_by_default']
        ) {
            $aclConfig['deny_by_default'] = true;
        }

        if (isset($config['zf-mvc-auth'])
            && isset($config['zf-mvc-auth']['rules'])
        ) {
            $rulesConfig = $config['zf-mvc-auth']['rules'];
            foreach ($rulesConfig as $controllerService => $rules) {
                $this->createAclConfigFromRules($controllerService, $rules, $aclConfig);
            }
        }

        return AclFactory::factory($aclConfig);
    }

    /**
     * Creates ACL configuration based on the rules configured
     *
     * - Extracts a rule per action
     * - Extracts rules for each of "collection" and "resource" configured
     *
     * @param string $controllerService
     * @param array $rules
     * @param array $aclConfig
     */
    protected function createAclConfigFromRules($controllerService, array $rules, &$aclConfig)
    {
        if (isset($rules['actions'])) {
            foreach ($rules['actions'] as $action => $methods) {
                $aclConfig[] = array(
                    'resource' => sprintf('%s::%s', $controllerService, $action),
                    'rights'   => $this->createRightsFromMethods($methods),
                );
            }
        }

        if (isset($rules['collection'])) {
            $aclConfig[] = array(
                'resource' => sprintf('%s::collection', $controllerService),
                'rights'   => $this->createRightsFromMethods($rules['collection']),
            );
        }

        if (isset($rules['resource'])) {
            $aclConfig[] = array(
                'resource' => sprintf('%s::resource', $controllerService),
                'rights'   => $this->createRightsFromMethods($rules['resource']),
            );
        }
    }

    /**
     * Create the list of HTTP methods defining rights
     *
     * @param array $methods
     * @return array|null
     */
    protected function createRightsFromMethods(array $methods)
    {
        $rights = array();

        if (isset($methods['all_methods']) && $methods['all_methods']) {
            $rights = $this->httpMethods;
        }

        foreach ($methods as $method => $flag) {
            if (!$flag) {
                if (isset($rights[$method])) {
                    unset($rights[$method]);
                }
                continue;
            }
            $rights[$method] = true;
        }

        if (empty($rights)) {
            return null;
        }

        return array_keys($rights);
    }
}
