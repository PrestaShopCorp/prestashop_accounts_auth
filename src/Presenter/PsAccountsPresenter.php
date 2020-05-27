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
              'user' =>   [
                  email => (Context::getContext())->employee->email,
                  emailIsValidated => false, //Always false, we will know this information only after
                ],
              'currentShop' => $this->getCurrentShop(),
              'shops' => $this->getShopsTree(),

        ];

        dump($presenter);
        die;

        return $presenter;
    }

    public function getCurrentShop()
    {
      if(false === Module::isEnabled('ps_accounts')){
        return \Configuration::get('PS_SHOP_NAME');
      }

      //TODO
      return \Configuration::get('PS_SHOP_NAME');
    }

    public function getOnboardingLink()
    {
      if(false === Module::isEnabled('ps_accounts')){
        return '';
      }
      $uiSvcBaseUrl= 'http://localhost:3000';
      
      return $uiSvcBaseUrl.'/shop/account/link/'.\Tools::getProtocol().'/';

      // ${svcUiDomainName}/shop/account/link/${protocolDomainToValidate}/${domainNameDomainToValidate}/${protocolBo}/${domainNameBo}/PSXEmoji.Deluxe.Fake.Service?
      $module = Module::getInstanceByName('ps_accounts');
      $psAccountscontext = $module->getContext();
      return [
        // 'boUrl' => preg_replace(
        //     '/^https?:\/\/[^\/]+/',
        //     '',
        //     $context->link->getAdminLink('AdminModules', true) . '&configure=' . $module->name
        // ),
        // 'nextStep' => preg_replace(
        //     '/^https?:\/\/[^\/]+/',
        //     '',
        //     $context->link->getAdminLink('AdminConfigureHmacPsAccounts')
        // ),
        'protocolDomainToValidate' => \Tools::getProtocol(),
        'domainNameDomainToValidate' => str_replace(
            \Tools::getProtocol((bool) \Configuration::get('PS_SSL_ENABLED')),
            '',
            \Tools::getShopDomainSsl(true)
        ),
        // 'adminController' => $context->link->getAdminLink('AdminAjaxPsAccounts'),
      ];
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

        $linkAdapter = new LinkAdapter($this->context->link);

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
}
