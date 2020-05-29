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

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
{
    /**
     * Present the PsAccounts module for vue.
     *
     * @return array
     */
    public function present()
    {
        $presenter = [
          'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
          'psAccountIsEnabled' => Module::isEnabled('ps_accounts'),
          'onboardingLink' => $this->getOnboardingLink(),
          'user' => [
              'email' => Context::getContext()->employee->email,
              'emailIsValidated' => false, //Always false, we will know this information only after
            ],
          'currentShop' => $this->getCurrentShop(),
          'shops' => $this->getShopsTree(),
        ];
        dump($presenter);
        exit;

        return $presenter;
    }

    /**
     * @return string
     */
    public function getCurrentShop()
    {
        if (false === Module::isEnabled('ps_accounts')) {
            return \Configuration::get('PS_SHOP_NAME');
        }

        //TODO
        return \Configuration::get('PS_SHOP_NAME');
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getDomainName()
    {
        return str_replace(
        \Tools::getProtocol(\Configuration::get('PS_SSL_ENABLED')),
        '',
        \Tools::getShopDomainSsl()
      );
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

        $uiSvcBaseUrl = 'http://localhost:3000';
        $protocol = $this->getProtocol();
        $domainName = $this->getDomainName();

        $queryParams = [
        'bo' => 'qwqwq',
        'pubKey' => \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY'),
        'next' => 'dfdfdf',
        'lang' => $context->language,
      ];

        $response = $uiSvcBaseUrl . '/shop/account/link/' . $protocol . '/' . $domainName . '?' . http_build_query($queryParams);
        //$response = 'https://accounts.psessentials-integration.net/shop/account/link/http/shop-accounts.services-integration.prestashop.net/http/shop-accounts.services-integration.prestashop.net/PSXEmoji.Deluxe.Fake.Service?bo=%2Fps-admin%2Findex.php%3Fcontroller%3DAdminModules%26token%3D3fbfa85a028b6b43f48fa51dbae785e5%26configure%3Dps_accounts&pubKey=-----BEGIN%20RSA%20PUBLIC%20KEY-----%0D%0AMIGJAoGBANsxeyXITCOJKhMRm1PGZ%2BxmB%2Bod34fbpTdf1vHsS4044NLzM0Z0jxLi%0D%0AfUwReMA9Um%2Btk1agBkrHiY4AicHOdPkQqpQLe5WUJtd9yiVytUx8pvkMEWg9vYlI%0D%0AVpotQgBHI2z8hK56uMHZq2CnX5JCaN0Xi6cZCc867Xf23YPx%2BFGzAgMBAAE%3D%0D%0A-----END%20RSA%20PUBLIC%20KEY-----&name=PrestaShop&next=%2Fps-admin%2Findex.php%3Fcontroller%3DAdminConfigureHmacPsAccounts%26token%3D9fdb6078d71e10b5809b08bc812146d4&lang=fr-FR';
        // ${svcUiDomainName}/shop/account/link/${protocolDomainToValidate}/${domainNameDomainToValidate}/${protocolBo}/${domainNameBo}/PSXEmoji.Deluxe.Fake.Service?

        return http_build_query($queryParams);
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
