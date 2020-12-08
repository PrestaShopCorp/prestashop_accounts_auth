<?php
/**
* 2007-2020 PrestaShop and Contributors
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

namespace PrestaShop\AccountsAuth\Installer;

use PrestaShop\AccountsAuth\Context\ShopContext;
use PrestaShop\AccountsAuth\Exception\ServiceNotFoundException;
use PrestaShop\AccountsAuth\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManager;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

/**
 * Install ps_accounts module
 */
class Install
{
    const PS_ACCOUNTS = 'ps_accounts';
    const PS_EVENTBUS = 'ps_eventbus';

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * Install constructor.
     */
    public function __construct()
    {
        $this->shopContext = new ShopContext();

        $this->moduleManager = ModuleManagerBuilder::getInstance()->build();
    }

    /**
     * @param $moduleName
     * @param bool $upgrade
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function installModule($moduleName, $upgrade = true)
    {
        if (true === $this->shopContext->isShop17()) {
            return true;
        }

        if (false === $upgrade && true === $this->moduleManager->isInstalled($moduleName)) {
            return true;
        }

        // install or upgrade module
        $moduleIsInstalled = $this->moduleManager->install($moduleName);

        if (false === $moduleIsInstalled) {
            throw new \Exception("Module ${moduleName} can't be installed", 500);
        }

        return $moduleIsInstalled;
    }

    /**
     * @param bool $upgrade
     * @return bool
     *
     * @throws \Exception
     */
    public function installDependencies($upgrade = true)
    {
        return $this->installModule(self::PS_ACCOUNTS, $upgrade)
            && $this->installModule(self::PS_EVENTBUS, $upgrade);

    }

    /**
     * @return bool
     *
     * @throws ServiceNotFoundException
     * @throws \Raven_Exception
     * @throws \Exception
     */
    public function installPsAccounts()
    {
        try {
            return $this->installModule(self::PS_ACCOUNTS, false);
        } catch (\Exception $e) {
            ErrorHandler::getInstance()->handle($e, 500);
            return true;
        }
    }
}
