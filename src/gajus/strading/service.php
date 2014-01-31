<?php
namespace gajus\strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @copyright Copyright (c) 2013-2014, Anuary (http://anuary.com/)
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Service {
	private
		$interface_url,
		$site_reference,
		$username,
		$password;
	
	/**
	 * Service is a Factory class carrying Secure Trading authentication parameters
	 * and supplying Request with the request XML template.
	 *
	 * @param string $interface_url
	 * @param string $username
	 * @param string $password 
	 */
	public function __construct ($interface_url, $site_reference, $username, $password) {
		$this->interface_url = $interface_url;
		$this->site_reference = $site_reference;
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * @param string $name Request template name, e.g. "card/order".
	 * @return gajus\strading\Request
	 */
	public function request ($name) {
		$template = __DIR__ . '/templates/' . $name . '.xml';
		
		if (!file_exists($template)) {
			throw new \InvalidArgumentException('Invalid request template.');
		}
		
		$dom = new \DOMDocument();
		$dom->load($template);
		$dom->formatOutput = true;

		$request = new Request($this->interface_url, $this->username, $this->password, $dom);	
		
		return $request;
	}
}