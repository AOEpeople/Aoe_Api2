<?php

class Aoe_Api2_Model_Server extends Mage_Api2_Model_Server
{
    /**
     * Run server
     */
    public function run()
    {
        try {
            $request = $this->getRequest();
            $response = $this->getResponse();
            $renderer = Mage_Api2_Model_Renderer::factory($request->getAcceptTypes());

            $this->filterBefore($request, $response);

            if (!$request->isOptions()) {
                try {
                    /** @var $apiUser Mage_Api2_Model_Auth_User_Abstract */
                    $apiUser = $this->_authenticate($request);

                    $this->_route($request);
                    $this->_allow($request, $apiUser);
                    $this->_dispatch($request, $response, $apiUser);

                    if ($response->isException()) {
                        //NOTE: At this moment Renderer already could have some content rendered, so we should replace it
                        throw new Mage_Api2_Exception('Unknown server error', self::HTTP_INTERNAL_ERROR);
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_renderException($e, $renderer, $response);
                }
            }

            $this->filterAfter($request, $response);

            $response->sendResponse();
        } catch (Exception $e) {
            Mage::logException($e);
            if (!headers_sent()) {
                header('HTTP/1.1 ' . self::HTTP_INTERNAL_ERROR);
            }
            echo 'Service temporarily unavailable';
        }
    }

    protected function getRequest()
    {
        /** @var $request Mage_Api2_Model_Request */
        $request = Mage::getSingleton('api2/request');

        return $request;
    }

    protected function getResponse()
    {
        /** @var $response Mage_Api2_Model_Response */
        $response = Mage::getSingleton('api2/response');

        return $response;
    }

    protected function getRenderer(array $acceptTypes)
    {
        /** @var $renderer Mage_Api2_Model_Renderer_Interface */
        $renderer = Mage_Api2_Model_Renderer::factory($acceptTypes);

        return $renderer;
    }

    protected function filterBefore(Mage_Api2_Model_Request $request, Mage_Api2_Model_Response $response)
    {
        // Add generic CORS headers - this is not the 'right' way to do this, but Magento has no CORS support in Mage_Api2
        $response->setHeader('Access-Control-Allow-Origin', '*', true);
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE', true);
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type', true);
        $response->setHeader('Access-Control-Max-Age', '86400', true);

        // Support credentials
        $response->setHeader('Access-Control-Allow-Credentials', 'true', true);
        $origin = $request->getHeader('Origin');
        if ($origin) {
            try {
                $origin = Zend_Uri_Http::factory($origin);
                $response->setHeader('Access-Control-Allow-Origin', $origin->getUri(), true);
            } catch (Exception $e) {
                // NOOP
            }
        }

        Mage::dispatchEvent('api2_server_filter_before', ['request' => $request, 'response' => $response]);
    }

    protected function filterAfter(Mage_Api2_Model_Request $request, Mage_Api2_Model_Response $response)
    {
        Mage::dispatchEvent('api2_server_filter_after', ['request' => $request, 'response' => $response]);
    }
}
