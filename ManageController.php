<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Newsletter
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Customers newsletter subscription controller
 *
 * @category   Mage
 * @package    Mage_Newsletter
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Newsletter_ManageController extends Mage_Core_Controller_Front_Action
{
    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        if ($block = $this->getLayout()->getBlock('customer_newsletter')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->getLayout()->getBlock('head')->setTitle($this->__('Newsletter Subscription'));
        $this->renderLayout();
    }

    public function saveAction()
    {
       
		if (!$this->_validateFormKey()) {
            return $this->_redirect('customer/account/');
        }
        try {
            Mage::getSingleton('customer/session')->getCustomer()
            ->setStoreId(Mage::app()->getStore()->getId())
            ->setIsSubscribed((boolean)$this->getRequest()->getParam('is_subscribed', false))
            ->save();
			$customer = Mage::getSingleton('customer/session')->getCustomer();
            if ((boolean)$this->getRequest()->getParam('is_subscribed', false)) {
				//ADDED BY RD MAILCHIMP
				$this->sendtomailchimp($customer->getEmail());
                Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription has been saved.'));
            } else {
				//Addeb by RD
				$this->unsubscribemailchimp($customer->getEmail());
                Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription has been removed.'));
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($this->__('An error occurred while saving your subscription.'));
        }
        $this->_redirect('customer/account/');
    }
	
	//SUBSCRIBE TO MAILCHIMP
	public function sendtomailchimp($email="")
    {
		$apikey='bcf76d50e50d67dc0ee4b6b3ccdd2bfe-us7';
		if(Mage::app()->getStore()->getCode() == 'arabic'){
			$listId='2671c9d754'; //Arabic 
			$merge_vars = array(
				'GROUPINGS' => array(
					0 => array(
						'name' => "النشرة الإخبارية", //You have to find the number via the API
						'groups' => "النشرة الإخبارية",
					)
				)
			);
		}else{
			$listId='4b702d62ec';
			$merge_vars = array(
				'GROUPINGS' => array(
					0 => array(
						'name' => "Newsletter", //You have to find the number via the API
						'groups' => "Newsletter",
					)
				)
			);
		}
			
			$data = array(
							'email_address'=>$email,
							'apikey'=>$apikey,
							'group_name'=>"General",
							'merge_vars' => $merge_vars,
							'id' => $listId,
							'double_optin' => false,
							'update_existing' => true,
							'replace_interests' => false,
							'send_welcome' => false,
							'email_type' => 'html'
					);
			$submit_url = "http://us7.api.mailchimp.com/1.3/?method=listSubscribe";


		$payload = json_encode($data); 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $submit_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode($payload));

		$result = curl_exec($ch);
		curl_close ($ch);
		
	}
	public function unsubscribemailchimp($email="")
    {
		$apikey='bcf76d50e50d67dc0ee4b6b3ccdd2bfe-us7';
		if(Mage::app()->getStore()->getCode() == 'arabic'){
			$listId='2671c9d754'; //Arabic 
		}else{
			$listId='4b702d62ec';
		}
			
			$data = array(
						"apikey": $apikey,
						"id": $listId,
						"email": {
							"email": $email,
							"euid": $email,
							"leid": $email
						},
						"delete_member": true,
						"send_goodbye": true,
						"send_notify": true
					);
			$submit_url = "http://us7.api.mailchimp.com/1.3/?method=unsubscribe";


		$payload = json_encode($data); 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $submit_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode($payload));

		$result = curl_exec($ch);
		print_r($result);
		die();
		curl_close ($ch);
		
	}
}
