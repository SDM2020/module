<?php

namespace Money\DisasterRelief\Lib;

use Money\DisasterRelief\Helper\System\Config;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Client
{
    const PORT = 443;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CurlFactory $curlFactory,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->curlFactory = $curlFactory;
        $this->config = $config;
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
        /** @var bool $isHttps */
        $isHttps = true; // @TODO should this be a config?
        /** @var string[] $settings */
        $settings = $this->config->getSettings($isHttps);
        /** @var \Magento\Framework\HTTP\Client\Curl $curl */
        $curl = $this->curlFactory->create();
        if (!!$settings['test']) {
            return $this->getDummyData();
        } else if (!!$settings['auth_required']) {
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
            $curl->setOption(CURLOPT_SSLCERT, $settings['cert_path']);
            $curl->setOption(CURLOPT_SSLKEY, $settings['key_path']);
            $curl->setOption(CURLOPT_SSLCERTPASSWD, $settings['cert_pass']);
        }
        $curl->addHeader('Content-Type', 'application/json');
        try {
            $curl->post($endpoint, $payload);
            try {
                $body = \Zend_Json::decode($curl->getBody());
            } catch (\Exception $e) {
                $this->logger->error("Money_DisasterRelief: " . $e->getMessage());
                // Handle non JSON response
                $body = $curl->getBody();
            }
            $response = [
                'status' => $curl->getStatus(),
                'body' => $body,
            ];

            if ($response['status'] >= 300 || $response['status'] < 200) {
                $this->logger->error("Money_DisasterRelief: Body:" . json_encode($body));
                throw new LocalizedException(__('Internal error during request to API'));
            }

            return $response;

        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Requst failed with exception: %s; request data: %s; response: %s',
                    $e->getMessage(),
                    json_encode($payload),
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

