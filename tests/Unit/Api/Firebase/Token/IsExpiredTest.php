<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Api\Firebase\Token;

use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Tests\TestCase;

class IsExpiredTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_true()
    {
        $shopId = $this->faker->randomNumber();

        $configuration = $this->createMock(Configuration::class);

        $date = (new \DateTime('yesterday'))->format('Y-m-d h:m:s');

        $configuration->method('getRaw')
            ->with('PS_PSX_FIREBASE_REFRESH_DATE', null, null, $shopId)
            ->willReturn($date);

        $token = new Token($configuration);

        $this->assertTrue($token->isExpired($shopId));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_false()
    {
        $shopId = $this->faker->randomNumber();

        $configuration = $this->createMock(Configuration::class);

        $date = (new \DateTime('tomorrow'))->format('Y-m-d h:m:s');

        $configuration->method('getRaw')
            ->with('PS_PSX_FIREBASE_REFRESH_DATE', null, null, $shopId)
            ->willReturn($date);

        $token = new Token($configuration);

        $this->assertFalse($token->isExpired($shopId));
    }
}
