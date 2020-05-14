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

namespace PrestaShop\AccountsAuth\Processor;

use Configuration;
use ModuleCore;
use PrestaShop\AccountsAuth\Presenter\Store\StorePresenter;

class Onboarding
{
    /**
     * PsAccounts module intance
     *
     * @var \Module
     */
    private $psAccountsInstance;

    public function __construct()
    {
        $this->psAccountsInstance = ModuleCore::getInstanceByName('ps_accounts');
    }

    /**
     * Present all data for vue
     *
     * @return array
     */
    public function present()
    {
        /**
         * Hack for phpstan
         *
         * @var \Ps_accounts $psAccountsInstance
         */
        $psAccountsInstance = $this->psAccountsInstance;

        if (false == $psAccountsInstance) {
            return ['psaccounts' => false];
        }

        return (new StorePresenter($psAccountsInstance, $psAccountsInstance->getContext()))->present();
    }

    /**
     * Check if you are already onboarded
     *
     * @return bool
     */
    public function isOnboarded()
    {
        return Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY')
            && Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY')
            && Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA');
    }
}
