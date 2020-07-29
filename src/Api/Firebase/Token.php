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

namespace PrestaShop\AccountsAuth\Api\Firebase;

use Lcobucci\JWT\Parser;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\FirebaseClient;
use PrestaShop\AccountsAuth\Environment\Env;

/**
 * Handle authentication firebase requests.
 */
class Token
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var FirebaseClient
     */
    private $firebaseClient;

    /**
     * Token constructor.
     *
     * @param Configuration|null $configuration
     * @param FirebaseClient|null $firebaseClient
     *
     * @throws \Exception
     */
    public function __construct($configuration=null, $firebaseClient=null)
    {
        new Env();

        //parent::__construct();

        $this->injectDependencies($configuration, $firebaseClient);
    }

    /**
     * Refresh the token.
     *
     * @see https://firebase.google.com/docs/reference/rest/auth#section-refresh-token Firebase documentation
     *
     * @param int $shopId
     *
     * @return array
     */
    public function refresh($shopId)
    {
        // FIXME : do we really need to do that ?
        if (! $this->configuration->getLock($shopId)) {
            return [];
        }

        $response = $this->firebaseClient->getTokenForRefreshToken(
            $this->configuration->get('PS_PSX_FIREBASE_REFRESH_TOKEN')
        );

        if ($response && true === $response['status']) {
            $this->updateShopToken($response['body']['id_token'], $response['body']['refresh_token'], $shopId);
        }

        $this->configuration->freeLock($shopId);

        return $response;
    }

    /**
     * get refreshToken.
     *
     * @see https://firebase.google.com/docs/reference/rest/auth Firebase documentation
     *
     * @param string $customToken
     * @param int $shopId
     *
     * @return void
     */
    public function getRefreshTokenWithAdminToken($customToken, $shopId)
    {
        if (false == $customToken) {
            return;
        }

        $response = $this->firebaseClient->signInWithCustomToken($customToken);

        if (!$response || false === $response['status']) {
            return;
        }

        $this->updateShopUuid($customToken, $shopId);

        $this->updateShopToken($response['body']['idToken'], $response['body']['refreshToken'], $shopId);

        $this->refresh($shopId);
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     * @param int $shopId
     */
    public function updateShopToken($idToken, $refreshToken, $shopId)
    {
        $this->configuration->setIdShop($shopId);

        $this->configuration->set('PS_PSX_FIREBASE_ID_TOKEN', $idToken);

        $this->configuration->set('PS_PSX_FIREBASE_REFRESH_TOKEN', $refreshToken);

        $this->configuration->set('PS_PSX_FIREBASE_REFRESH_DATE', date('Y-m-d H:i:s'));
    }

    /**
     * @param string $customToken
     * @param int $shopId
     */
    public function updateShopUuid($customToken, $shopId)
    {
        $uid = $this->parseJwt($customToken)->getClaim('uid');

        $this->configuration->setIdShop($shopId);

        // compat
        if (
            false === $this->configuration->getRaw(
            'PS_CHECKOUT_SHOP_UUID_V4',
            null,
            null,
            null
            )
        ) {
            $this->configuration->set('PS_CHECKOUT_SHOP_UUID_V4', $uid);
        }

        $this->configuration->set('PS_PSX_FIREBASE_ADMIN_TOKEN', $customToken);

        $this->configuration->set('PSX_UUID_V4', $uid);
    }

    /**
     * Check if we have a refresh token.
     *
     * @param int $shopId
     *
     * @return bool
     */
    public function hasRefreshToken($shopId)
    {
        $refresh_token = $this->configuration->get(
            'PS_PSX_FIREBASE_REFRESH_TOKEN',
            null,
            null,
            (int) $shopId
        );

        return !empty($refresh_token);
    }

    /**
     * Check the token validity. The token expire time is set to 3600 seconds.
     *
     * @param int $shopId
     *
     * @return bool
     */
    public function isExpired($shopId)
    {
        $refresh_date = $this->configuration->get(
            'PS_PSX_FIREBASE_REFRESH_DATE',
            null,
            null,
            (int) $shopId
        );

        if (empty($refresh_date)) {
            return true;
        }

        return strtotime($refresh_date) + 3600 < time();
    }

    /**
     * Get the user firebase token.
     *
     * @param int $shopId
     *
     * @return string
     */
    public function getToken($shopId)
    {
        if ($this->hasRefreshToken($shopId) && $this->isExpired($shopId)) {
            $this->refresh($shopId);
        }

        return $this->configuration->get(
            'PS_PSX_FIREBASE_ID_TOKEN',
            null,
            null,
            (int) $shopId
        );
    }

    /**
     * @param string $adminToken
     *
     * @return \Lcobucci\JWT\Token
     */
    public function parseJwt($adminToken)
    {
        return (new Parser())->parse((string) $adminToken);
    }

    /**
     * @param Configuration|null $configuration
     * @param FirebaseClient|null $firebaseApiClient
     */
    private function injectDependencies($configuration, $firebaseApiClient)
    {
        if ($configuration) {
            $this->configuration = $configuration;
        } else {
            $this->configuration = new Configuration();
        }

        if ($firebaseApiClient) {
            $this->firebaseClient = $firebaseApiClient;
        } else {
            $this->firebaseClient = new FirebaseClient();
        }
    }
}
