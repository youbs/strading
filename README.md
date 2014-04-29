# Strading

[![Build Status](https://travis-ci.org/gajus/strading.png?branch=master)](https://travis-ci.org/gajus/strading)
[![Coverage Status](https://coveralls.io/repos/gajus/strading/badge.png?branch=master)](https://coveralls.io/r/gajus/strading?branch=master)
[![Latest Stable Version](https://poser.pugx.org/gajus/strading/version.png)](https://packagist.org/packages/gajus/strading)
[![License](https://poser.pugx.org/gajus/strading/license.png)](https://packagist.org/packages/gajus/strading)

Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) (STWS) API abstraction.

## Documentation

1. [Instantiating Strading](#instantiating-strading)
1. [Load request template](#load-request-template)
2. [Populate the template](#populate-the-template)
3. [Make request](#make-request)
4. [Interpret response](#interpret-response)

To learn about the different types of requests and the required attributes, refer to the Secure Trading [Web Services](http://www.securetrading.com/support/document/category/web-services/) API documentation.

### Instantiating Strading

Service is a factory for building the requests using a template and populating the request with your credentials.

```php
/**
 * @param string $site_reference The Merchant's Site Reference.
 * @param string $username
 * @param string $password
 * @param string $interface_url
 */
$service = new \Gajus\Strading\Service('test_github53934', 'api@anuary.com', '93gbjdMR');
```

### Load Request template

Requests templates to process card and PayPal transactions come with the library:

* [paypal/order](./src/template/paypal/order.xml)
* [paypal/auth](./src/template/paypal/auth.xml)
* [card/refund](./src/template/cart/refund.xml)
* [card/auth](./src/template/card/auth.xml)
* [transactionquery](./src/template/paypal/transactionquery.xml)

To make a new template, copy over the full request XML from the respective Secure Trading documentation.

```php
/**
 * @param string $name Request template name, e.g. "card/order".
 * @return Gajus\Strading\Request
 */
$auth = $service->request('card/auth');
```

The above example has initialised [Request](./src/Request.php) using the "card/auth" template.

### Populate the Template

Template is populated from an array, where each array key refers to an existing node.

```php
/**
 * Populate XML template using data from an array.
 * 
 * @param array $data ['node name' => 'text node value', 'another node[attribute]' => 'attribute value']
 * @param string $namespace XML namespace under which the node resides, e.g. /requestblock/request
 * @return null
 */
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
```

The above example is using "/requestblock/request/billing" namespace to refer to a specific XML node. Do this to reduce the amount of array nesting.

To preview the request, you can retrive the `SimpleXMLElement`:

```php
/**
 * Request XML stripped of empty tags without attributes.
 *
 * @return string
 */
$auth->getXML();
```

The above will produce:

```xml
<?xml version="1.0"?>
<requestblock version="3.67">
    <alias>api@anuary.com</alias>
    <request type="AUTH">
        <billing>
            <amount currencycode="GBP">100</amount>
            <email>foo@bar.baz</email>
            <name>
                <last>Bar</last>
                <first>Foo</first>
            </name>
            <payment type="VISA">
                <expirydate>10/2031</expirydate>
                <pan>4111110000000211</pan>
                <securitycode>123</securitycode>
            </payment>
        </billing>
        <operation>
            <sitereference>test_github53934</sitereference>
            <accounttypedescription>ECOM</accounttypedescription>
        </operation>
    </request>
</requestblock>
```

The XML nodes that were not populated and did not have a default value in the template, were stripped away from the request XML.

You can populate the request over several itterations.

```php
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
```

The above will produce:

```xml
<?xml version="1.0"?>
<requestblock version="3.67">
    <alias>api@anuary.com</alias>
    <request type="AUTH">
        <merchant>
            <orderreference>gajus-0000001</orderreference>
        </merchant>
        <customer>
            <email>foo@bar.baz</email>
            <name>
                <last>Bar</last>
                <first>Foo</first>
            </name>
        </customer>
        <billing>
            <amount currencycode="GBP">100</amount>
            <email>foo@bar.baz</email>
            <name>
                <last>Bar</last>
                <first>Foo</first>
            </name>
            <payment type="VISA">
            <expirydate>10/2031</expirydate>
            <pan>4111110000000211</pan>
            <securitycode>123</securitycode>
            </payment>
        </billing>
        <operation>
            <sitereference>test_github53934</sitereference>
            <accounttypedescription>ECOM</accounttypedescription>
        </operation>
    </request>
</requestblock>
```

Methods to dump XML are for debugging only. You do not need to deal with XML when making the request or handling the response.

### Make Request

Make the request and capture the `Response`:

```php
/**
 * Issue the request.
 *
 * @return Gajus\Strading\Response
 */
$response = $auth->request();
```

If HTTP response is not `200`, then `Gajus\Strading\Exception\RuntimeException` will be thrown. This will happen if you provide invalid authentication credentials or your credentials do not grant you access to the API endpoint.

### Interpret Response

[Response](./src/Response.php) class abstracts the response XML.

```php
/**
 * Transaction abstracts access to the most generic information about the response:
 *
 * - request_reference
 * - transaction_type
 * - transaction_reference
 * - timestamp
 * - parent_transaction_reference
 * - authcode
 * - amount
 * - paypal_token
 * 
 * Presence of this data will depend on the type of the response you receive, e.g.
 * only PayPal order request will include "paypal_token" parameter.
 * 
 * @return array
 */
public function getTransaction ();

/**
 * This information is available when response type is "ERROR".
 *
 * @return null|Gajus\Strading\Error
 */
public function getError ();

/**
 * This information is available in response to the "paypal/order" request.
 * 
 * @return null|string URL to redirect the client to.
 */
public function getRedirectUrl () {
    return $this->redirect_url;
}

/**
 * @return string Response type.
 */
public function getType () {
    return $this->type;
}

/**
 * @return string Raw XML response.
 */
public function getXML () {
    return $this->xml->asXML();
}
```

#### Error

In case `Response` type is "ERROR", the `getError` method will return an instance of `Gajus\Strading\Error`.

```php
/**
 * @return string
 */
public function getCode () {
    return $this->code;
}

/**
 * @return string
 */
public function getMessage () {
    return $this->message;
}

/**
 * This tag contains one or more child elements. If the error code is "30000" (Field Error)
 * then this field will contain the field (or fields) which caused the error.
 * 
 * @todo https://github.com/gajus/strading/issues/1
 * @return string
 */
public function getData () {
    return $this->data;
}
```