<?php
class RequestTransactionQueryTest extends PHPUnit_Framework_TestCase {
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

    private function loadXML ($test_name) {
        $placeholders = array_map(function ($name) { return '{' . $name . '}'; }, array_keys($this->credentials));
        
        return str_replace($placeholders, array_values($this->credentials), file_get_contents(__DIR__ . '/xml/' . $test_name . '.xml'));
    }

    public function testSiteReferenceInFilter () {
        $transactionquery = $this->service->request('transactionquery');

        $this->assertXmlStringEqualsXmlString($this->loadXML('request_transaction_query/site_reference_in_filter'), $transactionquery->getXML());
    }
}