<?php
namespace securetrading;

abstract class Method {
	private
		$interface_url,
		$site_reference,
		$username,
		$password;
	
	protected
		$dom,
		$xpath;
	
	public function __construct ($interface_url, $site_reference, $username, $password) {
		$this->interface_url = $interface_url;
		$this->site_reference = $site_reference;
		$this->username = $username;
		$this->password = $password;
		
		$this->dom = new \DOMDocument('1.0', 'utf-8');
		$this->dom->formatOutput = true;
		
		$element_requestblock = $this->dom->createElement('requestblock');
		$element_requestblock->setAttribute('version', '3.67');
			
			$element_alias = $this->dom->createElement('alias', $this->username);
			
			$element_request = $this->dom->createElement('request');
			
				$element_operation = $this->dom->createElement('operation');
					
					$element_accounttypedescription = $this->dom->createElement('accounttypedescription', 'ECOM');
					$element_sitereference = $this->dom->createElement('sitereference', $this->site_reference);
				
				$element_operation->appendChild($element_accounttypedescription);
				$element_operation->appendChild($element_sitereference);
				
			$element_request->appendChild($element_operation);
		
		$element_requestblock->appendChild($element_alias);
		$element_requestblock->appendChild($element_request);
			
		$this->dom->appendChild($element_requestblock);
		
		$this->xpath = new \DOMXPath($this->dom);
	}
	
	public function __call ($name, $arguments) {
		if (strpos($name, 'request') === 0) {
			$xml = call_user_func_array([$this, 'generate' . substr($name, 7)], $arguments);
			
			return new Request($this->interface_url, $this->username, $this->password, $xml, [$this, 'parse' . substr($name, 7)]);
		}
		
		throw new \Exception('Unknown method: ' . $name);
	}
	
	public function buildElement ($name, array $data, array $schema = null) {
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
}