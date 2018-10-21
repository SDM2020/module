<?php

namespace Money\DisasterRelief\Lib;

use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Client
{
    const SANDBOX_ENPOINT = 'https://sandbox.api.visa.com/visadirect/fundstransfer/v1/pushfundstransactions'

    const PORT = 443;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CurlFactory $curlFactory,
        LoggerInterface $logger
    ) {
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
    }

    /**
     * Prepares and runs request to API
     *
     * @param  mixed[] $inputs
     * @return mixed[]
     */
    public function post($endpoint, $payload)
    {
        $isAuthRequired = false // @TODO make this a system config
        /** @var \Magento\Framework\HTTP\Client\Curl $curl */
        $curl = $this->curlFactory->create();
        if ($this->isTestMode($settings)) {
            return $this->getDummyData();
        } else if ($isAuthRequired) {
            $curl->setCredentials($settings['user'], $settings['pass']);
        }
        $curl->setOption(CURLOPT_PORT, static::PORT);
        if (!!$settings['skip_ssl']) {
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
        } else {
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);
            if ($settings['cert_path'] === null
                || !file_exists($settings['cert_path'])
            ) {
                $this->logger->error("Money_DisasterRelief: SSL Certificate does not exist");
                throw new LocalizedException(__('Internal error during request to API'));
            }
            $curl->setOption(CURLOPT_CAINFO, $settings['cert_path']);
        }
        $curl->addHeader('Content-Type', 'application/json');
        try {
            $curl->post($endpoint, $post);
            try {
                $body = \Zend_Json::decode($curl->getBody());
            } catch (\Exception $e) {
                // Handle non JSON response
                $body = $curl->getBody();
            }
            $response = [
                'status' => $curl->getStatus(),
                'body' => $body,
            ];

            if ($response['status'] >= 300 || $response['status'] < 200) {
                $this->logger->error("Money_DisasterRelief: " . $body);
                throw new LocalizedException(__('Internal error during request to API'));
            }

            return $response;

        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Request to %s has failed with exception: %s; request data: %s; response: %s',
                    $this->getRequestUrl(),
                    $e->getMessage(),
                    json_encode($this->requestBody),
                    $curl->getBody()
                )
            );
            $this->logger->critical($e);
            throw new LocalizedException(__('Internal error during request to API'));
        }
    }

    /**
     * Creates Dummy data for testing
     *
     * @return string[]
     */
    public function getDummyData()
    {
        return [];
    }

}
