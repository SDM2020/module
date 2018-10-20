<?php

namespace SomethingDigital\Camsnet\Helper\System;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_MONEY_SETTINGS_API            = "money/settings/api_endpoint";

    const XML_MONEY_SETTINGS_AUTH_REQUIRED  = "money/settings/auth_required";

    const XML_MONEY_SETTINGS_USER           = "money/settings/username";

    const XML_MONEY_SETTINGS_PASS           = "money/settings/password";

    const XML_MONEY_SETTINGS_SSL            = "money/settings/skip_ssl";

    const XML_MONEY_SETTINGS_CERT           = "money/settings/cert_path";

    const XML_MONEY_SETTINGS_TEST           = "money/settings/test";

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string[]|null
     */
    private $settings = null;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getSettings($isHttps = true) 
    {
        if ($this->settings !== null) {
            return $this->settings;
        }
        $this->settings = [
            'host'              => $this->getSanitizedHost($isHttps),
            'auth_required'     => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_AUTH_REQUIRED),
            'api_endpoint'      => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_API),
            'user'              => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_USER),
            'pass'              => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_PASS),
            'skip_ssl'          => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_SSL),
            'cert_path'         => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_CERT),
            'test'              => $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_TEST),
        ];
        return $this->settings;
    }

    /**
     * Ensure that we're using https protocol
     * to mitigate any SSRF concerns
     */
    private function getSanitizedHost($isHttps) 
    {
        $url = $this->scopeConfig->getValue(self::XML_MONEY_SETTINGS_API);
        $parsedUrl = parse_url($url);
        $protocol = $isHttps ? 'https' : 'http';
        return $protocol . '://' . $parsedUrl['host'];
    }
}

