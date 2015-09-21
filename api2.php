<?php
if (version_compare(phpversion(), '5.2.0', '<')) {
    if (!headers_sent()) {
        header('HTTP/1.1 500');
    }
    echo 'Service temporary unavailable';
    exit;
}

// Store the Magento root directory
define('MAGENTO_ROOT', getcwd());

$bootstrapFilename = MAGENTO_ROOT . '/app/bootstrap.php';
if (file_exists($bootstrapFilename)) {
    require $bootstrapFilename;
}

$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
if (!file_exists($mageFilename)) {
    echo 'Mage file not found';
    exit;
}
require $mageFilename;

if (!Mage::isInstalled()) {
    echo 'Application is not installed yet, please complete install wizard first.';
    exit;
}

if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
    Mage::setIsDeveloperMode(true);
    if (isset($_SERVER['MAGE_DISPLAY_ERRORS'])) {
        ini_set('display_errors', 1);
    }
}

Mage::$headersSentThrowsException = false;

$mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : 'admin';
$mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';
Mage::init($mageRunCode, $mageRunType);

Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_GLOBAL, Mage_Core_Model_App_Area::PART_EVENTS);
if (Mage::app()->getStore()->isAdmin()) {
    Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_ADMINHTML, Mage_Core_Model_App_Area::PART_EVENTS);
} else {
    Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
}

// emulate index.php entry point for correct URLs generation in API
Mage::register('custom_entry_point', true);

// query parameter "type" is set by .htaccess rewrite rule
$apiType = Mage::app()->getRequest()->getParam('type');

// Check if the request can be processed by Mage_Api2
if (!in_array($apiType, Mage_Api2_Model_Server::getApiTypes())) {
    if (!headers_sent()) {
        header('HTTP/1.1 500');
    }
    echo 'Service temporary unavailable';
    exit;
}

/** @var $server Mage_Api2_Model_Server */
$server = Mage::getSingleton('api2/server');
$server->run();
