<?php 
namespace Zoho\ZeptoMail\Controller\Adminhtml\AuthSettings; 
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Zoho\ZeptoMail\Helper\Config;
use Zoho\ZeptoMail\Helper\ZConstants;
use Zoho\ZeptoMail\Helper\ZeptoMailApi as ZeptoMailApi;
use Zoho\ZeptoMail\Model\AdminNotification as AdminNotification;
class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface, HttpPostActionInterface {
protected $resultPageFactory = false;
protected $helper;
protected $transportBuilder;
protected $storeManager;
protected $adminNotification;
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		Config $helper,
		TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
		AdminNotification $adminNotification
	)
	{
		parent::__construct($context);
		$this->helper = $helper;
		$this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
		$this->resultPageFactory = $resultPageFactory;
		$this->adminNotification = $adminNotification;
	}
	public function execute()
	{
		//Todo localhost should be baseurl, domain has to handle
		 $storeId = (int)$this->getRequest()->getParam('store');
		 
		$resultPage = $this->resultPageFactory->create();
		$params = $this->getRequest()->getParams(); //get params
		$postData = $this->getRequest()->isPost();
		if($postData){
			$respObj = json_decode("{}",true);
			if($params['option'] == 'saveOAuthSettings'){
				$zeptoMailApi = new ZeptoMailApi($params['zepto_domain'],$params['zepto_mail_token']);
				$failedEmails = array();
				
				foreach (ZConstants::$email_types as $email_type) {
					$mailRes = $this->sendTestMail($params[$email_type['param_name']],$email_type['type'],$zeptoMailApi);
					if($mailRes->result !== 'success'){
						$errorCode = $mailRes->details->code;
						if($errorCode === 'SERR_157'){
							$respObj["result"] = "failure";
							$respObj['error_message'] = "Enter valid send mail token to complete configuration";
						} else {
							$failedDetails = ['type'=>$email_type['param_name'],'error'=>$mailRes->details];
							array_push($failedEmails,$failedDetails);
						}
						
					}
				}
				
				if(count($failedEmails) == 0 && !isset($respObj['error_message'])){
					$respObj["result"] = "success";
					
					$this->helper->setStoreConfig('domain',$params['zepto_domain'],$storeId);
					$this->helper->setStoreConfig('mail_token',base64_encode($params['zepto_mail_token']),$storeId);
					$allowedEmails = array();
					foreach (ZConstants::$email_types as $email_type) {
						$this->helper->setZeptoEmailConfig($email_type['id'],$params[$email_type['param_name']],$storeId);
						array_push($allowedEmails,strtolower($params[$email_type['param_name']]));
					}
					$this->helper->setStoreConfig('allowed_emails',json_encode($allowedEmails),$storeId);
					$this->helper->setStoreConfig('zeptoFailedEmails',json_encode([]),$storeId);
				}
				else{
					$respObj["result"] = "failure";
					$respObj["email_error"] = $failedEmails;
				}
				
				$this->getResponse()->setBody(json_encode($respObj));
				$this->helper->flushCache();
				return;
			} else if($params['option'] == 'testOauthSettings'){
				$zeptoMailApi = new ZeptoMailApi($this->helper->getStoreConfig("domain",$storeId),base64_decode($this->helper->getStoreConfig('mail_token',$storeId)));
				$failedEmails = array();
				$respObj["result"] = "success";
				foreach (ZConstants::$email_types as $email_type) {
					$mailRes = $this->sendTestMail($this->helper->getZeptoEmailAddress($email_type['id'],$storeId),$email_type['type'],$zeptoMailApi);
					if($mailRes->result !== 'success'){
					
						if($mailRes->details->code === 'SERR_157'){
							$respObj["result"] = "failure";
							$respObj['error_message'] = "Invalid authtoken, please enter valid authtoken";
						} else{
							$respObj["result"] = "failure";
							$failedDetails = ['type'=>$email_type['param_name'],'error'=>$mailRes->details];
							array_push($failedEmails,$failedDetails);
						}
						
					}
					
				}
				if(count($failedEmails)>0){
					$respObj["email_error"] = $failedEmails;
				}
				$this->getResponse()->setBody(json_encode($respObj));
				return;
			}

		}else{
			$ids = $this->storeManager->getStores();
			if(array_key_exists($storeId,$ids)){
				$resultPage->getConfig()->getTitle()->prepend((__('ZeptoMail Settings')));
			}else{
				$params = $this->getRequest()->getParams();
				$params['store'] = array_key_first($ids);
				if(isset($ids) && count($ids)>0) {
					$resultRedirect = $this->resultRedirectFactory->create();
					$resultRedirect->setPath('zeptoauth/authsettings/', $params);
					return $resultRedirect;
				}
				
			}
			
		}

		return $resultPage;
	}
    public function sendTestMail($fromAddress,$type,$zeptoMailApi)
    {
		$mail_data = array();
		$from = json_decode('{}');
		$from->address = $fromAddress;
		$emailDetail = array();
		$toArray = array();
		
		$mail_data['from'] = ['address' => $fromAddress];
		
		$emailDetail["email_address"] = ['address' => $fromAddress];
		array_push($toArray,$emailDetail);
		
		$mail_data['to'] = $toArray;
		$mail_data['subject'] = "This is a test email for the '". $type ."'category.";
		$mail_data['htmlbody'] = "This is a test email for the '". $type ."'category.";
		
		return $zeptoMailApi->sendZeptoMail($mail_data);
        
    }
	 protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
	
}