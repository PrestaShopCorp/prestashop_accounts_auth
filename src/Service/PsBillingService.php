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
use PrestaShop\AccountsAuth\Adapter\LinkAdapter;
use PrestaShop\AccountsAuth\Api\ServicesBillingClient;
use PrestaShop\AccountsAuth\Context\ShopContext;
use PrestaShop\AccountsAuth\Environment\Env;

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

    /**
     * @param string $planName
     * @param mixed $shopId
     *
     * @return mixed An array with subscription identifiers if succeed
     *
     * @throws Exception in case of error
     */
    public function subscribeToFreePlan($module, $planName, $shopId = false, $customerIp = null)
    {
        $psAccountsService = new PsAccountsService();
        if ($shopId === false) {
            $shopId = $psAccountsService->getCurrentShop()['id'];
        }

        $uuid = $psAccountsService->getShopUuidV4($shopId);
        $toReturn = ['shopAccountId' => $uuid];

        if ($uuid && strlen($uuid) > 0) {
            $billingClient = new ServicesBillingClient($this->getContext()->link);

            $response = $billingClient->getBillingCustomer($uuid);
            if (!$response || !array_key_exists('httpCode', $response)) {
                throw new \Exception('Billing customer request failed.', 50);
            }
            if ($response['httpCode'] === 404) {
                $response = $billingClient->createBillingCustomer(
                    $uuid,
                    $customerIp ? ['created_from_ip' => $customerIp] : []
                );
                if (!$response || !array_key_exists('httpCode', $response) || $response['httpCode'] !== 200) {
                    throw new \Exception('Billing customer creation failed.', 60);
                }
            }
            $toReturn['customerId'] = $response['body']['customer']['id'];

            $response = $billingClient->getBillingSubscriptions($uuid, $module);
            if (!$response || !array_key_exists('httpCode', $response)) {
                throw new \Exception('Billing subscriptions request failed.', 51);
            }

            if ($response['httpCode'] === 404) {
                $response = $billingClient->createBillingSubscriptions($uuid, ['plan_id' => $planName, 'module' => $module]);
                if (!$response || !array_key_exists('httpCode', $response) || $response['httpCode'] >= 400) {
                    if (array_key_exists('body', $response)
                        && array_key_exists('message', $response['body'])
                        && array_key_exists(0, $response['body']['message'])) {
                        throw new \Exception($response['body']['message'][0]);
                    }
                    throw new \Exception('Billing subscription creation failed.', 65);
                }

                $toReturn['subscriptionId'] = $response['body']['subscription']['id'];

                return $toReturn;
            } else {
                // There is existing subscription. Testing if planName matches the right one.
                if (array_key_exists('body', $response) && $response['body']
                    && array_key_exists('subscription', $response['body'])
                    && array_key_exists('plan_id', $response['body']['subscription'])
                    && $response['body']['subscription']['plan_id'] === $planName) {
                    $toReturn['subscriptionId'] = $response['body']['subscription']['id'];

                    return $toReturn;
                } else {
                    throw new \Exception('Subscription plan name mismatch.', 20);
                }
            }
        }

        throw new \Exception('Shop account unknown.', 10);
    }
}
