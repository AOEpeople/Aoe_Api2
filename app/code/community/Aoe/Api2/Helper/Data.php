<?php

class Aoe_Api2_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return Mage_Core_Model_Session
     */
    public function getCoreSession()
    {
        return Mage::getSingleton('core/session', array('name' => Mage_Core_Controller_Front_Action::SESSION_NAMESPACE));
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @return Mage_Core_Model_Cookie
     */
    public function getCookie()
    {
        return Mage::getSingleton('core/cookie');
    }
}
