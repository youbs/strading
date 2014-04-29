<?php
// This is the example used in the documentation.

require __DIR__ . '/../../vendor/autoload.php';

$service = new \Gajus\Strading\Service('test_github53934', 'api@anuary.com', '93gbjdMR');

$auth = $service->request('card/auth');

$auth->populate([
    'amount' => 100,
    'amount[currencycode]' => 'GBP',
    'email' => 'foo@bar.baz',
    'name' => [
        'first' => 'Foo',
        'last' => 'Bar'
    ],
    'payment' => [
        'pan' => '4111110000000211',
        'securitycode' => '123',
        'expirydate' => '10/2031'
    ],
    'payment[type]' => 'VISA'
],'/requestblock/request/billing');

var_dump($auth->getXML());

$auth->populate([
    'merchant' => [
        'orderreference' => 'gajus-0000001'
    ],
    'customer' => [
        'name' => [
                'first' => 'Foo',
                'last' => 'Bar'
            ],
        'email' => 'foo@bar.baz'
    ]
], '/requestblock/request');

var_dump($auth->getXML());

var_dump($auth->request());