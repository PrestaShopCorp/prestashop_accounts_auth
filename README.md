# prestashop_accounts_auth

## Community Service & PrestaShop X modules

To work as a Community Service or as PrestaShop X, a module needs three parts:

### [module ps_accounts](http://github.com/PrestaShopCorp/ps_accounts)

* Contains all the controllers

### [librairy npm](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

* Contains all the vuejs components to manage onboarding

### [librairie composer](http://github.com/PrestaShopCorp/prestashop_accounts_auth)

* Wraps all the calls to ps_accounts
* Contains all the Firebase logic

## Installation

```bash
composer require prestashop/prestashop-accounts-auth
```

## Usage

Each PrestaShop X modules require that the module ps_accounts is installed in order to precess to the onboarding.
PrestaShop X modules need to install ps_accounts in their install() method. In order to simplify that we have created a method that handle it for you: 

```php
(new PrestaShop\AccountsAuth\Installer\Install())->installPsAccounts()
```

eg: You need to call the method above in the install() method in the main class of your module:

```php
/**
 * Function executed at the install of the module
 *
 * @return bool
 */
public function install()
{
  return (new PrestaShop\AccountsAuth\Installer\Install())->installPsAccounts() &&
    parent::install();
}
```

In your PrestaShop X or Community Service module:

- In the module's controllers and/or main class, get onboarding presenter and go to the view used by the
[viewsjs component](https://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

```php

$psAccountPresenter = new PrestaShop\AccountsAuth\Presenter\PsAccountsPresenter($this->name);

Media::addJsDef([
    'contextPsAccounts' => $psAccountPresenter->present(),
]);
```

The $psAccountPresenter format is:
```php
[
    'psIs17' => bool,
    'psAccountsInstallLink' => null|string,
    'psAccountsEnableLink' => null|string,
    'psAccountsIsInstalled' => bool,
    'psAccountsIsEnabled' => bool,
    'onboardingLink' => string,
    'user' => [
        'email' => null|string,
        'emailIsValidated' => bool,
        'isSuperAdmin' => bool,
    ],
    'currentShop' =>  [
        'id' => string,
        'name' => string,
        'domain' => string,
        'domainSsl' => string,
        'url' => string,
    ],
    'shops' => [],
    'firebaseRefreshToken' => null|string,
    'superAdminEmail' => string,
    'ssoResendVerificationEmail' => string,
];
```

## Testing

Run php-cs-fixer
```bash
php vendor/bin/php-cs-fixer fix
```

Run phpstan for prestashop 1.6.1.0

```bash
git@github.com:PrestaShopCorp/prestashop_accounts_auth.git path/to/clone

docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop:1.6.1.0;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -v path/to/clone:/web/ps_accounts -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan-PS-1.6.neon
```

Run phpstan for prestashop 1.7.0.3

```bash
git@github.com:PrestaShopCorp/prestashop_accounts_auth.git path/to/clone

docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop:1.7.0.3;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -v path/to/clone:/web/ps_accounts -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan-PS-1.7.neon
```
