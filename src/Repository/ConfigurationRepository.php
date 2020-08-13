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

namespace PrestaShop\AccountsAuth\Repository;

use Lcobucci\JWT\Parser;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Auth;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Service\PsAccountsService;

class ConfigurationRepository
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * ConfigurationRepository constructor.
     *
     * @param Configuration $configuration
     * @param Token $token
     * @param Auth $auth
     */
    public function __construct($configuration, $token, $auth)
    {
        $this->injectDefaultDependencies($configuration, $token, $auth);
    }

    // TODO : kill refresh date
    // TODO : kill admin_token
    // TODO : what if no token returned
    //
    // 'Authorization' => 'Bearer ' . (new Token())->getToken((int) $context->shop->id),
    // (new ConfigurationRepository())->getOrRefreshIdToken()

    /**
     * @param string $idToken
     * @param string $refreshToken
     */
    public function updateIdAndRefreshTokens($idToken, $refreshToken)
    {
        $this->configuration->set(Configuration::PS_PSX_FIREBASE_ID_TOKEN, $idToken);
        $this->configuration->set(Configuration::PS_PSX_FIREBASE_REFRESH_TOKEN, $refreshToken);

        // FIXME : use JWT expiry date
        $this->configuration->set('PS_PSX_FIREBASE_REFRESH_DATE', date('Y-m-d H:i:s'));
    }

    /**
     * Get the user firebase token.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getOrRefreshIdToken()
    {
        if ($this->hasRefreshToken() && $this->isIdTokenExpired()) {
            $this->refreshIdToken();
        }
        return $this->configuration->get(Configuration::PS_PSX_FIREBASE_ID_TOKEN);
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function refreshIdToken()
    {
        $response = $this->token->exchangeRefreshTokenForIdToken(
            $this->configuration->get(Configuration::PS_PSX_FIREBASE_REFRESH_TOKEN)
        );

        if ($response && true === $response['status']) {
            $this->updateIdAndRefreshTokens(
                $response['body']['id_token'],
                $response['body']['refresh_token']
            );
            return true;
        }
        return false;
    }

    /**
     * get refreshToken.
     *
     * @see https://firebase.google.com/docs/reference/rest/auth Firebase documentation
     *
     * @param string $customToken
     * @return bool
     *
     */
    public function exchangeCustomTokenForIdAndRefreshToken($customToken)
    {
        $response = $this->auth->signInWithCustomToken($customToken);

        if ($response && true === $response['status']) {
            $uid = (new Parser())->parse((string) $customToken)->getClaim('uid');

            $this->updateShopUuid($uid);

            $this->updateIdAndRefreshTokens(
                $response['body']['idToken'],
                $response['body']['refreshToken']
            );

            return true;
        }
        return false;
    }

    /**
     * Check if we have a refresh token.
     *
     * @return bool
     */
    public function hasRefreshToken()
    {
        return !empty($this->configuration->get(Configuration::PS_PSX_FIREBASE_REFRESH_TOKEN));
    }

    /**
     * Check the token validity. The token expire time is set to 3600 seconds.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isIdTokenExpired()
    {
        // iat, exp

        $token = (new Parser())->parse(
            $this->configuration->get(
                Configuration::PS_PSX_FIREBASE_ID_TOKEN
            )
        );

        return $token->isExpired();

        /*$refresh_date = $this->configuration->get(Configuration::PS_PSX_FIREBASE_REFRESH_DATE);

        if (empty($refresh_date)) {
            return true;
        }

        return strtotime($refresh_date) + 3600 < time();*/
    }

    /**
     * @param string $uuid Firebase User UUID
     */
    public function updateShopUuid($uuid)
    {
        if (false === $this->configuration->get(Configuration::PS_CHECKOUT_SHOP_UUID_V4)) {
            $this->configuration->set(Configuration::PS_CHECKOUT_SHOP_UUID_V4, $uuid);
        }
        $this->configuration->set(Configuration::PSX_UUID_V4, $uuid);
    }

    /**
     * @param Configuration|null $configuration
     * @param $token
     * @param $auth
     */
    private function injectDefaultDependencies($configuration, $token, $auth)
    {
        if (! $configuration) {
            $configuration = new Configuration();
            $psAccountsService = new PsAccountsService();
            $configuration->setIdShop((int) $psAccountsService->getCurrentShop()['id']);
        }
        if(! $token) {
            $token = new Token();
        }
        if(! $auth) {
            $auth = new Auth();
        }
        $this->configuration = $configuration;
        $this->token = $token;
        $this->auth = $auth;
    }
}
