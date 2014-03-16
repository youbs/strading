# Strading

[![Build Status](https://travis-ci.org/gajus/strading.png?branch=master)](https://travis-ci.org/gajus/strading)
[![Coverage Status](https://coveralls.io/repos/gajus/strading/badge.png)](https://coveralls.io/r/gajus/strading)

Strading is Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) API interface. The current implementation is handling card and payment transactions. Though, it can be easily extended to use any other API interface.

## Documentation

Your primary resource for documentation remains Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) documentation. Strading provides merely authentication and convenience method `populate` to populate those large request XML templates.

### Request

The following example illustrates how you would make card authorisatin. The API itself is documented under [XML Specification](http://www.securetrading.com/wp-content/uploads/2013/07/STPP-XML-Specification2.pdf) document.

```php
$service = new \Gajus\Strading\Service('site_reference', 'username', 'password');

$auth = $service->request('card/auth');

// Populate /requestblock/request/billing elements.
$auth->populate([
    'payment' => [
        'pan' => '4111110000000211',
        'securitycode' => '123',
        'expirydate' => '10/2031'
    ],
    'payment[type]' => 'VISA'
],'/requestblock/request/billing');

// You do not need to mash the entire request into a single populate call.
// Instead, you can populate the same Request instance as many times.
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

// However, overwriting an existing value will throw LogicException.
$auth->populate([
    'pan' => '4111110000000211',
],'/requestblock/request/billing/payment');

echo $auth->getRequestXML();
```

The above code (mind that you proparly handle the LogicException) will generate the following request:

```xml
<?xml version="1.0"?>
<requestblock version="3.67">
  <alias>username</alias>
  <request type="AUTH">
    <merchant>
      <orderreference>gajus-0000001</orderreference>
    </merchant>
    <customer>
      <email>dummy@gajus.com</email>
      <name>
        <last>Kuizinas</last>
        <first>Gajus</first>
      </name>
    </customer>
    <billing>
      <amount currencycode="GBP">100</amount>
      <email>dummy@gajus.com</email>
      <name>
        <last>Kuizinas</last>
        <first>Gajus</first>
      </name>
      <payment type="VISA">
        <expirydate>10/2031</expirydate>
        <pan>4111110000000211</pan>
        <securitycode>123</securitycode>
      </payment>
    </billing>
    <operation>
      <sitereference>site_reference</sitereference>
      <accounttypedescription>ECOM</accounttypedescription>
    </operation>
  </request>
</requestblock>
```

The above XML is for illustration only. It is unlikely that you will need to deal with XML except for debugging.

Next you issue the request itself against Secure Trading:

```php
$response = $auth->request();
```

### Response

I am working on convenience function to handle the response. In the mean time, response is `SimpleXMLElement` instance.

```php
$authcode_transaction = null;
$error = null;

foreach ($response->xpath('/responseblock/response') as $r) {
    if (!empty($r->error->code)) {
        // Error
    }
    
    if (!empty($transaction['authcode'])) {
        $authcode_transaction = $transaction;
    }
}

// Does PayPal require second step?

if (!empty($response->response->paypal->redirecturl)) {
    // Redirect to $response->response->paypal->redirecturl.
}

if (!isset($authcode_transaction)) {
    // Transaction declined. Unknown error.
}
```