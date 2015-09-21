<?php

/**
 * Provide a session based auth adapter for customer REST API requests.
 */
class Aoe_Api2_Model_Auth_Adapter_Session extends Mage_Api2_Model_Auth_Adapter_Abstract
{
    const USER_TYPE_CUSTOMER = 'customer';

    /**
     * Process request and figure out an API user type and its identifier
     *
     * Returns stdClass object with two properties: type and id
     *
     * @param Mage_Api2_Model_Request $request
     *
     * @return stdClass
     */
    public function getUserParams(Mage_Api2_Model_Request $request)
    {
        $userParamsObj = new stdClass();
        $userParamsObj->type = null;
        $userParamsObj->id = null;

        if ($this->isApplicableToRequest($request)) {
            $userParamsObj->id = $this->getHelper()->getCustomerSession()->getCustomerId();
            $userParamsObj->type = self::USER_TYPE_CUSTOMER;
        }

        return $userParamsObj;
    }

    /**
     * Check if request contains authentication info for adapter
     *
     * @param Mage_Api2_Model_Request $request
     *
     * @return boolean
     */
    public function isApplicableToRequest(Mage_Api2_Model_Request $request)
    {
        // This auth adapter is for frontend use only
        if (Mage::app()->getStore()->isAdmin()) {
            return false;
        }

        // Ensure frontend sessions are initialized using the proper cookie name
        $this->getHelper()->getCoreSession();

        // We are only applicable if the customer is logged in already
        return $this->getHelper()->getCustomerSession()->isLoggedIn();
    }

    /**
     * @return Aoe_Api2_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('Aoe_Api2');
    }
}
