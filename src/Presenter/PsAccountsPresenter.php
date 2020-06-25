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

use Context;
use Module;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Service\SshKey;
use PrestaShop\Module\PsAccounts\Adapter\LinkAdapter;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
{
    const STR_TO_SIGN = 'data';

    /**
     * @var string
     */
    public $psxName;

    /**
     * @var Module
     */
    public $module;

    /**
     * @var Context
     */
    public $context;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param string $psxName
     */
    public function __construct($psxName)
    {
        $this->psxName = $psxName;
        $dotenv = new Dotenv();
        $dotenv->load(_PS_MODULE_DIR_ . 'ps_accounts/.env');
        $this->module = Module::getInstanceByName('ps_accounts');
        $this->context = Context::getContext();
    }

    /**
     * Present the PsAccounts module for vue.
     *
     * @return array
     */
    public function present()
    {
        $currentShop = $this->getCurrentShop();
        $this->generateSshKey($currentShop['id']);
        $this->saveQueriesParams($currentShop['id']);

        $presenter = [
          'psIs17' => $this->isPs17(),
          'psAccountsInstallLink' => $this->getPsAccountsInstallLink(),
          'psAccountsEnableLink' => $this->getPsAccountsEnableLink(),
          'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
          'psAccountsIsEnabled' => Module::isEnabled('ps_accounts'),
          'onboardingLink' => $this->getOnboardingLink($currentShop['id']),
          'user' => [
              'email' => $this->getEmail($currentShop['id']),
              'emailIsValidated' => $this->isEmailValidated($currentShop['id']),
              'isSuperAdmin' => $this->context->employee->isSuperAdmin(),
            ],
          'currentShop' => $currentShop,
          'shops' => $this->getShopsTree(),
          'firebaseRefreshToken' => $this->getFirebaseRefreshToken($currentShop['id']),
          'superAdminEmail' => $this->getSuperAdminEmail(),
          'ssoResendVerificationEmail' => $_ENV['SSO_RESEND_VERIFICATION_EMAIL'],
        ];

        return $presenter;
    }

    /**
     * @param int $shopId
     *
     * @return string | null
     */
    private function getFirebaseRefreshToken($shopId)
    {
        return \Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN', null, null, (int) $shopId) ?: null;
    }

    /**
     * @return string
     */
    private function getSuperAdminEmail()
    {
        $employee = new \Employee(1);

        return $employee->email;
    }

    /**
     * @return bool
     */
    private function isPs17()
    {
        return version_compare(_PS_VERSION_, '1.7.3.0', '>=');
    }

    /**
     * @return string | null
     */
    public function getPsAccountsInstallLink()
    {
        if (true === Module::isInstalled('ps_accounts')) {
            return null;
        }

        if ($this->isPs17()) {
            $router = $this->get('router');

            return substr(\Tools::getShopDomainSsl(true) . __PS_BASE_URI__, 0, -1) . $router->generate('admin_module_manage_action', [
                'action' => 'install',
                'module_name' => 'ps_accounts',
            ]);
        }

        return $this->context->link->getAdminLink('AdminModules') . '&module_name=' . $this->psxName . '&install=' . $this->psxName;
    }

    /**
     * @return string | null
     */
    public function getPsAccountsEnableLink()
    {
        if (true === Module::isEnabled('ps_accounts')) {
            return null;
        }

        if ($this->isPs17()) {
            $router = $this->get('router');

            return substr(\Tools::getShopDomainSsl(true) . __PS_BASE_URI__, 0, -1) . $router->generate('admin_module_manage_action', [
                'action' => 'enable',
                'module_name' => 'ps_accounts',
            ]);
        }

        return $this->context->link->getAdminLink('AdminModules') . '&module_name=' . $this->psxName . '&enable=1';
    }

    /**
     * @param int $shopId
     *
     * @return string | null
     */
    public function getEmail($shopId)
    {
        return \Configuration::get('PS_PSX_EMAIL', null, null, (int) $shopId) ?: null;
    }

    /**
     * @param int $shopId
     *
     * @return bool
     */
    public function isEmailValidated($shopId)
    {
        // TODO:
        return in_array(\Configuration::get('PS_PSX_EMAIL_IS_VERIFIED', null, null, (int) $shopId), ['1', 1, true]);
    }

    /**
     * @return array
     */
    public function getCurrentShop()
    {
        $shop = \Shop::getShop($this->context->shop->id);
        $linkAdapter = new LinkAdapter($this->context->link);

        return [
            'id' => $shop['id_shop'],
            'name' => $shop['name'],
            'domain' => $shop['domain'],
            'domainSsl' => $shop['domain_ssl'],
            'url' => $linkAdapter->getAdminLink(
                'AdminModules',
                true,
                [],
                [
                    'configure' => $this->psxName,
                    'setShopContext' => 's-' . $shop['id_shop'],
                ]
            ),
        ];
    }

    /**
     * @param int $shopId
     *
     * @return string
     */
    public function getProtocol($shopId)
    {
        return false == \Configuration::get('PS_SSL_ENABLED', null, null, (int) $shopId) ? 'http' : 'https';
    }

    /**
     * @param int $shopId
     *
     * @return string
     */
    public function getDomainName($shopId)
    {
        $currentShop = $this->getCurrentShop();

        return false == \Configuration::get('PS_SSL_ENABLED', null, null, (int) $shopId) ? $currentShop['domain'] : $currentShop['domain_ssl'];
    }

    /**
     * @param int $shopId
     *
     * @return string
     */
    public function getOnboardingLink($shopId)
    {
        if (false === Module::isInstalled('ps_accounts') || false === Module::isEnabled('ps_accounts')) {
            return '';
        }

        $callback = preg_replace(
            '/^https?:\/\/[^\/]+/',
            '',
            $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->psxName
        );

        $uiSvcBaseUrl = $_ENV['ACCOUNTS_SVC_UI_URL'];
        if (false === $uiSvcBaseUrl) {
            throw new \Exception('Environmenrt variable ACCOUNTS_SVC_UI_URL should not be empty');
        }
        $protocol = $this->getProtocol($shopId);
        $domainName = $this->getDomainName($shopId);
        $currentShop = $this->getCurrentShop();
        $queryParams = [
            'bo' => $callback,
            'pubKey' => \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, null, (int) $shopId),
            'next' => preg_replace(
                '/^https?:\/\/[^\/]+/',
                '',
                $this->context->link->getAdminLink('AdminConfigureHmacPsAccounts')
            ),
            'name' => $currentShop['name'],
            'lang' => $this->context->language->iso_code,
        ];

        $queryParamsArray = [];
        foreach ($queryParams as $key => $value) {
            $queryParamsArray[] = $key . '=' . urlencode($value);
        }
        $strQueryParams = implode('&', $queryParamsArray);
        $response = $uiSvcBaseUrl . '/shop/account/link/' . $protocol . '/' . $domainName
            . '/' . $protocol . '/' . $domainName . '/' . $this->psxName . '?' . $strQueryParams;

        return $response;
    }

    /**
     * @param int $shopId
     *
     * @return void
     */
    private function generateSshKey($shopId)
    {
        if (false === $this->hasSshKey($shopId)) {
            $sshKey = new SshKey();
            $key = $sshKey->generate();
            \Configuration::updateValue('PS_ACCOUNTS_RSA_PRIVATE_KEY', $key['privatekey'], false, null, (int) $shopId);
            \Configuration::updateValue('PS_ACCOUNTS_RSA_PUBLIC_KEY', $key['publickey'], false, null, (int) $shopId);
            $data = 'data';
            \Configuration::updateValue(
                'PS_ACCOUNTS_RSA_SIGN_DATA',
                $sshKey->signData(
                    \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId),
                    self::STR_TO_SIGN
                ), false, null, (int) $shopId
            );
        }
    }

    /**
     * @param int $shopId
     *
     * @return bool
     */
    private function hasSshKey($shopId)
    {
        return false !== \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, null, (int) $shopId)
            && !empty(\Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, null, (int) $shopId))
            && false !== \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId)
            && !empty(\Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId))
            && false !== \Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA', null, null, (int) $shopId)
            && !empty(\Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA', null, null, (int) $shopId));
    }

    /**
     * @return bool
     */
    private function isShopContext()
    {
        if (\Shop::isFeatureActive() && \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    private function getShopsTree()
    {
        $shopList = [];

        if (true === $this->isShopContext()) {
            return $shopList;
        }

        $linkAdapter = new LinkAdapter($this->context->link);

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $shops[] = [
                    'id' => $shopId,
                    'name' => $shopData['name'],
                    'domain' => $shopData['domain'],
                    'domainSsl' => $shopData['domain_ssl'],
                    'url' => $linkAdapter->getAdminLink(
                        'AdminModules',
                        true,
                        [],
                        [
                            'configure' => $this->module->name,
                            'setShopContext' => 's-' . $shopId,
                        ]
                    ),
                ];
            }

            $shopList[] = [
                'id' => $groupId,
                'name' => $groupData['name'],
                'shops' => $shops,
            ];
        }

        return $shopList;
    }

    /**
     * Only callable during onboarding
     *
     * @param int $shopId
     *
     * @return void
     */
    private function saveQueriesParams($shopId)
    {
        $token = new Token();

        if (
            null !== \Tools::getValue('email')
            && !empty(\Tools::getValue('email'))
        ) {
            \Configuration::updateValue('PS_PSX_EMAIL', \Tools::getValue('email'), false, null, (int) $shopId);
            if (
                null !== \Tools::getValue('emailVerified')
                && !empty(\Tools::getValue('emailVerified'))
            ) {
                \Configuration::updateValue('PS_PSX_EMAIL_IS_VERIFIED', 'true' === \Tools::getValue('emailVerified'), false, null, (int) $shopId);
            }
        }

        if (
            null !== \Tools::getValue('adminToken')
            && !empty(\Tools::getValue('adminToken'))
            && true === $this->hasSshKey($shopId)
        ) {
            \Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', \Tools::getValue('adminToken'), false, null, (int) $shopId);
            $token->getRefreshTokenWithAdminToken(\Tools::getValue('adminToken'), $shopId);
        }
        $token->refresh($shopId);
    }

    /**
     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function get($serviceName)
    {
        if (null === $this->container) {
            $this->container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        }

        return $this->container->get($serviceName);
    }
}
