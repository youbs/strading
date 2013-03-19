<?php
namespace securetrading;

class STAPI {
	private
		$interface_url,
		$site_reference,
		$username,
		$headers = ['Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'],
		$dom;

	public function __construct ($interface_url, $site_reference, $username, $password) {
		$this->interface_url = $interface_url;
		$this->site_reference = $site_reference;
		$this->username = $username;
		$this->headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $password);
		
		$this->dom = new \DOMDocument('1.0', 'utf-8');
		$this->dom->formatOutput = true;
	}
	
	public function requestAuth (array $billing, $debug = false) {
		$element_requestblock = $this->dom->createElement('requestblock');
		$element_requestblock->setAttribute('version', '3.67');
			
			$element_alias = $this->dom->createElement('alias', $this->username);
			
			$element_request = $this->dom->createElement('request');
			$element_request->setAttribute('type', 'AUTH');
			
				$element_operation = $this->dom->createElement('operation');
					
					$element_accounttypedescription = $this->dom->createElement('accounttypedescription', 'ECOM');
					$element_sitereference = $this->dom->createElement('sitereference', $this->site_reference);
				
				$element_operation->appendChild($element_accounttypedescription);
				$element_operation->appendChild($element_sitereference);
				
				$element_billing = $this->buildBillingElement($billing);
				
			$element_request->appendChild($element_operation);
			$element_request->appendChild($element_billing);
		
		$element_requestblock->appendChild($element_alias);
		$element_requestblock->appendChild($element_request);
			
		$this->dom->appendChild($element_requestblock);
		
		$xml = $this->dom->saveXML();
		
		if ($debug) {
			return $xml;
		}
		
		return $this->request($xml);
	}
	
	private function buildBillingElement (array $data) {
		$element = $this->buildElement('billing', $data, ['premise', 'street', 'town', 'county', 'country', 'postcode', 'email', 'amount', 'amount[currencycode]', 'telephone', 'telephone[type]']);
		
		if (isset($data['name'])) {
			$element->appendChild($this->buildElement('name', $data['name'], ['prefix', 'first', 'middle', 'last', 'suffix']));
		}
		
		if (isset($data['payment'])) {
			$element_payment = $this->buildElement('payment', $data['payment'], ['pan', 'expirydate', 'securitycode']);
			
			if (isset($data['payment[type]'])) {
				$element_payment->setAttribute('type', $data['payment[type]']);
			}
			
			$element->appendChild($element_payment);
		}
		
		return $element;
	}
	
	private function buildElement ($name, array $data, array $schema = null) {
		$block = $this->dom->createElement($name);
		
		if ($schema === null) {
			$schema = array_keys($data);
		}
		
		foreach ($schema as $name) {
			if (!isset($data[$name])) {
				continue;
			}
			
			$element_name = strstr($name, '[', true);
			
			if (!$element_name) {
				if (is_object($data[$name])) {
					$tmp = $this->dom->createElement($name);
					$tmp->appendChild($data[$name]);
					
					$block->appendChild($tmp);
				} else {
					$block->appendChild($this->dom->createElement($name, $data[$name]));
				}
			} else {
				$attribute_name = trim(strstr($name, '['), '[]');
			
				$children = $block->getElementsByTagName($element_name);
				
				if (!$children->length) {
					throw new \Exception("Cannot add attribute '{$attribute_name}' to a non-existing element '{$element_name}'.");
				}
				
				foreach ($children as $child) {
					$child->setAttribute($attribute_name, $data[$name]);
				}
			}
		}
		
		return $block;
	}
	
	private function response ($xml) {
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
	
	private function request ($xml) {		
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
		    CURLOPT_POSTFIELDS => trim($xml)
		];
		
		curl_setopt_array($ch, $options);
		
		$response = curl_exec($ch);
		
		return $this->response($response);
	}
}