<?php

namespace PrestaShop\AccountsAuth\Adapter;

class Configuration
{
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

    /**
     * @param $shopId
     *
     * @return bool
     */
    public function getLock($shopId)
    {
        if (true == $this->getRaw('PS_PSX_FIREBASE_LOCK', null, null, (int) $shopId)) {
            return false;
        }
        $this->setRaw('PS_PSX_FIREBASE_LOCK', true, false, null, (int) $shopId);
        return true;
    }

    /**
     * @param $shopId
     */
    public function freeLock($shopId)
    {
        $this->setRaw('PS_PSX_FIREBASE_LOCK', false, false, null, (int) $shopId);
    }
}
