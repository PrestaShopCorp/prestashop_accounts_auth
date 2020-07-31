<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Api\Firebase\Token;

use Lcobucci\JWT\Builder;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Tests\TestCase;

class UpdateShopTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_update_shop_configuration()
    {
        $shopId = 5;
        $idToken = (new Builder())->withClaim('uid', $this->faker->uuid)->getToken();
        $refreshToken = (new Builder())->getToken();

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getRaw', 'setRaw'])
            ->getMock();

        $datetime = (new \DateTime())->format('Y-m-d H:i:s');

        $configuration->expects($this->exactly(3))
            ->method('setRaw')
            ->withConsecutive(
                ['PS_PSX_FIREBASE_ID_TOKEN', $idToken, false, null, $shopId],
                ['PS_PSX_FIREBASE_REFRESH_TOKEN', $refreshToken, false, null, $shopId],
                ['PS_PSX_FIREBASE_REFRESH_DATE', $this->matchesRegularExpression('/^' . $datetime . '/'), false, null, $shopId]
            );

        $token = new Token($configuration);

        $token->updateShopToken($idToken, $refreshToken, $shopId);
    }
}
