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

namespace Tests\Unit\Token;

if (!defined('_PS_MODULE_DIR')) {
    define('_PS_MODULE_DIR_', '');
}

use PHPUnit\Framework\TestCase;
use PrestaShop\AccountsAuth\Api\Firebase\Token;

class TokenTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_decode_token()
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJodHRwczovL2lkZW50aXR5dG9vbGtpdC5nb29nbGVhcGlzLmNvbS9nb29nbGUuaWRlbnRpdHkuaWRlbnRpdHl0b29sa2l0LnYxLklkZW50aXR5VG9vbGtpdCIsImlhdCI6MTU5NDk5Mzc1NywiZXhwIjoxNTk0OTk3MzU3LCJpc3MiOiJmaXJlYmFzZS1hZG1pbnNkay10ZHZ0cUBwcmVzdGFzaG9wLXJlYWR5LWludGVncmF0aW9uLmlhbS5nc2VydmljZWFjY291bnQuY29tIiwic3ViIjoiZmlyZWJhc2UtYWRtaW5zZGstdGR2dHFAcHJlc3Rhc2hvcC1yZWFkeS1pbnRlZ3JhdGlvbi5pYW0uZ3NlcnZpY2VhY2NvdW50LmNvbSIsInVpZCI6IjhmNElRSU9sRTRVcElnY2g1bkxheDlTUGVBNDMifQ.C-klIXP5GknviWGlnIfdNcO3b-ANtRmpGw7LHl3km-vNUXLxI_uo8qfscxY6nBFjxpN1FPvzvpQJnpFWLgsRxtedoFrcITKRbhk0qkV42RrJR4LlL77h_MuWbkf7kQnFa7nAsYXbbSfUqbNVUq-ETbD8nX_DdQws8l7aIsdt7Xh63aza1YO4RmbSIqH7D2oitSMr6rpyPg50An7zlLNIZyPoZX8c4on1q8FVFsLd7FDpHM6RQBY_Az05OpdynRmEok56oHpqdXoOUP-aX7rW9WArMbngr4XWNWbm5drOptPAYfemYwHTi5N1c8R5kh2TX-1jqqjmNVqwt_c9B0WROw';

        $token = (new Token())->parseJwt($jwt);

        $this->assertEquals('8f4IQIOlE4UpIgch5nLax9SPeA43', $token->getClaim('uid'));
    }

    /**
     * @test
     */
    public function it_should_fail_to_decode()
    {
        $this->expectException(\InvalidArgumentException::class);

        $jwt = 'foo.bar';

        $token = (new Token())->parseJwt($jwt);
    }
}
