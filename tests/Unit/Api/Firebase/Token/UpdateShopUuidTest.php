<?php

namespace PrestaShop\AccountsAuth\Tests\Unit\Api\Firebase\Token;

use PrestaShop\AccountsAuth\Adapter\Configuration;
use PrestaShop\AccountsAuth\Api\Firebase\Token;
use PrestaShop\AccountsAuth\Tests\TestCase;

// FIXME : use faker->uuid
// FIXME : use Lcobucci\JWT\Token
// FIXME : configuration default params issue
class UpdateShopUuidTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_update_shop_configuration()
    {
        $shopId = 5;

        $uid = '8f4IQIOlE4UpIgch5nLax9SPeA43';
        $customToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJodHRwczovL2lkZW50aXR5dG9vbGtpdC5nb29nbGVhcGlzLmNvbS9nb29nbGUuaWRlbnRpdHkuaWRlbnRpdHl0b29sa2l0LnYxLklkZW50aXR5VG9vbGtpdCIsImlhdCI6MTU5NDk5Mzc1NywiZXhwIjoxNTk0OTk3MzU3LCJpc3MiOiJmaXJlYmFzZS1hZG1pbnNkay10ZHZ0cUBwcmVzdGFzaG9wLXJlYWR5LWludGVncmF0aW9uLmlhbS5nc2VydmljZWFjY291bnQuY29tIiwic3ViIjoiZmlyZWJhc2UtYWRtaW5zZGstdGR2dHFAcHJlc3Rhc2hvcC1yZWFkeS1pbnRlZ3JhdGlvbi5pYW0uZ3NlcnZpY2VhY2NvdW50LmNvbSIsInVpZCI6IjhmNElRSU9sRTRVcElnY2g1bkxheDlTUGVBNDMifQ.C-klIXP5GknviWGlnIfdNcO3b-ANtRmpGw7LHl3km-vNUXLxI_uo8qfscxY6nBFjxpN1FPvzvpQJnpFWLgsRxtedoFrcITKRbhk0qkV42RrJR4LlL77h_MuWbkf7kQnFa7nAsYXbbSfUqbNVUq-ETbD8nX_DdQws8l7aIsdt7Xh63aza1YO4RmbSIqH7D2oitSMr6rpyPg50An7zlLNIZyPoZX8c4on1q8FVFsLd7FDpHM6RQBY_Az05OpdynRmEok56oHpqdXoOUP-aX7rW9WArMbngr4XWNWbm5drOptPAYfemYwHTi5N1c8R5kh2TX-1jqqjmNVqwt_c9B0WROw';

        //$configuration = $this->createMock(Configuration::class);

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getRaw', 'setRaw'])
            ->getMock();

        $configuration->method('getRaw')
            ->with('PS_CHECKOUT_SHOP_UUID_V4', null, null, null)
            ->willReturn('some_random_string');

        $configuration->expects($this->exactly(2))
            ->method('setRaw')
            ->withConsecutive(
                ['PS_PSX_FIREBASE_ADMIN_TOKEN', $customToken, false, null, $shopId],
                ['PSX_UUID_V4', $uid, false, null, $shopId]
            );

        $token = new Token($configuration);

        $token->updateShopUuid($customToken, $shopId);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_update_checkout_shop_uid()
    {
        $shopId = 5;

        $uid = '8f4IQIOlE4UpIgch5nLax9SPeA43';
        $customToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJodHRwczovL2lkZW50aXR5dG9vbGtpdC5nb29nbGVhcGlzLmNvbS9nb29nbGUuaWRlbnRpdHkuaWRlbnRpdHl0b29sa2l0LnYxLklkZW50aXR5VG9vbGtpdCIsImlhdCI6MTU5NDk5Mzc1NywiZXhwIjoxNTk0OTk3MzU3LCJpc3MiOiJmaXJlYmFzZS1hZG1pbnNkay10ZHZ0cUBwcmVzdGFzaG9wLXJlYWR5LWludGVncmF0aW9uLmlhbS5nc2VydmljZWFjY291bnQuY29tIiwic3ViIjoiZmlyZWJhc2UtYWRtaW5zZGstdGR2dHFAcHJlc3Rhc2hvcC1yZWFkeS1pbnRlZ3JhdGlvbi5pYW0uZ3NlcnZpY2VhY2NvdW50LmNvbSIsInVpZCI6IjhmNElRSU9sRTRVcElnY2g1bkxheDlTUGVBNDMifQ.C-klIXP5GknviWGlnIfdNcO3b-ANtRmpGw7LHl3km-vNUXLxI_uo8qfscxY6nBFjxpN1FPvzvpQJnpFWLgsRxtedoFrcITKRbhk0qkV42RrJR4LlL77h_MuWbkf7kQnFa7nAsYXbbSfUqbNVUq-ETbD8nX_DdQws8l7aIsdt7Xh63aza1YO4RmbSIqH7D2oitSMr6rpyPg50An7zlLNIZyPoZX8c4on1q8FVFsLd7FDpHM6RQBY_Az05OpdynRmEok56oHpqdXoOUP-aX7rW9WArMbngr4XWNWbm5drOptPAYfemYwHTi5N1c8R5kh2TX-1jqqjmNVqwt_c9B0WROw';

        //$configuration = $this->createMock(Configuration::class);

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setMethods(['getRaw', 'setRaw'])
            ->getMock();

        $configuration->method('getRaw')
            ->with('PS_CHECKOUT_SHOP_UUID_V4', null, null, null)
            ->willReturn(false);

        $configuration->expects($this->exactly(3))
            ->method('setRaw')
            ->withConsecutive(
                ['PS_CHECKOUT_SHOP_UUID_V4', $uid, false, null, $shopId],
                ['PS_PSX_FIREBASE_ADMIN_TOKEN', $customToken, false, null, $shopId],
                ['PSX_UUID_V4', $uid, false, null, $shopId]
            );

        $token = new Token($configuration);

        $token->updateShopUuid($customToken, $shopId);
    }
}
