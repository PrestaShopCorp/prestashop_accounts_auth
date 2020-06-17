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
use PrestaShop\AccountsAuth\Service\SshKey;
use PrestaShop\Module\PsAccounts\Adapter\LinkAdapter;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
{
    const STR_TO_SIGN = 'data';

    /**
     * @var string
     */
    public $bo;

    public function __construct(string $bo)
    {
        $this->bo = $bo;
    }

    /**
     * Present the PsAccounts module for vue.
     *
     * @param string $psx
     *
     * @return array
     */
    public function present($psx)
    {
        $this->generateSshKey();
        $presenter = [
          'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
          'psAccountIsEnabled' => Module::isEnabled('ps_accounts'),
          'onboardingLink' => $this->getOnboardingLink($psx ? $psx : 'default'),
          'user' => [
              'email' => $this->getEmail(),
              'emailIsValidated' => $this->isEmailValited(),
            ],
          'currentShop' => $this->getCurrentShop(),
          'shops' => $this->getShopsTree(),
        ];

        return $presenter;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return null !== \Tools::getValue('adminToken')
            && !empty(\Tools::getValue('adminToken'))
            && null !== \Tools::getValue('email')
            && !empty(\Tools::getValue('email')) ? \Tools::getValue('email') : '';
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
        $module = \Module::getInstanceByName('ps_accounts');
        $context = $module->getContext();

        $shop = \Shop::getShop($context->shop->id);
        $linkAdapter = new LinkAdapter($context->link);

        return [
            'id' => $shop['id_shop'],
            'name' => $shop['name'],
            'domain' => $shop['domain'],
            'domain_ssl' => $shop['domain_ssl'],
            'url' => $linkAdapter->getAdminLink(
                'AdminModules',
                true,
                [],
                [
                    'configure' => $module->name,
                    'setShopContext' => 's-' . $shop['id_shop'],
                ]
            ),
        ];
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return false == \Configuration::get('PS_SSL_ENABLED') ? 'http' : 'https';
    }

    /**
     * @return string
     */
    public function getDomainName()
    {
        $currentShop = $this->getCurrentShop();

        return false == \Configuration::get('PS_SSL_ENABLED') ? $currentShop['domain'] : $currentShop['domain_ssl'];
    }

    /**
     * @param string $psx
     *
     * @return string | false
     */
    public function getOnboardingLink($psx)
    {
        if (false === Module::isEnabled('ps_accounts')) {
            return false;
        }

        $module = Module::getInstanceByName('ps_accounts');
        $context = $module->getContext();

        $uiSvcBaseUrl = getenv('ACCOUNTS_SVC_UI_URL');
        if (false === $uiSvcBaseUrl) {
            throw new \Exception('Environmenrt variable ACCOUNTS_SVC_UI_URL should not be empty');
        }
        $protocol = $this->getProtocol();
        $domainName = $this->getDomainName();

        $queryParams = [
            'bo' => $this->bo,
            'pubKey' => \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY'),
            'next' => preg_replace(
                '/^https?:\/\/[^\/]+/',
                '',
                $context->link->getAdminLink('AdminConfigureHmacPsAccounts')
            ),
            'name' => \Configuration::get('PS_SHOP_NAME'),
            'lang' => $context->language->locale,
        ];

        $queryParamsArray = [];
        foreach ($queryParams as $key => $value) {
            $queryParamsArray[] = $key . '=' . urlencode($value);
        }
        $strQueryParams = implode('&', $queryParamsArray);
        $response = $uiSvcBaseUrl . '/shop/account/link/' . $protocol . '/' . $domainName
            . '/' . $protocol . '/' . $domainName . '/' . $psx . '?' . $strQueryParams;

        return $response;
    }

    /**
     * @return void
     */
    private function generateSshKey()
    {
        if (
            false === \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY')
            || false === \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY')
            || false === \Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA')
        ) {
            $sshKey = new SshKey();
            $key = $sshKey->generate();
            \Configuration::updateValue('PS_ACCOUNTS_RSA_PRIVATE_KEY', $key['privatekey']);
            \Configuration::updateValue('PS_ACCOUNTS_RSA_PUBLIC_KEY', $key['publickey']);
            $data = 'data';
            \Configuration::updateValue(
                'PS_ACCOUNTS_RSA_SIGN_DATA',
                $sshKey->signData(
                    \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY'),
                    self::STR_TO_SIGN
                )
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

        if (false === Module::isEnabled('ps_accounts') || true === $this->isShopContext()) {
            return $shopList;
        }

        $module = Module::getInstanceByName('ps_accounts');
        $context = $module->getContext();

        $linkAdapter = new LinkAdapter($context->link);

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $shops[] = [
                    'id' => $shopId,
                    'name' => $shopData['name'],
                    'domain' => $shopData['domain'],
                    'domain_ssl' => $shopData['domain_ssl'],
                    'url' => $linkAdapter->getAdminLink(
                        'AdminModules',
                        true,
                        [],
                        [
                            'configure' => $module->name,
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
}
