# prestashop_accounts_auth

## How install
```
composer require prestashop/prestashop-accounts-auth
```

In your PSX/AOS :


- In the PSX / AOS module controllers, get onboarding presenter and go to the view for which is used by the
[viewsjs component](https://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

```php
$onboarding = new PrestaShop\AccountsAuth\Processor\Onboarding();
Media::addJsDef([
    'store' => $onboarding->present(),
]);
```

### Testing

Run phpstan

```bash
docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan.neon
```

Run php-cs-fixer
```bash
php vendor/bin/php-cs-fixer fix
```
