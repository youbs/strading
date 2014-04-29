<?php
class RequestPayPalOrderTest extends PHPUnit_Framework_TestCase {
    private
        $credentials,
        $service;

    public function setUp () {
        $this->credentials = array(
            'site_reference' => 'test_github53934',
            'username' => 'api@anuary.com',
            'password' => '93gbjdMR'
        );

        $this->service = new \Gajus\Strading\Service($this->credentials['site_reference'], $this->credentials['username'], $this->credentials['password']);
    }

    private function loadXML ($test_name, array $replace = array()) {
        $xml = file_get_contents(__DIR__ . '/xml/' . $test_name . '.xml');

        $placeholders = array_map(function ($name) { return '{' . $name . '}'; }, array_keys($this->credentials));
        $xml = str_replace($placeholders, $this->credentials, $xml);
        
        $placeholders = array_map(function ($name) { return '{' . $name . '}'; }, array_keys($replace));
        $xml = str_replace($placeholders, $replace, $xml);

        return $xml;
    }

    /**
     * Remove all text nodes. Used to compare structure of the XML documents.
     */
    private function normaliseXML ($xml) {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//*[not(*) and text()]') as $node) {
            $node->nodeValue = '';
            $node->removeChild($node->firstChild);
        }

        return $dom->saveXML();
    }

    public function testMakeRequest () {
        $factory = new \RandomLib\Factory;
        $generator = $factory->getGenerator(new \SecurityLib\Strength(\SecurityLib\Strength::MEDIUM));

        $order_reference = $generator->generateString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

        $auth = $this->service->request('paypal/order');

        $auth->populate(array(
            'billing' => array(
                'amount' => 100,
                'amount[currencycode]' => 'GBP',
                'email' => 'foo@bar.baz',
                'name' => array(
                    'first' => 'Foo',
                    'last' => 'Bar'
                )
            ),
            'merchant' => array(
                'orderreference' => $order_reference,
                'returnurl' => 'http://gajus.com/',
                'cancelurl' => 'http://gajus.com/'
            ),
            'customer' => array(
                'name' => array(
                        'first' => 'Foo',
                        'last' => 'Bar'
                    ),
                'email' => 'foo@bar.baz'
            )
        ), '/requestblock/request');

        $response = $auth->request();

        $response_xml = $this->normaliseXML($response->getXML()->asXML());

        $this->assertXmlStringEqualsXmlString($this->normaliseXML($this->loadXML('request_paypal_order/test_make_request')), $response_xml);

        $transaction = $response->getTransaction();

        $this->assertNotNull($transaction['request_reference'], 'PayPal order transaction must resolve "request_reference".');
        $this->assertNotNull($transaction['transaction_type'], 'PayPal order transaction must resolve "transaction_type".');
        $this->assertNotNull($transaction['transaction_reference'], 'PayPal order transaction must resolve "transaction_reference".');
        $this->assertNotNull($transaction['timestamp'], 'PayPal order transaction must resolve "timestamp".');
        $this->assertNull($transaction['parent_transaction_reference'], 'PayPal order transaction must not resolve "parent_transaction_reference".');
        $this->assertNull($transaction['authcode'], 'PayPal order transaction must not resolve "authcode".');
        $this->assertNull($transaction['amount'], 'PayPal order transaction must not resolve "amount.');
        $this->assertNotNull($transaction['paypal_token'], 'PayPal order transaction must resolve "paypal_token".');
        
        $this->assertCount(8, $transaction, 'Transaction must consist of 8 entities.');
        
        $this->assertSame('PAYPAL', $transaction['transaction_type'], '"transaction_type" must be "PAYPAL"');
        
        $this->assertNull($response->getError(), 'Valid "paypal/order" must not produce an error.');
        
        $this->assertNotNull($response->getRedirectUrl(), '"paypal/order" must redirect user.');
    }
}