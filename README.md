# prestashop_accounts_auth

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
