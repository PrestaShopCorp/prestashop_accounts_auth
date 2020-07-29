<?php

namespace PrestaShop\AccountsAuth\Tests;

if (! defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', '');
}

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
}
