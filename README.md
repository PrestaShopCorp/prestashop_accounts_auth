# prestashop_accounts_auth

## AOS and comunity module

An AOS module needs three parts:

### [module ps_accounts](http://github.com/PrestaShopCorp/ps_accounts)

* Contains all the controllers

### [librairie npm](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

* Contains all the vuejs components to manage onboarding

### [librairie composer](http://github.com/PrestaShopCorp/prestashop_accounts_auth)

* Wrappe all the call to ps_accounts
* Contains all the firebase's logic

## Installation

```bash
composer require prestashop/prestashop-accounts-auth
```

## Usage

In your PSX/AOS :

- In the PSX / AOS module  main controller, get onboarding presenter and go to the view for which is used by the
[viewsjs component](https://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

```php

$psAccountPresenter = new PrestaShop\AccountsAuth\Presenter\PsAccountsPresenter($this->name);

Media::addJsDef([
    'contextPsAccounts' => $psAccountPresenter->present(),
]);
```

The $psAccountPresenter format is :
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
    'shops' => $this->getShopsTree(),
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
