<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Api\Firebase\Token;

use Lcobucci\JWT\Builder;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Tests\TestCase;

class GetTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_valid_token()
    {
        $shopId = $this->faker->randomNumber();

        $date = (new \DateTime('tomorrow'));

        $idToken = (new Builder())
            ->expiresAt($date->getTimestamp())
            ->withClaim('uid', $this->faker->uuid)->getToken();

        $refreshToken = (new Builder())->getToken();

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getRaw')
            ->will($this->returnValueMap([
                ['PS_PSX_FIREBASE_REFRESH_DATE', null, null, (int) $shopId, false, $date->format('Y-m-d h:m:s')],
                ['PS_PSX_FIREBASE_REFRESH_TOKEN', null, null, (int) $shopId, false, (string) $refreshToken],
                ['PS_PSX_FIREBASE_ID_TOKEN', null, null, (int) $shopId, false, (string) $idToken]
            ]));

        $token = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$configuration])
            ->setMethods(['refresh'])
            ->getMock();

        $token->expects($this->never())
            ->method('refresh');

        $this->assertEquals((string) $idToken, $token->getToken($shopId));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_refresh_expired_token()
    {
        $shopId = $this->faker->randomNumber();

        $date = (new \DateTime('yesterday'));

        $idToken = (new Builder())
            ->expiresAt($date->getTimestamp())
            ->withClaim('uid', $this->faker->uuid)->getToken();

        $refreshToken = (new Builder())->getToken();

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getRaw')
            ->will($this->returnValueMap([
                ['PS_PSX_FIREBASE_REFRESH_DATE', null, null, (int) $shopId, false, $date->format('Y-m-d h:m:s')],
                ['PS_PSX_FIREBASE_REFRESH_TOKEN', null, null, (int) $shopId, false, (string) $refreshToken],
                ['PS_PSX_FIREBASE_ID_TOKEN', null, null, (int) $shopId, false, (string) $idToken]
            ]));

        $token = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$configuration])
            ->setMethods(['refresh'])
            ->getMock();

        $token->expects($this->once())
            ->method('refresh')
            ->with($shopId);

        $this->assertEquals((string) $idToken, $token->getToken($shopId));
    }
}
