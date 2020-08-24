<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Service\PsAccountsService;

use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
use PrestaShop\AccountsAuth\Service\PsAccountsService;
use PrestaShop\AccountsAuth\Service\SshKey;
use PrestaShop\AccountsAuth\Tests\TestCase;

class GenerateSshKeyTest extends TestCase
{
    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function it_should_update_ssh_keys()
    {
        /** @var Configuration $configMock */
        $configMock = $this->getConfigurationMock([
            [Configuration::PS_ACCOUNTS_RSA_PRIVATE_KEY, false, null],
            [Configuration::PS_ACCOUNTS_RSA_PUBLIC_KEY, false, null],
            [Configuration::PS_ACCOUNTS_RSA_SIGN_DATA, false, null],
        ]);

        $configuration = new ConfigurationRepository($configMock);

        $service = new PsAccountsService($configuration);

        $this->assertEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertEmpty($configuration->getAccountsRsaSignData());

        $service->generateSshKey();

        $this->assertNotEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertNotEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertNotEmpty($configuration->getAccountsRsaSignData());

        $sshKey = new SshKey();
        $data = $this->faker->sentence();
        $signedData = $sshKey->signData($configuration->getAccountsRsaPrivateKey(), $data);

        $this->assertTrue(
            $sshKey->verifySignature(
                $configuration->getAccountsRsaPublicKey(),
                $signedData,
                $data
            )
        );
    }
}
