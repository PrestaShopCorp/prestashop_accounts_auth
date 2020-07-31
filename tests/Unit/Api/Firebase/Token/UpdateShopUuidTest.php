<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Api\Firebase\Token;

use Lcobucci\JWT\Builder;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Tests\TestCase;

class UpdateShopUuidTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_update_shop_configuration()
    {
        $shopId = 5;

        $uid = $this->faker->uuid;

        $customToken = (new Builder())->withClaim('uid', $uid)->getToken();

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getRaw', 'setRaw'])
            ->getMock();

        $configuration->method('getRaw')
            ->with('PS_CHECKOUT_SHOP_UUID_V4', null, null, null)
            ->willReturn('some_random_string');

        $configuration->expects($this->exactly(2))
            ->method('setRaw')
            ->withConsecutive(
                ['PS_PSX_FIREBASE_ADMIN_TOKEN', $customToken, false, null, $shopId],
                ['PSX_UUID_V4', $uid, false, null, $shopId]
            );

        $token = new Token($configuration);

        $token->updateShopUuid($customToken, $shopId);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_update_checkout_shop_uid()
    {
        $shopId = 5;

        $uid = $this->faker->uuid;

        $customToken = (new Builder())->withClaim('uid', $uid)->getToken();

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getRaw', 'setRaw'])
            ->getMock();

        $configuration->method('getRaw')
            ->with('PS_CHECKOUT_SHOP_UUID_V4', null, null, null)
            ->willReturn(false);

        $configuration->expects($this->exactly(3))
            ->method('setRaw')
            ->withConsecutive(
                ['PS_CHECKOUT_SHOP_UUID_V4', $uid, false, null, $shopId],
                ['PS_PSX_FIREBASE_ADMIN_TOKEN', $customToken, false, null, $shopId],
                ['PSX_UUID_V4', $uid, false, null, $shopId]
            );

        $token = new Token($configuration);

        $token->updateShopUuid($customToken, $shopId);
    }
}
