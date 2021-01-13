<?php

namespace PrestaShop\AccountsAuth\Tests;

use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\DependencyInjection\PsAccountsServiceProvider;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Faker\Generator
     */
    public $faker;

    /**
     * @var PsAccountsServiceProvider
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

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->faker = \Faker\Factory::create();

        $this->container = PsAccountsServiceProvider::getInstance();
        $this->container->reset();
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
            ->will($this->returnCallback(function ($key, $default = false) {
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
            ->will($this->returnCallback(function ($key, $values, $html = false) {
                foreach ($this->config as &$row) {
                    if ($row[0] == $key) {
                        $row[2] = (string) $values;

                        return;
                    }
                }
                $this->config[] = [$key, false, (string) $values];
            }));

        return $configuration;
    }

    /**
     * @param \DateTimeImmutable|null $expiresAt
     * @param array $claims
     *
     * @return \Lcobucci\JWT\Token
     */
    public function makeJwtToken(\DateTimeImmutable $expiresAt = null, array $claims = [])
    {
        $builder = (new \Lcobucci\JWT\Builder())->expiresAt($expiresAt);

        foreach ($claims as $claim => $value) {
            $builder->withClaim($claim, $value);
        }

        $configuration = \Lcobucci\JWT\Configuration::forUnsecuredSigner();

        return $builder->getToken(
            $configuration->signer(),
            $configuration->signingKey()
        );
    }
}
