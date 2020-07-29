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

use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Api\FirebaseClient;
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

        $configuration = $this->createMock(Configuration::class);

        $configuration->method('getLock')
            ->willReturn(true);

        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('getTokenForRefreshToken')
            ->willReturn([
                'status' => true,
                'body' => [
                    'id_token' => 'foo',
                    'refresh_token' => 'bar',
                ],
            ]);

        $token = new Token($configuration, $firebaseClient);

        $response = $token->refresh($shopId);

        $this->assertArrayHasKey('body', $response);

        $this->assertEquals('foo', $response['body']['id_token']);

        $this->assertEquals('bar', $response['body']['refresh_token']);
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

        $configuration->method('getLock')
            ->willReturn(true);

        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('getTokenForRefreshToken')
            ->willReturn([
                'status' => false,
            ]);

        $token = new Token($configuration, $firebaseClient);

        $response = $token->refresh($shopId);

        $this->assertFalse($response['status']);
    }
}
