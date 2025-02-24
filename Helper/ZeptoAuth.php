<?php
namespace Zoho\ZeptoMail\Helper;

class ZeptoAuth {
	
	private $authtoken;
	private $domain;
	
	public function __construct($domain, $authtoken) {
		$this->domain = $domain;
		$this->authtoken = $authtoken;
	}
	
	protected function getAuthToken() {
		return $this->authtoken;
	} 
	protected function getDomain() {
		return $this->domain;
	} 
}
	