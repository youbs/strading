<?php
namespace securetrading;

class Request implements \ArrayAccess {
	private
		$interface_url,
		$headers = ['Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'],
		$response;

	public function __construct ($interface_url, $xml, $username, $password) {
		$this->headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
	
		$this->response = $this->request($interface_url, $xml);
		$this->data = $this->parseResponse($this->response);
	}
	
	public function offsetExists ($offset) {
		return isset($this->data[$offset]);
	}
	
	public function offsetGet ($offset) {
		return $this->data[$offset];
	}
	
	public function offsetSet ($offset, $value) {
		$this->data[$offset] = $value;
	}
	
	public function offsetUnset ($offset) {
		unset($this->data[$offset]);
	}
	
	public function getRaw () {
		return $this->response;
	}

	private function parseResponse ($xml) {
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->loadXML($xml);
		
		$responseblock = $dom->getElementsByTagName('responseblock');
		
		if ($responseblock->length !== 1) {
			throw new \Exception('Invalid response.');
		}
		
		$responseblock_element = $responseblock->item(0);
		
		$response = [];
		$response['requestreference'] = $responseblock_element->getElementsByTagName('requestreference')->item(0)->nodeValue;
		
		$response_element = $responseblock_element->getElementsByTagName('response')->item(0);
		
		$response['type'] = $response_element->getAttribute('type');
		
		$response['timestamp'] = $response_element->getElementsByTagName('timestamp')->item(0)->nodeValue;
		
		if ($response['type'] === 'ERROR') {
			$error_element = $response_element->getElementsByTagName('error')->item(0);
			
			$response['error'] = [
				'message' => $error_element->getElementsByTagName('message')->item(0)->nodeValue,
				'code' => (int) $error_element->getElementsByTagName('code')->item(0)->nodeValue,
				'data' => $error_element->getElementsByTagName('data')->item(0)->nodeValue
			];
		} else if ($response['type'] === 'AUTH') {
			$security_element = $response_element->getElementsByTagName('security')->item(0);
			$error_block = $response_element->getElementsByTagName('error')->item(0);
			
			$response['auth'] = [
				'transactionreference' => $response_element->getElementsByTagName('transactionreference')->item(0)->nodeValue,
				'authcode' => $response_element->getElementsByTagName('authcode')->item(0)->nodeValue,
				'live' => (int) $response_element->getElementsByTagName('live')->item(0)->nodeValue,
				'security' => [
					'address' => (int) $security_element->getElementsByTagName('address')->item(0)->nodeValue,
					'postcode' => (int) $security_element->getElementsByTagName('postcode')->item(0)->nodeValue,
					'securitycode' => (int) $security_element->getElementsByTagName('securitycode')->item(0)->nodeValue
				],
				'error' => [
					'code' => (int) $error_block->getElementsByTagName('code')->item(0)->nodeValue,
					'message' => $error_block->getElementsByTagName('message')->item(0)->nodeValue,
				]
			];
		} else {
			throw new \Exception('Invalid response type.');
		}
		
		return $response;
	}
	
	private function request ($url, $xml) {		
		$ch = curl_init();
		
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
		    CURLOPT_TIMEOUT => 10,
		    CURLOPT_SSL_VERIFYHOST => 2,
		    CURLOPT_SSL_VERIFYPEER => true,
		    CURLOPT_HTTPHEADER => $this->headers,
		    CURLOPT_POSTFIELDS => trim($xml)
		];
		
		curl_setopt_array($ch, $options);
		
		$response = curl_exec($ch);
		
		return $response;
	}
}