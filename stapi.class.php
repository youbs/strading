<?php
namespace securetrading;

class STAPI {
	private
		$interface_url,
		$site_reference,
		$username,
		$headers = ['Content-Type: text/xml;charset=utf-8', 'Accept: text/xml'];

	public function __construct ($interface_url, $site_reference, $username, $password) {
		$this->interface_url = $interface_url;
		$this->site_reference = $site_reference;
		$this->username = $username;
		$this->headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $password);
	}
	
	public function payment() {
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		
		// requestblock
		$requestblock = $dom->createElement('requestblock');
		$requestblock->setAttribute('version', '3.67');
		
		$dom->appendChild($requestblock);
		
		// requestblock/alias
		$alias = $dom->createElement('alias', $this->username);
		
		$requestblock->appendChild($alias);
		
		// requestblock/request
		$request = $dom->createElement('request');
		$request->setAttribute('type', 'AUTH');
		
		$requestblock->appendChild($request);
		
		// requestblock/request/operation
		$operation = $dom->createElement('operation');
		
		$request->appendChild($operation);
		
		// requestblock/request/operation/accounttypedescription
		$accounttypedescription = $dom->createElement('accounttypedescription', 'ECOM');
		
		$operation->appendChild($accounttypedescription);
		
		// requestblock/request/operation/sitereference
		$sitereference = $dom->createElement('sitereference', $this->site_reference);
		
		$operation->appendChild($sitereference);
		
		// requestblock/request/billing
		$billing = $dom->createElement('billing');
		
		$request->appendChild($billing);
		
		// requestblock/request/billing/amount
		$amount = $dom->createElement('amount', 1);
		$amount->setAttribute('currencycode', 'GBP');
		
		$billing->appendChild($amount);
		
		// requestblock/request/billing/payment
		$payment = $dom->createElement('payment');
		
		$billing->appendChild($payment);
		
		// requestblock/request/billing/payment/pan
		$pan = $dom->createElement('pan', '4111111111111111');
		
		$payment->appendChild($pan);
		
		// requestblock/request/billing/payment/expirydate
		$expirydate = $dom->createElement('expirydate', '01/2020');
		
		$payment->appendChild($expirydate);
		
		return $this->request($dom->saveXML());
	}
	
	private function request ($xml) {
		$ch = curl_init();
		
		$options = [
			CURLOPT_URL => $this->interface_url,
			CURLOPT_HEADER => true,
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