<?php
namespace Gajus\Strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Request {
	private
		$interface_url,
		$headers = ['Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'],
		$dom,
		$xpath,
		$response;

	/**
	 * @param string $interface_url
	 * @param string $site_reference
	 * @param string $username
	 * @param string $password
	 * @param DOMDocument $dom
	 */
	public function __construct ($interface_url, $site_reference, $username, $password, \DOMDocument $dom) {
		$this->interface_url = $interface_url;
		$this->headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
		$this->dom = $dom;
		$this->xpath = new \DOMXPath($dom);

		$request_type = $this->xpath->query('/requestblock/request')->item(0)->getAttribute('type');
		
		if ($request_type === 'TRANSACTIONQUERY') {
			$this->populate(['alias' => $username, 'request/filter/sitereference' => $site_reference], '/requestblock');
		} else {
			$this->populate(['alias' => $username, 'request/operation/sitereference' => $site_reference], '/requestblock');
		}
	}
	
	/**
	 * Populate XML template with given data.
	 * 
	 * @param array $data ['node name' => 'text node value', 'node[attribute]' => 'attribute value', 'parent node' => ['child node' => 'text node value']]
	 * @param string $namespace
	 * @return void
	 */
	public function populate (array $data, $namespace = '') {
		if ($this->response) {
			throw new Exception\LogicException('Cannot populate data after request.');
		}

		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$this->populate($v, $namespace . '/' . $k);
			} else {
				$attribute = null;
				
				if (($attribute_position = strpos($k, '[')) !== false) {
					$attribute = substr($k, $attribute_position + 1, -1);
					$k = substr($k, 0, $attribute_position);
				}
				
				$element = $this->xpath->query($namespace . '/' . $k);
				
				if ($element->length === 0) {
					throw new Exception\InvalidArgumentException($namespace . '/' . $k . ' path does not refer to an existing element.');
				}

				#else if ($element->length > 1) {
				#	throw new \InvalidArgumentException($namespace . '/' . $k . ' path is referring to multiple elements.');
				#}
				
				if ($attribute) {
					$element->item(0)->setAttribute($attribute, $v);
				} else {
					$element->item(0)->nodeValue = '';
					$element->item(0)->appendChild($this->dom->createTextNode($v));
				}
			}
		}
	}

	/**
	 * Request XML is stripped of empty tags without attributes.
	 *
	 * @return string
	 */
	public function getRequestXML () {
		$dom = clone $this->dom;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$xpath = new \DOMXPath($dom);

		// http://stackoverflow.com/a/21492078/368691
		while (($node_list = $xpath->query('//*[not(*) and not(@*) and not(text()[normalize-space()])]')) && $node_list->length) {
			foreach ($node_list as $node) {
				$node->parentNode->removeChild($node);
			}
		}

		$xml = tidy_repair_string($dom->saveXML(), array( 
			'output-xml' => true, 
			'input-xml' => true,
			'indent' => true,
			'wrap' => false
		));

		return $xml;
	}

	public function getRequestHeaders () {
		return $this->headers;
	}
	
	/**
	 * Issue request.
	 *
	 * @return SimpleXMLElement
	 */
	public function request () {
		if ($this->response) {
			throw new Exception\LogicException('Cannot use the same Request instance for multiple requests.');
		}

		$this->response = $this->makeRequest();

		return new \SimpleXMLElement($this->response);
	}
	
	/**
	 * @return string
	 */
	private function makeRequest () {		
		$ch = curl_init();
		
		$options = [
			CURLOPT_URL => $this->interface_url,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
		    CURLOPT_TIMEOUT => 10,
		    CURLOPT_SSL_VERIFYHOST => 2,
		    CURLOPT_SSL_VERIFYPEER => true,
		    CURLOPT_HTTPHEADER => $this->getRequestHeaders(),
		    CURLOPT_POSTFIELDS => $this->getRequestXML()
		];
		
		curl_setopt_array($ch, $options);
		
		$response = curl_exec($ch);
		
		return $response;
	}
}