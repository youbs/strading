<?php
class RequestTest extends PHPUnit_Framework_TestCase {
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

    /**
     * @expectedException Gajus\Strading\Exception\InvalidArgumentException
     * @expectedExceptionMessage Request template does not exist.
     */
    public function testLoadNotExistingTemplate () {
        $this->service->request('does/not/exist');
    }

    public function testBuildRequest () {
        $auth = $this->service->request('card/auth');

        $this->assertInstanceOf('Gajus\Strading\Request', $auth);

        $request_xml = $auth->getXML();

        // The purpose of this test is to make sure that request is stripped of empty tags.
        $this->assertXmlStringEqualsXmlString($this->loadXML('build_request/test_build_request'), $request_xml);

        return $auth;
    }

    /**
     * @depends testBuildRequest
     */
    public function testGetHeaders (\Gajus\Strading\Request $auth) {
        $headers = $auth->getHeaders();

        $this->assertSame(array(
            'Content-Type: text/xml;charset=utf-8',
            'Accept: text/xml',
            'Authorization: Basic YXBpQGFudWFyeS5jb206OTNnYmpkTVI='
        ), $headers);
    }

    /**
     * @depends testBuildRequest
     * @expectedException Gajus\Strading\Exception\InvalidArgumentException
     * @expectedExceptionMessage /foo path does not refer to an existing element.
     */
    public function testPopulateNotExistingTag (\Gajus\Strading\Request $auth) {
        $auth->populate(array(
            'foo' => 'bar'
        ));
    }

    public function testSetAttribute () {
        $transactionquery = $this->service->request('transactionquery');

        $transactionquery->populate(array(
            'requestblock' => array(
                'request' => array(
                    'filter[foo]' => 'bar'
                )
            )
        ));

        $this->assertXmlStringEqualsXmlString($this->loadXML('build_request/test_set_attribute'), $transactionquery->getXML());
    }

    public function testSetAttributeUsingNamespace () {
        $transactionquery = $this->service->request('transactionquery');

        $transactionquery->populate(array(
            'filter[foo]' => 'bar'
        ), '/requestblock/request');

        $this->assertXmlStringEqualsXmlString($this->loadXML('build_request/test_set_attribute'), $transactionquery->getXML());
    }
}