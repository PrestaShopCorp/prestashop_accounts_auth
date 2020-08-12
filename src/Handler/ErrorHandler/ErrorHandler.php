<?php
/**
* 2007-2020 PrestaShop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

namespace PrestaShop\AccountsAuth\Handler\Error;

use PrestaShop\AccountsAuth\Service\PsAccountsService;
use Raven_Client;

/**
 * Handle Error.
 */
class ErrorHandler
{
    /**
     * @var Raven_Client
     */
    protected $client;

    /**
     * @var ErrorHandler
     */
    private static $instance;

    /**
     * @var ErrorHandler
     */
    private static $instance;

    public function __construct()
    {
        $psAccountsService = new PsAccountsService();
        $this->client = new Raven_Client(
            $_ENV['SENTRY_CREDENTIALS'],
            [
                'level' => 'warning',
                'tags' => [
                    'php_version' => phpversion(),
                    'ps_accounts_version' => \Ps_accounts::VERSION,
                    'prestashop_vesion' => _PS_VERSION_,
                    'ps_accounts_is_enabled' => \Module::isEnabled('ps_accounts'),
                    'ps_accounts_is_installed' => \Module::isInstalled('ps_accounts'),
                    'email' => $psAccountsService->getEmail($psAccountsService->getCurrentShop()['id']),
                ],
            ]
        );

        $this->client->install();
    }

    /**
     * @param \Exception $error
     * @param mixed $code
     * @param bool|null $throw
     *
     * @return void
     */
    public function handle($error, $code = null, $throw = true)
    {
        $code ? $this->client->captureException($error) : $this->client->captureMessage($error);
        if ($code && true === $throw) {
            http_response_code($code);
            throw $error;
        }
    }

    /**
     * @return ErrorHandler
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ErrorHandler();
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
