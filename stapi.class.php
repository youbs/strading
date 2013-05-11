<?php
namespace ay\securetrading;

class STAPI {
	private
		$interface_url,
		$site_reference,
		$username,
		$password;
	
	public function __construct ($interface_url, $site_reference, $username, $password) {
		$this->interface_url = $interface_url;
		$this->site_reference = $site_reference;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function load ($name) {
		$template = __DIR__ . '/templates/' . $name . '.xml';
		
		if (!file_exists($template)) {
			throw new \ErrorException('Invalid request template.');
		}
		
		$xml = new \DOMDocument();
		$xml->load($template);
		
		$xpath = new \DOMXPath($xml);
		
		$request = new Request($this->interface_url, $this->username, $this->password, $xml);
		
		$request->populate(['alias' => $this->username, 'request/operation/sitereference' => $this->site_reference], '/requestblock');
		
		return $request;
	}
}

class STAPIException extends \Exception {}