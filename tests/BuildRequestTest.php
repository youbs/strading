<?php
class BuildRequestTest extends PHPUnit_Framework_TestCase {
    private
        $credentials,
        $service;

    public function setUp () {
        if (file_exists(__DIR__ . '/config.php')) {
            $this->credentials = require __DIR__ . '/config.php';
        } else {
            $this->credentials = ['site_reference' => 'foo', 'username' => 'bar', 'password' => 'baz'];
        }

        $this->service = new \gajus\strading\Service($this->credentials['site_reference'], $this->credentials['username'], $this->credentials['password']);
    }

    private function loadXML ($test_name) {
        $placeholders = array_map(function ($name) { return '{' . $name . '}'; }, array_keys($this->credentials));
        
        return str_replace($placeholders, array_values($this->credentials), file_get_contents(__DIR__ . '/xml/' . $test_name . '.xml'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Request template does not exist.
     */
    public function testLoadNotExistingTemplate () {
        $this->service->request('does/not/exist');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage /foo path does not refer to an existing element.
     */
    public function testPopulateNotExistingTag () {
        $auth = $this->service->request('card/auth');

        $auth->populate([
            'foo' => 'bar'
        ]);
    }

    public function testRemoveEmptyTags () {
        $order = $this->service->request('paypal/order');

        $this->assertSame($this->loadXML('build_request_test_remove_empty_tags'), $order->getRequestXML());
    }

    public function testSetAttribute () {
        $transactionquery = $this->service->request('transactionquery');

        $transactionquery->populate([
            'requestblock' => [
                'request' => [
                    'filter[foo]' => 'bar'
                ]
            ]
        ]);

        $this->assertSame($this->loadXML('build_request_test_set_attribute'), $transactionquery->getRequestXML());
    }

    public function testSetAttributeUsingNamespace () {
        $transactionquery = $this->service->request('transactionquery');

        $transactionquery->populate([
            'filter[foo]' => 'bar'
        ], '/requestblock/request');

        $this->assertSame($this->loadXML('build_request_test_set_attribute'), $transactionquery->getRequestXML());
    }

    public function testPopulateExistingTemplate () {
        $auth = $this->service->request('card/auth');

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

        $this->assertSame($this->loadXML('build_request_test_populate_existing_template'), $auth->getRequestXML());
    }

    public function testTransactionQuerySiteReferenceInFilter () {
        $transactionquery = $this->service->request('transactionquery');

        $this->assertSame($this->loadXML('build_request_test_transaction_query_site_reference_in_filter'), $transactionquery->getRequestXML());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Cannot use the same Request instance for multiple requests.
     */
    public function testRequestAfterRequest () {
        if ($this->credentials['site_reference'] === 'foo') {
            $this->markTestSkipped('Skipped until Secure Trading provides API credentials for testing.');
        }

        $auth = $this->service->request('card/auth');

        $auth->request();
        $auth->request();
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Cannot populate data after request.
     */
    public function testPopulateRequestAfterRequest () {
        if ($this->credentials['site_reference'] === 'foo') {
            $this->markTestSkipped('Skipped until Secure Trading provides API credentials for testing.');
        }

        $auth = $this->service->request('card/auth');

        $auth->request();

        $auth->populate([
            'payment' => [
                'pan' => '4111110000000211',
                'securitycode' => '123',
                'expirydate' => '10/2031'
            ]
        ],'/requestblock/request/billing');
    }
}