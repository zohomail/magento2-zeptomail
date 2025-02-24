<?php
namespace Zoho\ZeptoMail\Model;

use Magento\Framework\Notification\MessageInterface;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Framework\UrlInterface;

class AdminNotification
{
    protected $inboxFactory;
	protected $urlBuilder;


    public function __construct(InboxFactory $inboxFactory, UrlInterface $urlBuilder)
    {
        $this->inboxFactory = $inboxFactory;
		$this->urlBuilder = $urlBuilder;
    }

    public function addNotice($title, $description, $url = '', $severity = \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE)
    {
		 $settingsUrl = $this->urlBuilder->getUrl('zeptoauth/authsettings/index');
        $inbox = $this->inboxFactory->create();
        $inbox->addNotice($title, $description, '', $severity);
    }
}
