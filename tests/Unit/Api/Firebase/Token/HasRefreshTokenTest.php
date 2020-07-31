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
use PrestaShop\AccountsAuth\Tests\TestCase;

class HasRefreshTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_true()
    {
        $shopId = 5;

        $configuration = $this->createMock(Configuration::class);

        $configuration->method('getRaw')
            ->with('PS_PSX_FIREBASE_REFRESH_TOKEN', null, null, (int) $shopId)
            ->willReturn(null);

        $this->assertFalse((new Token($configuration))->hasRefreshToken($shopId));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_false()
    {
        $shopId = 5;

        $configuration = $this->createMock(Configuration::class);

        $configuration->method('getRaw')
            ->with('PS_PSX_FIREBASE_REFRESH_TOKEN', null, null, (int) $shopId)
            ->willReturn('sqjfjkjsfhksklfjlsjflsj');

        $this->assertTrue((new Token($configuration))->hasRefreshToken($shopId));
    }

}
