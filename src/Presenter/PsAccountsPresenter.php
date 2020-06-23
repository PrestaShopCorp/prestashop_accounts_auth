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
use PrestaShop\AccountsAuth\Manager\ShopUuidManager;
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
    public $psx;

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

    public function __construct(string $psx)
    {
        $this->psx = $psx;
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
        $this->getRefreshTokenWithAdminToken($currentShop['id']);

        $presenter = [
          'psIs17' => $this->isPs17(),
          'psAccountsInstallLink' => $this->getPsAccountsInstallLink(),
          'psAccountsEnableLink' => $this->getPsAccountsEnableLink(),
          'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
          'psAccountIsEnabled' => Module::isEnabled('ps_accounts'),
          'onboardingLink' => $this->getOnboardingLink($currentShop['id']),
          'user' => [
              'email' => $this->getEmail(),
              'emailIsValidated' => $this->isEmailValited(),
              'isSuperAdmin' => $this->context->employee->isSuperAdmin(),
            ],
          'currentShop' => $currentShop,
          'shops' => $this->getShopsTree(),
        ];
        // dump($presenter);

        return $presenter;
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

        return $this->context->link->getAdminLink('AdminModules') . '&module_name=' . $this->psx . '&install=' . $this->psx;
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

        return $this->context->link->getAdminLink('AdminModules') . '&module_name=' . $this->psx . '&enable=1';
    }

    /**
     * @return string | null
     */
    public function getEmail()
    {
        if (
            null !== \Tools::getValue('email')
            && !empty(\Tools::getValue('email'))
        ) {
            return \Tools::getValue('email');
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isEmailValited()
    {
        return $this->getEmail()
            && null !== \Tools::getValue('emailVerified')
            && !empty(\Tools::getValue('emailVerified'))
            && true == \Tools::getValue('emailVerified');
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
                    'configure' => $this->psx,
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
            $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->psx
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
            'lang' => $this->context->language->locale,
        ];

        $queryParamsArray = [];
        foreach ($queryParams as $key => $value) {
            $queryParamsArray[] = $key . '=' . urlencode($value);
        }
        $strQueryParams = implode('&', $queryParamsArray);
        $response = $uiSvcBaseUrl . '/shop/account/link/' . $protocol . '/' . $domainName
            . '/' . $protocol . '/' . $domainName . '/' . $this->psx . '?' . $strQueryParams;

        return $response;
    }

    /**
     * @param int $shopId
     *
     * @return void
     */
    private function generateSshKey($shopId)
    {
        if (
            false === \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, null, (int) $shopId)
            || false === \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId)
            || false === \Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA', null, null, (int) $shopId)
        ) {
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
    private function getRefreshTokenWithAdminToken($shopId)
    {
        if (
            null !== \Tools::getValue('adminToken')
            && !empty(\Tools::getValue('adminToken'))
        ) {
            \Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', \Tools::getValue('adminToken'), false, null, (int) $shopId);
            $ShopUuidManager = new ShopUuidManager();
            $ShopUuidManager->generateForShop($shopId);
            $token = new Token();
            $token->getRefreshTokenWithAdminToken(\Tools::getValue('adminToken'), $shopId);
            $token->refresh($shopId);
        }
    }

    /**
     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
     *
     * @return void
     */
    public function get($serviceName)
    {
        if (null === $this->container) {
            $this->container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        }

        return $this->container->get($serviceName);
    }
}
