<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Service\PsAccountsService;

use Lcobucci\JWT\Builder;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Service\PsAccountsService;
use PrestaShop\AccountsAuth\Tests\TestCase;

class IsTokenExpiredTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_true()
    {
        $date = new \DateTimeImmutable('yesterday');

        $idToken = $this->makeJwtToken($date);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var Configuration $configMock */
        $configMock = $this->getConfigurationMock([
            [Configuration::PS_PSX_FIREBASE_REFRESH_DATE, false, $date->format('Y-m-d h:m:s')],
            [Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN, false, (string) $refreshToken],
            [Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN, false, (string) $idToken],
        ]);

        $this->container->singleton(Configuration::class, $configMock);

        $service = new PsAccountsService(
            //new ConfigurationRepository($configMock)
        );

        $this->assertTrue($service->isTokenExpired());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_false()
    {
        $date = new \DateTimeImmutable('+2 hours');

        $idToken = $this->makeJwtToken($date);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var Configuration $configMock */
        $configMock = $this->getConfigurationMock([
            [Configuration::PS_PSX_FIREBASE_REFRESH_DATE, false, $date->format('Y-m-d h:m:s')],
            [Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN, false, (string) $refreshToken],
            [Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN, false, (string) $idToken],
        ]);

        $this->container->singleton(Configuration::class, $configMock);

        $service = new PsAccountsService(
            //new ConfigurationRepository($configMock)
        );

        $this->assertFalse($service->isTokenExpired());
    }
}
