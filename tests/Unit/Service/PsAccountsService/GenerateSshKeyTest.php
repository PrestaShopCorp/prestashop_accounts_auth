<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Service\PsAccountsService;

use Lcobucci\JWT\Builder;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
use PrestaShop\AccountsAuth\Service\PsAccountsService;

class GenerateSshKeyTest
{
    public function it_should_update_ssh_keys()
    {
        //$date = (new \DateTime('tomorrow'));
        $date = $this->faker->dateTimeBetween('now', '+2 hours');

        $idToken = (new Builder())
            ->expiresAt($date->getTimestamp())
            //->withClaim('uid', $this->faker->uuid)
            ->getToken();

        $refreshToken = (new Builder())->getToken();

        /** @var Configuration $configMock */
        $configMock = $this->getConfigurationMock([
            [Configuration::PS_PSX_FIREBASE_REFRESH_DATE, false, $date->format('Y-m-d h:m:s')],
            [Configuration::PS_PSX_FIREBASE_REFRESH_TOKEN, false, (string) $refreshToken],
            [Configuration::PS_PSX_FIREBASE_ID_TOKEN, false, (string) $idToken],
        ]);

        $configuration = new ConfigurationRepository($configMock);

        $service = new PsAccountsService($configuration);

        $this->assertEquals((string) $idToken, $service->generateSshKey());
    }
}
