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

namespace PrestaShop\AccountsAuth\Service;

use Context;
use Module;
use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Adapter\LinkAdapter;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Api\ServicesAccountsClient;
use PrestaShop\AccountsAuth\Context\ShopContext;
use PrestaShop\AccountsAuth\Environment\Env;

/**
 * Construct the psaccounts service.
 */
class PsAccountsService
{
    const STR_TO_SIGN = 'data';

    /**
     * @var Module
     */
    public $module;

    /**
     * @var Context
     */
    public $context;

    /**
     * @var ShopContext
     */
    public $shopContext;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var string | null
     */
    public $psxName = null;

    /**
     * @var Configuration
     */
    public $configuration;

    /**
     * @var LinkAdapter
     */
    protected $linkAdapter;

    public function __construct()
    {
        new Env();
        $this->module = Module::getInstanceByName('ps_accounts');
        $this->context = Context::getContext();
        $this->shopContext = new ShopContext();
        $this->linkAdapter = new LinkAdapter($this->context->link);

        $this->configuration = new Configuration();
        //$this->configuration->setIdShop((int) $this->getCurrentShop()['id']);
        $this->configuration->setIdShop($this->context->shop->id);
    }

    /**
     * @return ShopContext
     */
    public function getShopContext()
    {
        return $this->shopContext;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $psxName
     *
     * @return void
     */
    public function setPsxName($psxName)
    {
        $this->psxName = $psxName;
    }

    /**
     * @return string | null
     */
    public function getPsxName()
    {
        return $this->psxName;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function manageOnboarding()
    {
        $currentShop = $this->getCurrentShop();
        $this->generateSshKey($currentShop['id']);
        $this->saveQueriesParams($currentShop['id']);
    }

    /**
     * FIXME : redundant with Token ?
     * @return string | null
     */
    public function getFirebaseRefreshToken()
    {
        return $this->configuration->get('PS_PSX_FIREBASE_REFRESH_TOKEN') ?: null;
    }

    /**
     * FIXME : redundant with Token ?
     * @return string | null
     */
    public function getFirebaseIdToken()
    {
        return $this->configuration->getRaw('PS_PSX_FIREBASE_ID_TOKEN') ?: null;
    }

    /**
     * @return string
     */
    public function getSuperAdminEmail()
    {
        $employee = new \Employee(1);

        return $employee->email;
    }

    /**
     * @return string | null
     */
    public function getPsAccountsInstallLink()
    {
        if (true === Module::isInstalled('ps_accounts')) {
            return null;
        }

        if ($this->shopContext->isShop17()) {
            $router = $this->get('router');

            return substr(\Tools::getShopDomainSsl(true) . __PS_BASE_URI__, 0, -1) . $router->generate('admin_module_manage_action', [
                'action' => 'install',
                'module_name' => 'ps_accounts',
            ]);
        }

        return  $this->linkAdapter->getAdminLink('AdminModules', true, [], [
            'module_name' => $this->psxName,
            'install' => $this->psxName,
        ]);
    }

    /**
     * @return string | null
     */
    public function getPsAccountsEnableLink()
    {
        if (true === Module::isEnabled('ps_accounts')) {
            return null;
        }

        if ($this->shopContext->isShop17()) {
            $router = $this->get('router');

            return substr(\Tools::getShopDomainSsl(true) . __PS_BASE_URI__, 0, -1) . $router->generate('admin_module_manage_action', [
                'action' => 'enable',
                'module_name' => 'ps_accounts',
            ]);
        }

        return  $this->linkAdapter->getAdminLink('AdminModules', true, [], [
            'module_name' => $this->psxName,
            'enable' => '1',
        ]);
    }

    /**
     * @param int $shopId
     *
     * @return string | null
     */
    public function getEmail($shopId)
    {
        return $this->configuration->getRaw(
            'PS_PSX_FIREBASE_EMAIL',
            null,
            null,
            (int) $shopId
        ) ?: null;
    }

    /**
     * @param int $shopId
     *
     * @return bool
     */
    public function isEmailValidated($shopId)
    {
        return in_array($this->configuration->getRaw(
            'PS_PSX_FIREBASE_EMAIL_IS_VERIFIED',
            null,
            null,
            (int) $shopId),
            ['1', 1, true]
        );
    }

    /**
     * @return array
     */
    public function getCurrentShop()
    {
        $shop = \Shop::getShop($this->context->shop->id);

        return [
            'id' => $shop['id_shop'],
            'name' => $shop['name'],
            'domain' => $shop['domain'],
            'domainSsl' => $shop['domain_ssl'],
            'url' => $this->linkAdapter->getAdminLink(
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
     * @param mixed $shopId
     *
     * @return bool
     */
    public function sslEnabled($shopId = false)
    {
        $shopId = $shopId ? $shopId : $this->getCurrentShop()['id'];

        return true == $this->configuration->getRaw(
                'PS_SSL_ENABLED',
                null,
                null,
                (int) $shopId
            );
    }

    /**
     * @param mixed $shopId
     *
     * @return string
     */
    public function getProtocol($shopId = false)
    {
        return false == $this->sslEnabled($shopId) ? 'http' : 'https';
    }

    /**
     * @param mixed $shopId
     *
     * @return string
     */
    public function getDomainName($shopId = false)
    {
        if ($shopId === false) {
            $currentShop = $this->getCurrentShop();

            return false == $this->sslEnabled() ? $currentShop['domain'] : $currentShop['domainSsl'];
        }

        $shop = \Shop::getShop($shopId);

        return false == $this->sslEnabled($shopId) ? $shop['domain'] : $shop['domain_ssl'];
    }

    /**
     * @param int $shopId
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getOnboardingLink($shopId)
    {
        if (false === Module::isInstalled('ps_accounts')) {
            return '';
        }

        $callback = preg_replace(
            '/^https?:\/\/[^\/]+/',
            '',
            $this->linkAdapter->getAdminLink('AdminModules', true) . '&configure=' . $this->psxName
        );

        $uiSvcBaseUrl = $_ENV['ACCOUNTS_SVC_UI_URL'];
        if (false === $uiSvcBaseUrl) {
            throw new \Exception('Environmenrt variable ACCOUNTS_SVC_UI_URL should not be empty');
        }
        $protocol = $this->getProtocol($shopId);
        $domainName = $this->getDomainName($shopId);
        $currentShop = \Shop::getShop($shopId);
        $queryParams = [
            'bo' => $callback,
            'pubKey' => $this->configuration->getRaw(
                'PS_ACCOUNTS_RSA_PUBLIC_KEY',
                null,
                null,
                (int) $shopId
            ),
            'next' => preg_replace(
                '/^https?:\/\/[^\/]+/',
                '',
                $this->linkAdapter->getAdminLink('AdminConfigureHmacPsAccounts')
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
    public function generateSshKey($shopId)
    {
        if (false === $this->hasSshKey($shopId)) {

            $sshKey = new SshKey();

            $key = $sshKey->generate();

            $this->configuration->setRaw('PS_ACCOUNTS_RSA_PRIVATE_KEY', $key['privatekey'], false, null, (int) $shopId);
            $this->configuration->setRaw('PS_ACCOUNTS_RSA_PUBLIC_KEY', $key['publickey'], false, null, (int) $shopId);

            $this->configuration->setRaw(
                'PS_ACCOUNTS_RSA_SIGN_DATA',
                $sshKey->signData(
                    $this->configuration->getRaw('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId),
                    self::STR_TO_SIGN
                ),
                false, null, (int) $shopId
            );
        }
    }

    /**
     * @param int $shopId
     *
     * @return bool
     */
    public function hasSshKey($shopId)
    {
        return false !== $this->configuration->getRaw('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, null, (int) $shopId)
            && !empty($this->configuration->getRaw('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, null, (int) $shopId))
            && false !== $this->configuration->getRaw('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId)
            && !empty($this->configuration->getRaw('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, null, (int) $shopId))
            && false !== $this->configuration->getRaw('PS_ACCOUNTS_RSA_SIGN_DATA', null, null, (int) $shopId)
            && !empty($this->configuration->getRaw('PS_ACCOUNTS_RSA_SIGN_DATA', null, null, (int) $shopId));
    }

    /**
     * @return bool
     */
    public function isShopContext()
    {
        if (\Shop::isFeatureActive() && \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getShopsTree()
    {
        $shopList = [];

        if (true === $this->isShopContext()) {
            return $shopList;
        }

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $shops[] = [
                    'id' => $shopId,
                    'name' => $shopData['name'],
                    'domain' => $shopData['domain'],
                    'domainSsl' => $shopData['domain_ssl'],
                    'url' => $this->linkAdapter->getAdminLink(
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
     * Prepare onboarding data
     *
     * @param int $shopId
     *
     * @return void
     *
     * @throws \Exception
     */
    public function saveQueriesParams($shopId)
    {
        if (false === $this->hasSshKey($shopId)) {
            return;
        }

        if (! $this->storeIdAndRefreshToken(
            \Tools::getValue('adminToken'),
            $shopId)
        ) {
            return;
        }

        $this->storeEmailVerifiedStatus(
            \Tools::getValue('email'),
            \Tools::getValue('emailVerified'),
            $shopId
        );

        // refresh token : WHY ?
        //(new Token())->refresh($shopId);
    }

    /**
     * @param mixed $shopId
     *
     * @return string | false
     */
    public function getShopUuidV4($shopId = false)
    {
        return $this->configuration->getRaw('PSX_UUID_V4', null, null, (int) ($shopId ? $shopId : $this->getCurrentShop()['id']));
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

    /**
     * @param array $bodyHttp
     * @param string $trigger
     *
     * @return mixed
     */
    public function changeUrl($bodyHttp, $trigger)
    {
        $shopId = array_key_exists('shop_id', $bodyHttp) ? $bodyHttp['shop_id'] : $this->getCurrentShop()['id']; // id for multishop
        $sslEnabled = $this->sslEnabled($shopId);
        $protocol = $this->getProtocol($shopId);
        $domain = $sslEnabled ? $bodyHttp['domain_ssl'] : $bodyHttp['domain'];
        $uuid = $this->getShopUuidV4($shopId);
        $response = false;
        $boUrl = preg_replace(
             '/^https?:\/\/[^\/]+/',
             $protocol . '://' . $domain,
             $this->linkAdapter->getAdminLink('AdminModules', true)
         );

        if ($uuid && strlen($uuid) > 0) {
            $response = (new ServicesAccountsClient($this->getContext()->link))->fetch(
                $uuid,
                [
                    'protocol' => $protocol,
                    'domain' => $domain,
                    'boUrl' => $boUrl,
                    'trigger' => $trigger,
                ]
            );
        }

        return $response;
    }

    /**
     * @param string $customToken
     * @param int $shopId
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function storeIdAndRefreshToken($customToken, $shopId)
    {
        error_log('########################## storeIdAndRefreshToken ' . $customToken . ' ' . $shopId);
        return (new Token())->exchangeCustomTokenForIdAndRefreshToken($customToken, $shopId);
    }

    /**
     * @param string $email
     * @param bool $status
     * @param $shopId
     */
    protected function storeEmailVerifiedStatus($email, $status, $shopId)
    {
        error_log('########################## storeEmailVerifiedStatus ' . $email . ' ' . $status);
        $this->configuration->setIdShop((int) $shopId);

        if (null !== $email && !empty($email)) {

            $this->configuration->set('PS_PSX_FIREBASE_EMAIL', $email);

            if (null !== $status && !empty($status)) {

                $this->configuration->set('PS_PSX_FIREBASE_EMAIL_IS_VERIFIED', 'true' === $status);
            }
        }
    }
}
