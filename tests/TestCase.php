<?php

namespace PrestaShop\AccountsAuth\Tests;

use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\DependencyInjection\DependencyContainer;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;

if (! defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', '');
}

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Faker\Generator
     */
    public $faker;

    /**
     * @var DependencyContainer
     */
    public $container;

    /**
     * @var array
     */
    private $config;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp()
    {
        parent::setUp();

        $this->faker = \Faker\Factory::create();

        $this->container = DependencyContainer::getInstance();
        $this->container->clearCache();
    }

    /**
     * @param array $valueMap
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getConfigurationMock(array $valueMap)
    {
        if (!$this->config) {
            $this->config = $valueMap;
        }

        $configuration = $this->createMock(Configuration::class);

        $configuration->method('get')
            ->will($this->returnCallback(function ($key, $default=null) {

                foreach ($this->config as $map) {

                    $return = array_pop($map);
                    if ([$key, $default] === $map) {
                        return $return;
                    }
                }
                return null;
            }));
            //->will($this->returnValueMap($valueMap));

        $configuration->method('set')
            ->will($this->returnCallback(function ($key, $values, $html=false) use ($configuration) {

                foreach ($this->config as &$row) {

                    if ($row[0] == $key) {
                        $row[2] = (string) $values;
                        return;
                    }
                }
                $this->config[] = [$key, null, (string) $values];
            }));

        return $configuration;
    }
}
