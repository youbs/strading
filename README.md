# Strading

[![Build Status](https://travis-ci.org/gajus/strading.png?branch=master)](https://travis-ci.org/gajus/strading)
[![Coverage Status](https://coveralls.io/repos/gajus/strading/badge.png?branch=master)](https://coveralls.io/r/gajus/strading?branch=master)
[![Latest Stable Version](https://poser.pugx.org/gajus/strading/version.png)](https://packagist.org/packages/gajus/strading)
[![License](https://poser.pugx.org/gajus/strading/license.png)](https://packagist.org/packages/gajus/strading)

Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) API abstraction.

## Documentation

The primary resource is Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) documentation. Strading handles authentication and provides a convenience method `populate` to populate the request XML template.

### Card Authorisation Request Example

The API itself is documented under [XML Specification](http://www.securetrading.com/wp-content/uploads/2013/07/STPP-XML-Specification2.pdf) document.

```php
/**
 * @param string $site_reference
 * @param string $username
 * @param string $password
 * @param string $interface_url
 */
$service = new \Gajus\Strading\Service('site_reference', 'username', 'password');

/**
 * @param string $name Request template name, e.g. "card/order".
 * @return Gajus\Strading\Request
 */
$auth = $service->request('card/auth');

/**
 * Populate XML template with given data.
 * 
 * @param array $data ['node name' => 'text node value', 'node[attribute]' => 'attribute value', 'parent node' => ['child node' => 'text node value']]
 * @param string $namespace
 * @return null
 */
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

/**
 * Request XML is stripped of empty tags without attributes.
 *
 * @return string
 */
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