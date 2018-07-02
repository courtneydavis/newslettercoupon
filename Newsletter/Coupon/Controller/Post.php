<?php
namespace Newsletter\Coupon\Controller\Index;

use Zend\Log\Filter\Timestamp;

class Post extends \Magento\Framework\App\Action\Action
{
    const XML_PATH_EMAIL_RECIPIENT_NAME = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';

    protected $_inlineTranslation;
    protected $_transportBuilder;
    protected $_scopeConfig;
    protected $_logLoggerInterface;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $loggerInterface,
        array $data = []

    )
    {
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_logLoggerInterface = $loggerInterface;
        $this->messageManager = $context->getMessageManager();


        parent::__construct($context);


    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();
        try
        {
            // Send Mail
            $this->_inlineTranslation->suspend();
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $senderName = $post['name'];
            $senderEmail = $post['email'];

            $sender = [
                'name' => $senderName,
                'email' => $senderEmail,
            ];

            $sentToEmail = $this->_scopeConfig ->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $sentToName = $this->_scopeConfig ->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);


            $transport = $this->_transportBuilder
                ->setTemplateIdentifier('customemail_email_template')
                ->setTemplateOptions(
                    [
                        'area' => 'frontend',
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars([
                    'name'  => $post['name'],
                    'email'  => $post['email']
                ])
                ->setFrom($sender)
                ->addTo($sentToEmail,$sentToName)
                ->addTo('me@courtneyreneedavis.com','owner')
                ->getTransport();

            $transport->sendMessage();

            $this->_inlineTranslation->resume();
            $this->messageManager->addSuccess('Email sent successfully');
            $this->_redirect('customemail/index/index');

        } catch(\Exception $e){
            $this->messageManager->addError($e->getMessage());
            $this->_logLoggerInterface->debug($e->getMessage());
            exit;
        }



    }
}