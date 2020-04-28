# prestashop_accounts_auth

## How install
```
composer require prestashop/prestashop-accounts-auth
```

In your PSX/AOS two way:


- In the PSX / AOS module controllers, get onboarding presenter and go to the view for which is used by the
[viewsjs component](https://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

```php
$onboarding = new PrestaShop\AccountsAuth\Processor\Onboarding();
Media::addJsDef([
    'store' => $onboarding->getPresenter(),
]);
```


- In the PSX / AOS module controllers, call the function with return params. In this fact, the function do all the onboarding process and redirect after on your return params.
```php
$onboarding = new PrestaShop\AccountsAuth\Processor\Onboarding();
$onboarding->process('AdminMyController');
```

### Testing

Run phpstan

```bash
docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan.neon
```
