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

use PrestaShop\AccountsAuth\Api\Firebase\Client\FirebaseClient;

/**
 * Handle authentication firebase requests.
 */
class Token extends FirebaseClient
{
    /**
     * Refresh the token.
     *
     * @see https://firebase.google.com/docs/reference/rest/auth#section-refresh-token Firebase documentation
     *
     * @param int $idShop
     *
     * @return array
     */
    public function refresh($idShop)
    {
        if (true == \Configuration::get('PS_PSX_FIREBASE_LOCK')) {
            return [];
        }
        \Configuration::updateValue('PS_PSX_FIREBASE_LOCK', true,
            false,
            null,
            (int) $idShop
        );
        $this->setRoute('https://securetoken.googleapis.com/v1/token');

        $response = $this->post([
            'json' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => \Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN'),
            ],
        ]);

        if ($response && true === $response['status']) {
            \Configuration::updateValue('PS_PSX_FIREBASE_ID_TOKEN', $response['body']['id_token'],
                false,
                null,
                (int) $idShop
            );
            \Configuration::updateValue('PS_PSX_FIREBASE_REFRESH_TOKEN', $response['body']['refresh_token'],
                false,
                null,
                (int) $idShop
            );
            \Configuration::updateValue('PS_PSX_FIREBASE_REFRESH_DATE', date('Y-m-d H:i:s'),
                false,
                null,
                (int) $idShop
            );
        }
        \Configuration::updateValue('PS_PSX_FIREBASE_LOCK', false,
            false,
            null,
            (int) $idShop
        );

        return $response;
    }

    /**
     * get refreshToken.
     *
     * @see https://firebase.google.com/docs/reference/rest/auth Firebase documentation
     *
     * @param string $adminToken
     * @param int $idShop
     *
     * @return array
     */
    public function getRefreshTokenWithAdminToken($adminToken, $idShop)
    {
        $this->setRoute('https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken?key=' . $_ENV['FIREBASE_API_KEY']);
        $response = $this->post([
            'json' => [
                'token' => $adminToken,
                'returnSecureToken' => true,
            ],
        ]);
        $jwt = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $adminToken)[1]))));

        if ($response && true === $response['status']) {
            if (
                false === \Configuration::get('PS_CHECKOUT_SHOP_UUID_V4',
                null,
                null,
                (int) $idShop
            )
            ) {
                \Configuration::updateValue(
                    'PS_CHECKOUT_SHOP_UUID_V4', $jwt->uid,
                    false,
                    null,
                    (int) $idShop
                );
            }

            \Configuration::updateValue(
                'PSX_UUID_V4', $jwt->uid,
                false,
                null,
                (int) $idShop
            );
            \Configuration::updateValue(
                'PS_PSX_FIREBASE_ID_TOKEN', $response['body']['idToken'],
                false,
                null,
                (int) $idShop
            );
            \Configuration::updateValue('PS_PSX_FIREBASE_REFRESH_TOKEN', $response['body']['refreshToken'],
                false,
                null,
                (int) $idShop
            );
            \Configuration::updateValue('PS_PSX_FIREBASE_REFRESH_DATE', date('Y-m-d H:i:s'),
                false,
                null,
                (int) $idShop
            );
        }

        return $response;
    }

    /**
     * Check if we have a refresh token.
     *
     * @param int $idShop
     *
     * @return bool
     */
    public function hasRefreshToken($idShop)
    {
        $refresh_token = \Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN',
            null,
            null,
            (int) $idShop
        );

        return !empty($refresh_token);
    }

    /**
     * Check the token validity. The token expire time is set to 3600 seconds.
     *
     * @param int $idShop
     *
     * @return bool
     */
    public function isExpired($idShop)
    {
        $refresh_date = \Configuration::get('PS_PSX_FIREBASE_REFRESH_DATE',
            null,
            null,
            (int) $idShop
        );

        if (empty($refresh_date)) {
            return true;
        }

        return strtotime($refresh_date) + 3600 < time();
    }

    /**
     * Get the user firebase token.
     *
     * @param int $idShop
     *
     * @return string
     */
    public function getToken($idShop)
    {
        if ($this->hasRefreshToken($idShop) && $this->isExpired($idShop)) {
            $this->refresh($idShop);
        }

        return \Configuration::get('PS_PSX_FIREBASE_ID_TOKEN',
            null,
            null,
            (int) $idShop
        );
    }
}
