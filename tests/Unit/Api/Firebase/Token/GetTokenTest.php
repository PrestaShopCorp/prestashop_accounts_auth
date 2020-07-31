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

        $configuration = $this->createMock(Configuration::class);

        $idToken = (new Builder())
            ->expiresAt((new \DateTime('tomorrow'))->getTimestamp())
            ->withClaim('uid', $this->faker->uuid)->getToken();

        $configuration->method('getRaw')
            ->with('PS_PSX_FIREBASE_ID_TOKEN', null, null, $shopId)
            ->willReturn($idToken);

        $token = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$configuration])
            ->setMethods(['refresh'])
            ->getMock();

        $token->expects($this->never())
            ->method('refresh');

        $this->assertEquals($idToken, $token->getToken($shopId));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_refresh_expired_token()
    {
        $shopId = $this->faker->randomNumber();

        $configuration = $this->createMock(Configuration::class);

        $idToken = (new Builder())
            ->expiresAt((new \DateTime('yesterday'))->getTimestamp())
            ->withClaim('uid', $this->faker->uuid)->getToken();

        $configuration->method('getRaw')
            ->with('PS_PSX_FIREBASE_ID_TOKEN', null, null, $shopId)
            ->willReturn($idToken);

        $token = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$configuration])
            ->setMethods(['refresh'])
            ->getMock();

        $token->expects($this->once())
            ->method('refresh')
            ->with($idToken);

        $this->assertEquals($idToken, $token->getToken($shopId));
    }
}
