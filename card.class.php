<?php
namespace securetrading;

class Card extends Method {
	public function getAuth (array $billing) {	
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
}