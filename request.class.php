<?php
namespace securetrading\stapi;

class Request implements \ArrayAccess {
	private
		$interface_url,
		$headers = ['Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'];
	
	public
		$xpath,
		$request,
		$response;

	public function __construct ($interface_url, $username, $password, \DOMDocument $xml) {
		$this->interface_url = $interface_url;
		$this->headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
		
		$xml->formatOutput = true;
		
		$this->request = $xml;
		$this->xpath = new \DOMXPath($xml);
	}
	
	public function populate (array $data, $namespace = '') {
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$this->populate($v, $namespace . '/' . $k);
			} else {
				$element = $this->xpath->query($namespace . '/' . $k);
				
				if ($element->length === 0) {
					throw new RequestException($namespace . '/' . $k . ' path does not refer to an existing element.');
				} else if ($element->length > 1) {
					throw new RequestException($namespace . '/' . $k . ' path is referring to multiple elements.');
				}
				
				$element->item(0)->nodeValue = $v;
			}
		}
	}
	
	public function offsetExists ($offset) {
		throw new \Exception('offsetExists');
		//return isset($this->data[$offset]);
	}
	
	public function offsetGet ($offset) {
		return $this->xpath->query($offset);
	}
	
	public function offsetSet ($offset, $value) {
		throw new \Exception('offsetSet');
		//$this->data[$offset] = $value;
	}
	
	public function offsetUnset ($offset) {
		throw new \Exception('offsetUnset');
		//unset($this->data[$offset]);
	}
	
	public function getRaw () {
		return $this->response;
	}
	
	public function request () {
		$response = $this->makeRequest();
		
		return new \SimpleXMLElement($response);
	}
	
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
		    CURLOPT_HTTPHEADER => $this->headers,
		    CURLOPT_POSTFIELDS => trim($this->request->saveXML())
		];
		
		curl_setopt_array($ch, $options);
		
		$response = curl_exec($ch);
		
		return $response;
	}
}

class RequestException extends \Exception {}