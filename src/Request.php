<?php
namespace Gajus\Strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Request
{
    private
        /**
         * @var string
         */
        $interface_url,
        /**
         * @var array
         */
        $headers = array('Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'),
        /**
         * @var SimpleXMLElement
         */
        $xml,
        /**
         * @var string
         */
        $type;

    /**
     * @param string $interface_url
     * @param string $site_reference
     * @param string $username
     * @param string $password
     * @param SimpleXMLElement|\SimpleXMLElement $xml
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($interface_url, $site_reference, $username, $password, \SimpleXMLElement $xml)
    {
        $this->interface_url = $interface_url;
        $this->headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
        $this->xml = $xml;

        // @todo What about multipart requests?
        // PHP 5.3 does not allow array access to the method response.
        $this->type = $this->xml->xpath('/requestblock/request');
        $this->type = $this->type[0]->attributes();
        $this->type = (string)$this->type['type'];

        if ($this->getType() === 'TRANSACTIONQUERY') {
            $this->populate(array(
                'alias' => $username,
                'request/filter/sitereference' => $site_reference
            ), '/requestblock');
        } else if ($this->getType() === 'TRANSACTIONUPDATE') {
            $this->populate(array(
                'alias' => $username,
                'request/filter/sitereference' => $site_reference
            ), '/requestblock');
        } else {
            $this->populate(array(
                'alias' => $username,
                'request/operation/sitereference' => $site_reference
            ), '/requestblock');
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Populate XML template using data from an array.
     *
     * @param array $data ['node name' => 'text node value', 'another node[attribute]' => 'attribute value']
     * @param string $namespace XML namespace under which the node resides, e.g. /requestblock/request
     * @return null
     * @throws Exception\InvalidArgumentException
     */
    public function populate(array $data, $namespace = '')
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $this->populate($v, $namespace . '/' . $k);
            } else {
                $attribute = null;

                if (($attribute_position = strpos($k, '[')) !== false) {
                    $attribute = substr($k, $attribute_position + 1, -1);
                    $k = substr($k, 0, $attribute_position);
                }

                $element = $this->xml->xpath($namespace . '/' . $k);

                if (count($element) === 0) {
                    throw new Exception\InvalidArgumentException($namespace . '/' . $k . ' path does not refer to an existing element.');
                } else if (count($element) > 1) {
                    throw new \InvalidArgumentException($namespace . '/' . $k . ' path is referring to multiple elements.');
                }

                if ($attribute) {
                    $element[0]->addAttribute($attribute, $v);
                } else {
                    $element[0]->{0} = $v;
                }
            }
        }
    }

    /**
     * Request XML stripped of empty tags without attributes.
     *
     * @return string
     */
    public function getXML()
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->xml->asXML());
        $xpath = new \DOMXPath($dom);

        // Remove empty tags
        // @see http://stackoverflow.com/a/21492078/368691
        while (($node_list = $xpath->query('//*[not(*) and not(@*) and not(text()[normalize-space()])]')) && $node_list->length) {
            foreach ($node_list as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        // Remove text nodes that have only whitespace
        $node_list = $xpath->query("//*[not(*) and not(normalize-space())]");

        foreach ($node_list as $node) {
            $node->nodeValue = '';
            $node->removeChild($node->firstChild);
        }

        $xml = $dom->saveXML();

        return $xml;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Issue the request.
     * @return Gajus\Strading\Response
     * @throws Exception\RuntimeException
     */
    public function request()
    {
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $this->interface_url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => $this->getHeaders(),
            CURLOPT_POSTFIELDS => $this->getXML()
        );

        curl_setopt_array($ch, $options);

        $raw_response = curl_exec($ch);

        //print_r($raw_response);

        $info = curl_getinfo($ch);

        if ($info['http_code'] !== 200) {
            throw new Exception\RuntimeException($raw_response);
        }

        $response = new \SimpleXMLElement($raw_response);

        return new Response($response, $this);
    }
}