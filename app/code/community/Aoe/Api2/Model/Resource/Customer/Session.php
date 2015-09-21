<?php

class Aoe_Api2_Model_Resource_Customer_Session extends Aoe_Api2_Model_Resource
{
    public function dispatch()
    {
        if ($this->getActionType() !== self::ACTION_TYPE_ENTITY) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }

        switch ($this->getOperation()) {
            case self::OPERATION_CREATE:
            case self::OPERATION_UPDATE:
                $requestData = $this->getRequest()->getBodyParams();
                if (empty($requestData)) {
                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
                }

                $filteredData = $this->getFilter()->in($requestData);
                if (empty($filteredData)) {
                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
                }

                $this->login($filteredData);
                if ($this->getResponse()->isException()) {
                    break;
                }

                // This fixes a 'bug' with calling in() and out() on the same filter in one request
                /** @var $filter Mage_Api2_Model_Acl_Filter */
                $filter = Mage::getModel('api2/acl_filter', $this);
                $this->setFilter($filter);

                $retrievedData = $this->info();
                $filteredData = $this->getFilter()->out($retrievedData);
                $this->_render($filteredData);
                break;

            case self::OPERATION_RETRIEVE:
                $retrievedData = $this->info();
                $filteredData = $this->getFilter()->out($retrievedData);
                $this->_render($filteredData);
                break;

            case self::OPERATION_DELETE:
                $this->logout();
                break;

            default:
                $this->_critical(self::RESOURCE_METHOD_NOT_ALLOWED);
                break;
        }
    }

    public function info()
    {
        $session = $this->getHelper()->getCustomerSession();
        $customer = $session->getCustomer();

        return [
            'isloggedin' => $customer && $customer->getId(),
            'firstname'  => $customer->getFirstname(),
            'lastname'   => $customer->getLastname(),
        ];
    }

    public function login(array $data)
    {
        if (!array_key_exists('login', $data) || !array_key_exists('password', $data)) {
            $this->_critical(self::RESOURCE_DATA_PRE_VALIDATION_ERROR);
        }

        try {
            $session = $this->getHelper()->getCustomerSession();
            $session->login($data['login'], $data['password']);
        } catch (Mage_Api2_Exception $e) {
            throw $e;
        } catch (Mage_Core_Exception $e) {
            if ($e->getCode() == Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD || $e->getCode() == Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED) {
                throw new Mage_Api2_Exception($e->getMessage(), Mage_Api2_Model_Server::HTTP_UNAUTHORIZED);
            }
            Mage::logException($e);
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }

    public function logout()
    {
        $session = $this->getHelper()->getCustomerSession();
        if ($session->getCustomer() && $session->getCustomer()->getId()) {
            $session->logout();
        }
    }

    /**
     * @return Aoe_Api2_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('Aoe_Api2');
    }
}
