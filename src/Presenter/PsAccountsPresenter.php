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
use PrestaShop\Module\PsAccounts\Adapter\LinkAdapter;
use PrestaShop\AccountsAuth\Service\SshKey;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
{
    const STR_TO_SIGN = "data";


    /**
     * @var string
     */
    public $bo;

    public function __construct($bo)
    {
        $this->bo = $bo;
    }

    /**
     * Present the PsAccounts module for vue.
     *
     * @return array
     */
    public function present()
    {
        $this->generateSshKey();
        $presenter = [
          'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
          'psAccountIsEnabled' => Module::isEnabled('ps_accounts'),
          'onboardingLink' => $this->getOnboardingLink(),
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
     * @return string
     */
    public function getCurrentShop()
    {
        //TODO
        return \Configuration::get('PS_SHOP_NAME');
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
        return
        \Tools::getShopDomain();
    }

    /**
     * @return string
     */
    public function getOnboardingLink()
    {
        if (false === Module::isEnabled('ps_accounts')) {
            return '';
        }
        $module = Module::getInstanceByName('ps_accounts');
        $context = $module->getContext();

        $uiSvcBaseUrl = getenv('ACCOUNTS_API_URL');
        if(false === $uiSvcBaseUrl){
            throw new \Exception('Environmenrt variable ACCOUNTS_API_URL should not be empty');
        }
        $protocol = $this->getProtocol();
        $domainName = $this->getDomainName();

        $queryParams = [
        // Maybe
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
        $response = $uiSvcBaseUrl . '/shop/account/link/' . $protocol . '/' . $domainName . '/' . $protocol . '/' . $domainName .'/PSXEmoji.Deluxe.Fake.Service?' . $strQueryParams;

        return $response;
    }

    /**
     * @return void
     */
    private function generateSshKey()
    {
        if(
            false === \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY')
            || false === \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY')
            || false === \Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA')
        ){
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
