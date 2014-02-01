# Strading

[![Build Status](https://travis-ci.org/gajus/strading.png?branch=master)](https://travis-ci.org/gajus/strading)
[![Coverage Status](https://coveralls.io/repos/gajus/strading/badge.png)](https://coveralls.io/r/gajus/strading)

Strading is Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) API interface. The current implementation is handling card and payment transactions. Though, it can be easily extended to use any other API interface.

## Documentation

Your primary resource for documentation remains Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) documentation. Strading provides merely authentication and convenience method `populate` to populate those large request XML templates.

### Example

The following example illustrates how you would make card authorisatin. The API itself is documented under [XML Specification](http://www.securetrading.com/wp-content/uploads/2013/07/STPP-XML-Specification2.pdf) document.

```php
$auth = $this->service->request('card/auth');

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

$response = $auth->request();
```