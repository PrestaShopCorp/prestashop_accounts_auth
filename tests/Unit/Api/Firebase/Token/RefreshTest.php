<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\AccountsAuth\Tests\Unit\Api\Firebase\Token;

use Lcobucci\JWT\Builder;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Api\Client\FirebaseClient;
use PrestaShop\AccountsAuth\Tests\TestCase;

class RefreshTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_handle_response_success()
    {
        $shopId = 5;
        $idToken = (new Builder())->withClaim('uid', $this->faker->uuid)->getToken();
        $refreshToken = (new Builder())->getToken();

        $configuration = $this->createMock(Configuration::class);

        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('exchangeRefreshTokenForIdToken')
            ->willReturn([
                'status' => true,
                'body' => [
                    'id_token' => $idToken,
                    'refresh_token' => $refreshToken,
                ],
            ]);

        $token = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$configuration, $firebaseClient])
            ->setMethods(['updateShopToken'])
            ->getMock();

        $token->expects($this->once())
            ->method('updateShopToken')
            ->with($idToken, $refreshToken, $shopId);

        $this->assertTrue($token->refresh($shopId));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_handle_response_error()
    {
        $shopId = 5;

        $configuration = $this->createMock(Configuration::class);

        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('exchangeRefreshTokenForIdToken')
            ->willReturn([
                'status' => false,
            ]);

        $token = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$configuration, $firebaseClient])
            ->setMethods(['updateShopToken'])
            ->getMock();

        $token->expects($this->never())
            ->method('updateShopToken');

        $token = new Token($configuration, $firebaseClient);

        $this->assertFalse($token->refresh($shopId));
    }
}
