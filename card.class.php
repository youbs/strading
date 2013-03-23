<?php
namespace securetrading;

class Card extends Method {
	public function generateAuth (array $billing) {	
		$element_request = $this->xpath->query('/requestblock/request')->item(0);
		
		$element_request->setAttribute('type', 'AUTH');
		
		$element_billing = $this->buildElement('billing', $billing, ['premise', 'street', 'town', 'county', 'country', 'postcode', 'email', 'amount', 'amount[currencycode]', 'telephone', 'telephone[type]']);
		
		
		
		if (isset($billing['name'])) {
			$element_billing->appendChild($this->buildElement('name', $billing['name'], ['prefix', 'first', 'middle', 'last', 'suffix']));
		}
		
		if (isset($billing['payment'])) {
			$element_payment = $this->buildElement('payment', $billing['payment'], ['pan', 'expirydate', 'securitycode']);
			
			if (isset($billing['payment[type]'])) {
				$element_payment->setAttribute('type', $billing['payment[type]']);
			}
			
			$element_billing->appendChild($element_payment);
		}
		
		$element_request->appendChild($element_billing);
		
		return $this->dom->saveXML();
	}
	
	/*private function parseResponse ($xml) {
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
	}*/
}