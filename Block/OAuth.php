<?php
namespace Zoho\ZeptoMail\Block;

class OAuth extends \Magento\Framework\View\Element\Template
{


    protected $config;
   

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Zoho\ZeptoMail\Helper\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }


    public function getDomain()
    {
        return $this->config->getStoreConfig("domain",$this->getStoreId());
    }

    public function getMailToken()
    {
	$mailtoken = $this->config->getStoreConfig("mail_token",$this->getStoreId());
	if(!empty($mailtoken )) {
		return base64_decode($mailtoken);
	}
        return '';
    }
	
	public function getHostedDomain() {
		return $this->config->getStoreConfig("domain",$this->getStoreId());
	}


    public function getBaseUrl()
    {
        return $this->config->getBaseUrl();
    }

	public function getParams(){
		return $this->getRequest()->getParam('zepto_client_id');
	}
	
	public function getSupportEmail() {
		return $this->config->getTransmailEmailAddress("ident_support");
	}
	public function getTransmailEmailAddress($type,$storeId)
    {
        return $this->config->getZeptoEmailAddress($type,$storeId);
    }
	public function getStoreId() {
		return (int)$this->getRequest()->getParam('store');
	}
  

}
