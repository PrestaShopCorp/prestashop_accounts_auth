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

namespace PrestaShop\AccountsAuth\Adapter;

class Configuration
{
    const PS_PSX_FIREBASE_ID_TOKEN = 'PS_PSX_FIREBASE_ID_TOKEN';
    const PS_PSX_FIREBASE_REFRESH_TOKEN = 'PS_PSX_FIREBASE_REFRESH_TOKEN';
    const PS_CHECKOUT_SHOP_UUID_V4 = 'PS_CHECKOUT_SHOP_UUID_V4';
    const PSX_UUID_V4 = 'PSX_UUID_V4';
    const PS_PSX_FIREBASE_ADMIN_TOKEN = 'PS_PSX_FIREBASE_ADMIN_TOKEN';
    const PS_PSX_FIREBASE_REFRESH_DATE = 'PS_PSX_FIREBASE_REFRESH_DATE';
    const PS_PSX_FIREBASE_EMAIL = 'PS_PSX_FIREBASE_EMAIL';
    const PS_PSX_FIREBASE_EMAIL_IS_VERIFIED = 'PS_PSX_FIREBASE_EMAIL_IS_VERIFIED';
    const PS_ACCOUNTS_RSA_PUBLIC_KEY = 'PS_ACCOUNTS_RSA_PUBLIC_KEY';
    const PS_ACCOUNTS_RSA_PRIVATE_KEY = 'PS_ACCOUNTS_RSA_PRIVATE_KEY';
    const PS_ACCOUNTS_RSA_SIGN_DATA = 'PS_ACCOUNTS_RSA_SIGN_DATA';

    /**
     * @var int
     */
    private $idShop = null;

    /**
     * @var int
     */
    private $idShopGroup = null;

    /**
     * @var int
     */
    private $idLang = null;

    /**
     * @return int
     */
    public function getIdShop()
    {
        return $this->idShop;
    }

    /**
     * @param int $idShop
     */
    public function setIdShop($idShop)
    {
        $this->idShop = $idShop;
    }

    /**
     * @return int
     */
    public function getIdShopGroup()
    {
        return $this->idShopGroup;
    }

    /**
     * @param int $idShopGroup
     */
    public function setIdShopGroup($idShopGroup)
    {
        $this->idShopGroup = $idShopGroup;
    }

    /**
     * @return int
     */
    public function getIdLang()
    {
        return $this->idLang;
    }

    /**
     * @param int $idLang
     */
    public function setIdLang($idLang)
    {
        $this->idLang = $idLang;
    }

    /**
     * @param $key
     * @param bool $default
     *
     * @return mixed
     */
    public function get($key, $default=false)
    {
        return $this->getRaw($key, $this->idLang, $this->idShopGroup, $this->idShop, $default);
    }

    /**
     * @param $key
     * @param null $idLang
     * @param null $idShopGroup
     * @param null $idShop
     * @param bool $default
     *
     * @return mixed
     */
    public function getRaw($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
    {
        return \Configuration::get($key, $idLang, $idShopGroup, $idShop, $default);
    }

    /**
     * @param $key
     * @param $values
     * @param bool $html
     *
     * @return mixed
     */
    public function set($key, $values, $html = false)
    {
        return $this->setRaw($key, $values, $html, $this->idShopGroup, $this->idShop);
    }

    /**
     * @param $key
     * @param $values
     * @param bool $html
     * @param null $idShopGroup
     * @param null $idShop
     *
     * @return mixed
     */
    public function setRaw($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        return \Configuration::updateValue($key, $values, $html, $idShopGroup, $idShop);
    }
}
