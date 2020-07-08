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

namespace PrestaShop\AccountsAuth\Presenter;

use Module;
use PrestaShop\AccountsAuth\Service\PsAccountsService;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
{
    /**
     * @var psAccountsService
     */
    protected $psAccountsService;

    /**
     * @param string $psxName
     */
    public function __construct($psxName)
    {
        $this->psAccountsService = new PsAccountsService();
        $this->psAccountsService->setPsxName($psxName);
        $this->psAccountsService->manageOnboarding();
    }

    /**
     * Present the PsAccounts module for vue.
     *
     * @return array
     */
    public function present()
    {
        return [
          'psIs17' => $this->psAccountsService->getShopContext()->isShop17(),
          'psAccountsInstallLink' => $this->psAccountsService->getPsAccountsInstallLink(),
          'psAccountsEnableLink' => $this->psAccountsService->getPsAccountsEnableLink(),
          'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
          'psAccountsIsEnabled' => Module::isEnabled('ps_accounts'),
          'onboardingLink' => $this->psAccountsService->getOnboardingLink($this->psAccountsService->getCurrentShop()['id']),
          'user' => [
              'email' => $this->psAccountsService->getEmail($this->psAccountsService->getCurrentShop()['id']),
              'emailIsValidated' => $this->psAccountsService->isEmailValidated($this->psAccountsService->getCurrentShop()['id']),
              'isSuperAdmin' => $this->psAccountsService->getContext()->employee->isSuperAdmin(),
            ],
          'currentShop' => $this->psAccountsService->getCurrentShop(),
          'shops' => $this->psAccountsService->getShopsTree(),
          'superAdminEmail' => $this->psAccountsService->getSuperAdminEmail(),
          'ssoResendVerificationEmail' => $_ENV['SSO_RESEND_VERIFICATION_EMAIL'],
          'language' => $this->psAccountsService->getContext()->language->iso_code,
        ];
    }
}
