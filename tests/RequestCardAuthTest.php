<?php
class RequestCardAuthTest extends PHPUnit_Framework_TestCase {
    private
        $credentials,
        $service,
        $order_reference;

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

        $auth = $this->service->request('card/auth');

        $auth->populate(array(
            'billing' => array(
                'amount' => 100,
                'amount[currencycode]' => 'GBP',
                'email' => 'foo@bar.baz',
                'name' => array(
                    'first' => 'Foo',
                    'last' => 'Bar'
                ),
                'payment' => array(
                    'pan' => '4111110000000211',
                    'securitycode' => '123',
                    'expirydate' => '10/2031'
                ),
                'payment[type]' => 'VISA'
            ),
            'merchant' => array(
                'orderreference' => $order_reference
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

        $transaction = $response->getTransaction();

        $this->assertNotNull($transaction, 'Card Auth transaction cannot be NULL.');

        $this->assertNotNull($transaction['request_reference'], 'Card Auth transaction must resolve "request_reference".');
        $this->assertNotNull($transaction['transaction_type'], 'Card Auth transaction must resolve "transaction_type".');
        $this->assertNotNull($transaction['transaction_reference'], 'Card Auth transaction must resolve "transaction_reference".');
        $this->assertNotNull($transaction['timestamp'], 'Card Auth transaction must resolve "timestamp".');
        $this->assertNull($transaction['parent_transaction_reference'], 'Card Auth transaction must not resolve "parent_transaction_reference".');
        $this->assertNotNull($transaction['authcode'], 'Card Auth transaction must resolve "authcode".');
        $this->assertNotNull($transaction['amount'], 'Card Auth transaction must resolve "amount.');
        $this->assertNull($transaction['paypal_token'], 'Card Auth transaction must not resolve "paypal_token".');
        
        $this->assertCount(8, $transaction, 'Transaction must consist of 8 entities.');
        
        $this->assertSame('VISA', $transaction['transaction_type'], '"transaction_type" must be "VISA"');

        // Valid "card/auth" must not produce an error.
        $this->assertNull($response->getError());

        // "card/auth" must not redirect user.
        $this->assertNull($response->getRedirectUrl());
    }

    public function testMakeRequestWithError () {
        $auth = $this->service->request('card/auth');

        $auth->populate(array(
            'billing' => array(
                'amount' => 100,
                'amount[currencycode]' => 'XXX', // Invalid currency.
                'email' => 'foo@bar.baz',
                'name' => array(
                    'first' => 'Foo',
                    'last' => 'Bar'
                ),
                'payment' => array(
                    'pan' => '4111110000000211',
                    'securitycode' => '123',
                    'expirydate' => '10/2031'
                ),
                'payment[type]' => 'VISA'
            ),
            'merchant' => array(
                'orderreference' => $this->order_reference
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

        // All requests have the generic information available.
        $this->assertNotNull($response->getTransaction());

        $error = $response->getError();

        $this->assertInstanceOf('Gajus\Strading\Error', $error);
        $this->assertSame('30000', $error->getCode());
        $this->assertSame('Invalid field', $error->getMessage());
        $this->assertSame('currencyiso3a', $error->getData());

        // "card/auth" must not redirect user.
        $this->assertNull($response->getRedirectUrl());
    }
}