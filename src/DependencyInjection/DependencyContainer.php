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
    private $container = [];

    /**
     * @var self
     */
    private static $instance;

    /**
     * @return DependencyContainer
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

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
    public function get($class, array $arguments = [])
    {
        if (!array_key_exists($class, $this->container)) {
            $dep = null;

            switch ($class) {
                case FirebaseClient::class:
                    $dep = new FirebaseClient();
                    break;

                case Module::class:
                    $dep = Module::getInstanceByName('ps_accounts');
                    break;

                case Context::class:
                    $dep = Context::getContext();
                    break;

                case ShopContext::class:
                    $dep = new ShopContext();
                    break;

                case LinkAdapter::class:
                    $dep = new LinkAdapter(\Context::getContext()->link);
                    break;

                case Configuration::class:
                    $dep = new Configuration();
                    $dep->setIdShop((int) \Context::getContext()->shop->id);
                    break;

                case ConfigurationRepository::class:
                    $dep = new ConfigurationRepository($this->get(Configuration::class));
                    break;

                case PsAccountsService::class:
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
                    $msg = 'Cannot build dependency : ' . $class;
                    error_log($msg);
                    //throw new \Exception("Cannot build dependency : " . $class);
                    break;
            }

            $this->container[$class] = $dep;
        }

        return $this->container[$class];
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

        $this->container[$class] = $instance;
    }

    /**
     * Empties dependency cache
     */
    public function clearCache()
    {
        $this->container = [];
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
    public function buildDependencies($method, array $params = [])
    {
        $reflectionMethod = $method;
        if (!$method instanceof \ReflectionMethod) {
            $reflectionMethod = new \ReflectionMethod(get_class($method), '__construct');
        }

        $dependencies = [];

        foreach ($reflectionMethod->getParameters() as $index => $reflectionParameter) {
            $param = $reflectionParameter->getName();

            if (!isset($params[$index]) || $params[$index] === null) {
                $dependencies[$param] = $this->get($reflectionParameter->getClass()->getName());
            } else {
                $dependencies[$param] = $params[$index];
            }
        }

        return $dependencies;
    }
}
