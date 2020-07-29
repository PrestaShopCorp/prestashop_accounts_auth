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

namespace PrestaShop\AccountsAuth\Handler\Sentry;

use PrestaShop\AccountsAuth\Service\PsAccountsService;
use Raven_Client;

/**
 * Handle Sentry.
 *
 * @doc https://github.com/getsentry/raven-php
 */
abstract class SentryHandler
{
    /**
     * @var Raven_Client
     */
    protected $client;

    public function __construct()
    {
        $psAccountsService = new PsAccountsService();
        $this->client = new Raven_Client(
            $_ENV['SENTRY_CREDENTIALS'],
            [
                'tags' => [
                    'php_version' => phpversion(),
                    'ps_accounts_version' => \Ps_accounts::VERSION,
                    'prestashop_vesion' => _PS_VERSION_,
                ],
                'ps_accounts_is_enabled' => \Module::isEnabled('ps_accounts'),
                'ps_accounts_is_installed' => \Module::isInstalled('ps_accounts'),
                'currentShop' => $psAccountsService->getCurrentShop(),
                'shops' => $psAccountsService->getShopsTree(),
            ]
        );
        $this->client->user_context(
            [
                'email' => $psAccountsService->getEmail($psAccountsService->getCurrentShop()['id']),
                'emailIsValidated' => $psAccountsService->isEmailValidated($psAccountsService->getCurrentShop()['id']),
                'isSuperAdmin' => $psAccountsService->getContext()->employee->isSuperAdmin(),
            ]);
        $this->client->install();
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function setMessage($message)
    {
        $this->client->captureMessage($message);
    }
}
