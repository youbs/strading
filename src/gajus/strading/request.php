<?php
namespace gajus\strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @copyright Copyright (c) 2013-2014, Anuary (http://anuary.com/)
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Request /* implements \ArrayAccess*/ {
	private
		$interface_url,
		$headers = ['Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'],
		$dom,
		$xpath,
		$response;

	public function __construct ($interface_url, $username, $password, \DOMDocument $dom) {
		$this->interface_url = $interface_url;
		$this->headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
		$this->dom = $dom;
		$this->xpath = new \DOMXPath($dom);

		if ($name === 'transactionquery') {
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
	 */
	public function populate (array $data, $namespace = '') {
		if ($this->response) {
			throw new \LogicException('Cannot use populate Request body with new data post request.');
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
					throw new \InvalidArgumentException($namespace . '/' . $k . ' path does not refer to an existing element.');
				} else if ($element->length > 1) {
					throw new \InvalidArgumentException($namespace . '/' . $k . ' path is referring to multiple elements.');
				}
				
				if ($attribute) {
					$element->item(0)->setAttribute($attribute, $v);
				} else {
					$element->item(0)->nodeValue = ''; // or while first child remove
					$element->item(0)->appendChild($this->dom->createTextNode($v));
				}
			}
		}
	}
	
	/*public function offsetExists ($offset) {
		throw new \BadMethodCallException('Method not in use.');
	}
	
	public function offsetGet ($offset) {
		return $this->xpath->query($offset);
	}
	
	public function offsetSet ($offset, $value) {
		throw new \BadMethodCallException('Method not in use.');
	}
	
	public function offsetUnset ($offset) {
		throw new \BadMethodCallException('Method not in use.');
	}*/
	
	#public function getRaw () {
	#	return $this->response;
	#}
	
	public function request () {
		if ($this->response) {
			throw new \LogicException('Cannot use Request to produce multiple requests.');
		}

		$this->response = $this->makeRequest();
		
		return new \SimpleXMLElement($response);
	}
	
	private function makeRequest () {		
		$ch = curl_init();
		
		// Remove all elements that do not have children nodes and attributes.
		foreach ($this->xpath->query('//*[not(node()) and not(@*)]') as $node) {
			$node->parentNode->removeChild($node);
		}
		
		$options = [
			CURLOPT_URL => $this->interface_url,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
		    CURLOPT_TIMEOUT => 10,
		    CURLOPT_SSL_VERIFYHOST => 2,
		    CURLOPT_SSL_VERIFYPEER => true,
		    CURLOPT_HTTPHEADER => $this->headers,
		    CURLOPT_POSTFIELDS => trim($this->dom->saveXML())
		];
		
		curl_setopt_array($ch, $options);
		
		$response = curl_exec($ch);
		
		return $response;
	}
}