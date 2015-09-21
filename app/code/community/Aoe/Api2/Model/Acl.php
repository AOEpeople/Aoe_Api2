<?php

class Aoe_Api2_Model_Acl extends Mage_Api2_Model_Acl
{
    /**
     * Retrieve rules data from DB and inject it into ACL
     *
     * @return Mage_Api2_Model_Acl
     */
    protected function _setRules()
    {
        $resources = $this->getResources();

        /** @var Mage_Api2_Model_Resource_Acl_Global_Rule_Collection $rulesCollection */
        $rulesCollection = Mage::getResourceModel('api2/acl_global_rule_collection');
        foreach ($rulesCollection as $rule) {
            /** @var Mage_Api2_Model_Acl_Global_Rule $rule */
            if (Mage_Api2_Model_Acl_Global_Rule::RESOURCE_ALL === $rule->getResourceId()) {
                if (in_array($rule->getRoleId(), Mage_Api2_Model_Acl_Global_Role::getSystemRoles())) {
                    /** @var Mage_Api2_Model_Acl_Global_Role $role */
                    $role = $this->_getRolesCollection()->getItemById($rule->getRoleId());
                    $privileges = $this->_getConfig()->getResourceUserPrivileges(
                        $this->_resourceType,
                        $role->getConfigNodeName()
                    );

                    if (!array_key_exists($this->_operation, $privileges)) {
                        continue;
                    }
                }

                $this->allow($rule->getRoleId());
            } elseif (in_array($rule->getResourceId(), $resources)) {
                $this->allow($rule->getRoleId(), $rule->getResourceId(), $rule->getPrivilege());
            }
        }

        return $this;
    }
}
