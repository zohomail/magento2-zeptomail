<?php

namespace Zoho\ZeptoMail\Mail;


use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\Transport as MagentoTransport;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;

use Psr\Log\LoggerInterface;
use Zend_Mail;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Laminas\Mime\Part as LaminasMimePart;
use Laminas\Mime\Message as LaminasMimeMessage;
use Magento\Framework\Encryption\EncryptorInterface as EncryptorInterface;
use Zoho\ZeptoMail\Helper\Config as OauthConfig;
use Zoho\ZeptoMail\Helper\ZeptoAuth as ZeptoAuth;
use Zoho\ZeptoMail\Helper\ZeptoMailApi as ZeptoMailApi;
use Zoho\ZeptoMail\Model\AdminNotification as AdminNotification;
use Zoho\ZeptoMail\Helper\ZConstants as ZConstants;
class Transport extends MagentoTransport implements TransportInterface
{


    protected $config;
	
	protected $message;
    protected $logger;
	protected $oauthconfig;
	protected $adminNotification;
	protected $parameters;
	protected $storeManager;
	protected $storeId;

    public function __construct(EmailMessageInterface $message, StoreManagerInterface $storeManager, $parameters = null, ?LoggerInterface $logger = null, ?OauthConfig $oauthConfig = null)
    {
		$this->message = $message;
		$this->storeManager = $storeManager;
		$this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
		$this->oauthconfig = $logger ?: ObjectManager::getInstance()->get(OauthConfig::class);
		$this->parameters = $parameters;
		if($storeManager->getStore()){
			$this->storeId =$storeManager->getStore()->getId();
		}
        parent::__construct($message, $parameters);
		
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     */
    public function sendMessage(): void
    {
		if(!isset($this->storeId)){
			parent::sendMessage();
			return;
		}
		$domain = $this->oauthconfig->getStoreConfig('domain',$this->storeId);
		$authtoken = base64_decode($this->oauthconfig->getStoreConfig('mail_token',$this->storeId));
		$allowed_emails = json_decode($this->oauthconfig->getStoreConfig('allowed_emails',$this->storeId),true);
		
		if(!isset($domain) || !isset($authtoken)){
			
			parent::sendMessage();
			return;
		}
		$zeptoAPI = new ZeptoMailApi($domain,$authtoken);
        $body = $this->message->getSymfonyMessage()->getMessageBody();
        $parsedBody = [
            'text' => '',
			'html' => '',
            'attachments' => []
        ];

		$parsedBody['ishtml'] = false;
        foreach ($body->getParts() as $part) {
			if ($part->getType() == 'text/plain') {
				$parsedBody['text'] .= $part->getRawContent();
			}
			else if ($part->getType() == 'text/html') {
				$parsedBody['html'] .= $part->getRawContent();
				$parsedBody['ishtml'] = true;
			} else {
				$parsedBody['attachments'][] = [
						'content' => base64_encode($part->getRawContent()),
						'name' => $part->getFileName(),
						'mime_type' => $part->getType()
					];
			}
			
		}
		
		
		$message = $this->message;
		
		
		$to = $cc = $bcc = $reply_to = array();
		$headers = $message->getHeaders();
		
		$fromAddress =$this:: splitNameAndAddress($headers['From']);
		
		$mail_data = array();
		if ($headers['From'] !== null) {
			if (!in_array(strtolower($fromAddress['address']), $allowed_emails)){
				$fromAddress['address'] = $this->oauthconfig->getTransmailEmailAddress(ZConstants::ident_general,$this->storeId);
			}
			$mail_data['from'] =  $fromAddress;
			
        }
		
		if ($headers['To'] !== null) {
			$toRecipients = $headers['To'];
			$mail_data['to'] = $this::getEmailDetails($toRecipients);
        }
		if (array_key_exists('Cc',$headers)) {
			$ccRecipients = $headers['Cc'];
			$mail_data['cc'] = $this::getEmailDetails($ccRecipients);
        }
		
		if (array_key_exists('Bcc',$headers)) {
			$bccRecipients = $headers['Bcc'];
			$mail_data['bcc'] = $this::getEmailDetails($bccRecipients);
        }
		
		if (array_key_exists('Subject',$headers)) {
			$subject = $headers['Subject'];
			$mail_data['subject'] = $subject;
        }
		if($parsedBody['attachments']){
			$mail_data['attachments'] = $parsedBody['attachments'];
		}
		if($parsedBody['ishtml']){
			$mail_data['htmlbody'] = $parsedBody['html'];
		} else {
			$mail_data['textbody'] = $parsedBody['text'];
		}
		

	$respObj = $zeptoAPI->sendZeptoMail($mail_data);
	
	if($respObj->result !== 'success'){
		if($this->oauthconfig->getStoreConfig('zeptoFailedEmails',$this->storeId) !== null){
			$failedEmails = json_decode($this->oauthconfig->getStoreConfig('zeptoFailedEmails',$this->storeId),true);
		}
		else {
			$failedEmails = [];
		}
		
		$errorCode = $respObj->details->code;
		if($errorCode == 'SM_111' || $errorCode == 'SM_145' || $errorCode == 'SM_147') {
			if (!in_array(strtolower($fromAddress['address']), $failedEmails)){
				array_push($failedEmails,$fromAddress['address']);
				$this->oauthconfig->setStoreConfig('zeptoFailedEmails',json_encode($failedEmails),$this->storeId);
				$this->oauthconfig->flushCache();
				$this->adminNotification = ObjectManager::getInstance()->get(AdminNotification::class);
				$this->adminNotification->addNotice('Zoho ZeptoMail Notification',' please reconfigure zeptomail again.','',\Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE);
			}
		}
		
	}
	
	
		
    }
	
	private function splitNameAndAddress($emailAddress) {
		$result = [
			'name' => '',
			'address' => ''
			];
		$pos = strpos($emailAddress, '<');
		if($pos !== false) {
			$result['address'] =  substr($emailAddress, $pos+1, strlen($emailAddress)-$pos-2);
			$result['name'] = substr($emailAddress,0,$pos-1);
		} else {
			$result['address'] = $emailAddress;
		}
		
		return $result;
	}
	private function getEmailDetails($emailAddressDetails) {
		if(!is_array($emailAddressDetails)){
			$emailAddressDetails = explode( ',', $emailAddressDetails );
		}
		$result = array();
		
		
		foreach($emailAddressDetails as $emailAddress){
			$emailDetail = array();
			$emailDetail["email_address"] = $this::splitNameAndAddress($emailAddress);
			array_push($result,$emailDetail);
		}
		
		return $result;
	}

}
