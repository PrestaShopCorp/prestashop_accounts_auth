# prestashop_accounts_auth

Firebase auth class

## How install
```
composer require prestashop/prestashop-accounts-auth
```

Add

```
"minimum-stability": "dev"
```
in your `composer.json`


### Testing

```bash
docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan.neon
```
