<?php

abstract class Aoe_Api2_Model_Resource extends Mage_Api2_Model_Resource
{
    /**
     * Hash of external/internal attribute codes
     *
     * @var string[]
     */
    protected $attributeMap = [];

    /**
     * Hash of external attribute codes and their data type
     *
     * @var string[]
     */
    protected $attributeTypeMap = [];

    /**
     * Array of external attribute codes that are manually generated
     *
     * @var string[]
     */
    protected $manualAttributes = [];

    /**
     * Hash of default embed codes
     *
     * @var string[]
     */
    protected $defaultEmbeds = [];

    /**
     * @return Mage_Core_Model_Store
     */
    protected function _getStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * @param false|null|string|string[] $embeds
     *
     * @return string[]
     */
    protected function parseEmbeds($embeds)
    {
        if ($embeds === false || $embeds === '') {
            return [];
        } elseif ($embeds === null) {
            return $this->defaultEmbeds;
        }

        if (is_string($embeds)) {
            $embeds = explode(',', $embeds);
        }

        if (is_array($embeds)) {
            $embeds = array_filter(array_map('trim', $embeds));
        } else {
            $embeds = [];
        }

        return $embeds;
    }

    /**
     * Remap attribute keys
     *
     * @param array $data
     *
     * @return array
     */
    protected function mapAttributes(array &$data)
    {
        $map = $this->attributeMap;
        $out = [];

        foreach ($data as $key => &$value) {
            if (isset($map[$key])) {
                $key = $map[$key];
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Reverse remap the attribute keys
     *
     * @param array $data
     *
     * @return array
     */
    protected function unmapAttributes(array &$data)
    {
        $map = array_flip($this->attributeMap);
        $out = [];

        foreach ($data as $key => &$value) {
            if (isset($map[$key])) {
                $key = $map[$key];
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Type cast array values
     *
     * @param array  $data
     * @param array  $typeMap
     * @param string $currencyCode ISO currency code
     *
     * @return array
     *
     * @throws Zend_Currency_Exception
     * @throws Zend_Locale_Exception
     */
    protected function fixTypes(array $data, array $typeMap, $currencyCode)
    {
        if (empty($typeMap)) {
            $typeMap = $this->attributeTypeMap;
        }

        if (empty($currencyCode)) {
            $currencyCode = $this->_getStore()->getDefaultCurrencyCode();
        }

        foreach ($typeMap as $code => $type) {
            if (array_key_exists($code, $data) && (is_scalar($data[$code]) || is_null($data[$code]))) {
                switch ($type) {
                    case 'bool':
                        $data[$code] = (!empty($data[$code]) && strtolower($data[$code]) !== 'false');
                        break;
                    case 'int':
                        $data[$code] = intval($data[$code]);
                        break;
                    case 'float':
                        $data[$code] = floatval($data[$code]);
                        break;
                    case 'currency':
                        $amount = floatval($data[$code]);
                        $precision = Zend_Locale_Data::getContent(null, 'currencyfraction', $currencyCode);
                        if ($precision === false) {
                            $precision = Zend_Locale_Data::getContent(null, 'currencyfraction');
                        }
                        if ($precision !== false) {
                            $amount = round($amount, $precision);
                            $formatted = Mage::app()->getLocale()->currency($currencyCode)->toCurrency($amount, ['precision' => $precision]);
                        } else {
                            $formatted = Mage::app()->getLocale()->currency($currencyCode)->toCurrency($amount);
                        }
                        $data[$code] = ['currency' => $currencyCode, 'amount' => $amount, 'formatted' => $formatted];
                        break;
                    case 'string':
                    default:
                        $data[$code] = (is_null($data[$code]) ? $data[$code] : (string)$data[$code]);
                        break;
                }
            }
        }

        return $data;
    }
}
