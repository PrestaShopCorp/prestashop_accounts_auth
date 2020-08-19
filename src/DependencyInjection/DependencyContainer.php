<?php

namespace PrestaShop\AccountsAuth\DependencyInjection;

use Context;
use Module;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Adapter\LinkAdapter;
use PrestaShop\AccountsAuth\Api\Client\FirebaseClient;
use PrestaShop\AccountsAuth\Context\ShopContext;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
use PrestaShop\AccountsAuth\Service\PsAccountsService;

class DependencyContainer
{
    /**
     * @var array kinda service container
     */
    private static $container = array();

    /**
     * Dependency boilerplate lives here
     *
     * @param string $class
     * @param array $arguments optional arguments to build dependency
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function get($class, array $arguments = array()) {

        if (! array_key_exists($class, self::$container)) {

            $dep = null;

            switch ($class) {

                case FirebaseClient::class :
                    $dep = new FirebaseClient();
                    break;

                case Module::class :
                    $dep = Module::getInstanceByName('ps_accounts');
                    break;

                case Context::class :
                    $dep = Context::getContext();
                    break;

                case ShopContext::class :
                    $dep = new ShopContext();
                    break;

                case LinkAdapter::class :
                    $dep = new LinkAdapter();
                    break;

                case Configuration::class :
                    $dep = new Configuration();
                    $dep->setIdShop((int) \Context::getContext()->shop->id);
                    break;

                case ConfigurationRepository::class :
                    $dep = new ConfigurationRepository($this->get(Configuration::class));
                    break;

                case PsAccountsService::class :
                    $dep = new PsAccountsService(
                        $this->get(ConfigurationRepository::class),
                        $this->get(FirebaseClient::class),
                        $this->get(Module::class),
                        $this->get(Context::class),
                        $this->get(ShopContext::class),
                        $this->get(LinkAdapter::class)
                    );
                    break;

                default:
                    $msg = "Cannot build dependency : " . $class;
                    error_log($msg);
                    //throw new \Exception("Cannot build dependency : " . $class);
                    break;
            }

            self::$container[$class] = $dep;
        }

        return self::$container[$class];
    }

    /**
     * @param mixed $class
     * @param mixed $instance
     */
    public function set($class, $instance = null)
    {
        if (func_num_args() == 1) {
            $instance = $class;
            $class = get_class($instance);
        }

        self::$container[$class] = $instance;
    }

    /**
     * Empties dependency cache
     */
    public function clearCache()
    {
        self::$container = array();
    }

    /**
     * Utility method to build dependencies for a given method
     *
     * @param mixed $method
     * @param array $params
     *
     * @return array
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function buildDependencies($method, array $params = array())
    {
        $reflectionMethod = $method;
        if (! $method instanceof \ReflectionMethod) {
            $reflectionMethod = new \ReflectionMethod(get_class($method), '__construct');
        }

        $dependencies = array();

        foreach ($reflectionMethod->getParameters() as $index => $reflectionParameter) {

            $param = $reflectionParameter->getName();

            if (! isset($params[$index]) || $params[$index] === null) {

                $dependencies[$param] = $this->get($reflectionParameter->getClass()->getName());
            } else {
                $dependencies[$param] = $params[$index];
            }
        }

        return $dependencies;
    }
}

