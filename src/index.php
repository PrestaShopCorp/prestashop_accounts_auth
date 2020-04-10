<?php

namespace PrestaShop\AccountsAuth;

class Say
{
    public function hello($name = "anonymous")
    {
        return 'hello '.$name;
    }
}
