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

    public function testSiteReferenceInFilter () {
        $transactionquery = $this->service->request('transactionquery');

        $this->assertXmlStringEqualsXmlString($this->loadXML('request_transaction_query/site_reference_in_filter'), $transactionquery->getXML());
    }
}