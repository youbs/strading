<?php
class BuildRequestTest extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadNotExistingTemplate () {
        $service = new \gajus\strading\Service('site_reference', 'username', 'password');

        $service->request('does/not/exist');
    }

    public function testRemoveEmptyTags () {
        $service = new \gajus\strading\Service('site_reference', 'username', 'password');

        $order = $service->request('paypal/order');

        $this->assertSame(file_get_contents(__DIR__ . '/xml/build_request_test_remove_empty_tags.xml'), $order->getRequestXML());
    }

    public function testPopulateExistingTemplate () {
        $service = new \gajus\strading\Service('site_reference', 'username', 'password');

        $auth = $service->request('card/auth');

        $auth->populate([
            'payment' => [
                'pan' => '4111110000000211',
                'securitycode' => '123',
                'expirydate' => '10/2031'
            ],
            'payment[type]' => 'VISA'
        ],'/requestblock/request/billing');

        $auth->populate([
            'billing' => [
                'amount' => 100,
                'amount[currencycode]' => 'GBP',
                'email' => 'dummy@gajus.com',
                'name' => [
                    'first' => 'Gajus',
                    'last' => 'Kuizinas'
                ]
            ],
            'merchant' => [
                'orderreference' => 'gajus-0000001'
            ],
            'customer' => [
                'name' => [
                        'first' => 'Gajus',
                        'last' => 'Kuizinas'
                    ],
                'email' => 'dummy@gajus.com'
            ]
        ], '/requestblock/request');

        $this->assertSame(file_get_contents(__DIR__ . '/xml/build_request_test_populate_existing_template.xml'), $auth->getRequestXML());
    }

    public function testTransactionQuerySiteReferenceInFilter () {
        $service = new \gajus\strading\Service('site_reference', 'username', 'password');

        $transactionquery = $service->request('transactionquery');

        $this->assertSame(file_get_contents(__DIR__ . '/xml/build_request_test_transaction_query_site_reference_in_filter.xml'), $transactionquery->getRequestXML());
    }
}