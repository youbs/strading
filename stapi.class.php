<?php
namespace securetrading;

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
	
	public function method ($name) {
		if (!class_exists(__NAMESPACE__ . '\\' . $name) || !is_subclass_of(__NAMESPACE__ . '\\' . $name, __NAMESPACE__ . '\Method')) {
			throw new STAPIException('Method does not exist or cannot be loaded.');
		}
		
		$method = __NAMESPACE__ . '\\' . $name;
		
		return new $method($this->interface_url, $this->site_reference, $this->username, $this->password);
	}
}

class STAPIException extends \Exception {}