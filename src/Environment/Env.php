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

namespace PrestaShop\AccountsAuth\Environment;

use PrestaShop\AccountsAuth\Exception\EnvironmentFileException;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Load environment variables.
 */
class Env
{
    /**
     * Firebase public api key.
     *
     * @var string
     */
    private $firebaseApiKey;

    /**
     * @var Env
     */
    private static $instance;

    /**
     * Env constructor.
     *
     * @throws EnvironmentFileException
     */
    public function __construct()
    {
        $dotenv = new Dotenv();
        if (file_exists(_PS_MODULE_DIR_ . 'ps_accounts/.env')) {
            $dotenv->load(_PS_MODULE_DIR_ . 'ps_accounts/.env');
        } elseif (file_exists(dirname(__FILE__) . '/env')) {
            $dotenv->load(dirname(__FILE__) . '/env');
        } else {
            throw new EnvironmentFileException('Environment file not found');
        }
        $this->setFirebaseApiKey(getenv('FIREBASE_API_KEY'));
    }

    /**
     * getter for firebaseApiKey.
     *
     * @return string
     */
    public function getFirebaseApiKey()
    {
        return $this->firebaseApiKey;
    }

    /**
     * setter for firebaseApiKey.
     *
     * @param string $apiKey
     *
     * @return void
     */
    private function setFirebaseApiKey($apiKey)
    {
        $this->firebaseApiKey = $apiKey;
    }

    /**
     * @return Env
     *
     * @throws EnvironmentFileException
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Env();
        }

        return self::$instance;
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }
}
