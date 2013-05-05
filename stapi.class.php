<?php
namespace securetrading\stapi;

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
			throw new \Exception('Invalid request template.');
		}
		
		$xml = new \DOMDocument();
		$xml->load($template);
		
		$xpath = new \DOMXPath($xml);
		
		$xpath->query('/requestblock/alias')->item(0)->nodeValue = $this->username;
		$xpath->query('/requestblock/request/operation/sitereference')->item(0)->nodeValue = $this->site_reference;
		
		return new Request($this->interface_url, $this->username, $this->password, $xml);
	}
}

class STAPIException extends \Exception {}