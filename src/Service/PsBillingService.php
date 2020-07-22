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
use PrestaShop\AccountsAuth\Adapter\LinkAdapter;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Api\ServicesAccountsClient;
use PrestaShop\AccountsAuth\Api\ServicesBillingClient;
use PrestaShop\AccountsAuth\Context\ShopContext;
use PrestaShop\AccountsAuth\Environment\Env;
use PrestaShop\AccountsAuth\Service\PsAccountsService;

/**
 * Construct the psbilling service.
 */
class PsBillingService
{
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
     * @var LinkAdapter
     */
    protected $linkAdapter;

    public function __construct()
    {
        new Env();
        $this->context = Context::getContext();
        $this->shopContext = new ShopContext();
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

    // TODO !0: method implement :
    public function subscribeToFreePlan($planName, $shopId = false) {
        $psAccountsService = new PsAccountsService();
        if ($shopId === false) {
            $shopId = $psAccountsService->getCurrentShop()['id'];
        }

        $uuid = $psAccountsService->getShopUuidV4($shopId);
        $response = false;

        if ($uuid && strlen($uuid) > 0) {
            $response = (new ServicesBillingClient($this->getContext()->link))->getBillingCustomer($uuid);
            // TODO !0: vérifier si le customer existe. Si non, le créer (autre appel).
            // appel pour vérifier si le plan $planName est souscrit chez le customer, et souscrire sinon.
            // renvoyer en sortie un true si tout bon ?
        }

        return $response !== false;
    }
}
