<?php
namespace Zoho\ZeptoMail\Helper;

class ZeptoMailApi {
	private $domain;
	private $authtoken;
	
	private function getZeptoUrl() {
		return "https://zeptomail.".$this->domain;
	}
	public function __construct($domain,$authtoken) {
		$this->domain = $domain;
		$this->authtoken = $authtoken;
	}
	public function sendZeptoMail($mail_data) {
		$responseObj = json_decode('{}');
		$urlToSend = $this->getZeptoUrl().'/v1.1/email';	
		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_URL => $urlToSend,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($mail_data),
				CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"authorization: ".$this->authtoken,
				"cache-control: no-cache",
				"content-type: application/json",
				"User-Agent: MagentoPlugin"
			),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($httpcode == '200' || $httpcode == '201') {
			$responseObj->result = "success";
		}else{
			$responseObj->result ="error";
			$responseObj->details = json_decode($response)->error->details[0];
		}
		return $responseObj;
	}
	
   
}
	