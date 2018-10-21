<?php

namespace Money\DisasterRelief\Cron;

use Money\DisasterRelief\Lib\Client;
use Money\DisasterRelief\Helper\System\Config;
use Experius\DonationProduct\Api\DonationsRepositoryInterface;
use Experius\DonationProduct\Api\Data\DonationsSearchResultsInterface;
use Experius\DonationProduct\Api\Data\DonationsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Psr\Log\LoggerInterface;

class PostDonations
{
    /**
     * @var Client 
     */
    private $client;

    /**
     * @var Config 
     */
    private $config;

    /**
     * @var DonationsRepositoryInterface 
     */
    private $donationsRepo;

    /**
     * @var SearchCriteriaBuilder 
     */
    private $searchBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Client $client,
        Config $config,
        DonationsRepositoryInterface $donationsRepo,
        SearchCriteriaBuilder $searchBuilder,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->donationsRepo = $donationsRepo;
        $this->searchBuilder = $searchBuilder;
        $this->logger = $logger;
    }

    public function execute()
    {
        /** @var DonationsInterface[] $donations */
        $donations = $this->getAllNonPostedDonations();
        if (empty($donations)) {
            // nothing to do here!
            return;
        }
        
        /** @var int $total */
        $total = 0;
        /** @var mixed[] $payloadBody */
        $payloadBody = [];
        /** @var DonationsInterface $donation */
        foreach ($donations as $donation) {
            // @TODO $donation->setPosted(true);
            $total += $donation->getAmount();
            try {
                $this->donationsRepo->save($donation);
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        if ($total === 0) {
            $this->logger->notice("Money_DisasterRelief: A total of " . count($donations) . " donations were parsed but no money was sent");
            return;
        }
        $settings = $this->config->getSettings();
        $payloadBody = [
            "amount" => $total,
            "currency" => "USD",
            "merchant_name" => "Magento",
            "fundId" => "asdl8ewSDKGagadskvj"
        ];
        // $this->client->post($settings['host'], $payloadBody);

        $this->client->post(
            $settings['api_endpoint'],
            [
                "magneticStripeData" => [
                    "track2Data" => "4008310000000007D130310191014085"
                ],
                "acquirerCountryCode" => "840",
                "pointOfServiceCapability" => [
                    "posTerminalType" => 4,
                    "posTerminalEntryCapability" => 2
                ],
                "pinData" => [
                    "securityRelatedControlInfo" => [
                        "pinBlockFormatCode" => 1,
                        "zoneKeyIndex" => 1
                    ],
                    "pinDataBlock" => "1cd948f2b961b682"
                ],
                "acquiringBin" => "408999",
                "amount" => 350,
                "businessApplicationId" => "AA",
                "cardAcceptor" => [
                    "address" => [
                        "country" => "USA",
                        "county" => "San Mateo",
                        "state" => "CA",
                        "zipCode" => "94404"
                    ],
                    "idCode" => "VMT200911026070",
                    "name" => "Acceptor 1",
                    "terminalId" => "365539"
                ],
                "localTransactionDateTime" => "2018-10-20T22:07:58",
                "merchantCategoryCode" => "6012",
                "feeProgramIndicator" => 123,
                "pointOfServiceData" => [
                    "motoECIIndicator" => "0",
                    "panEntryMode" => "90",
                    "posConditionCode" => "0"
                ],
                "recipientName" => "rohan",
                "recipientPrimaryAccountNumber" => "4957030420210462",
                "retrievalReferenceNumber" => "330000550000",
                "senderAccountNumber" => "4957030420210454",
                "senderAddress" => "901 Metro Center Blvd",
                "senderCity" => "Foster City",
                "senderCountryCode" => "124",
                "senderName" => "Mohammed Qasim",
                "senderReference" => "",
                "senderStateCode" => "CA",
                "sourceOfFundsCode" => "05",
                "systemsTraceAuditNumber" => "451000",
                "transactionCurrencyCode" => "USD",
                "transactionIdentifier" => "381228649430011"
            ]
            // [
            //     "magneticStripeData" => [
            //         "track1Data" => "1010101010101010101010101010",
            //         "track2Data" => "4008310000000007D130310191014085",
            //     ],
            //     "pullCardAcceptor" => [
            //         "idCode" => "VMT200911086070",
            //         "terminalId" => "365529",
            //     ],
            //     "pullSystemsTraceAuditNumber" => "792155",
            //     "pullRetrievalReferenceNumber" => "717311813559",
            //     "pushRetrievalReferenceNumber" => "717311813560",
            //     "acquirerCountryCode" => "840",
            //     "pointOfServiceCapability" => [
            //         "posTerminalType" => 4,
            //         "posTerminalEntryCapability" => 2,
            //     ],
            //     "pushCardAcceptor" => [
            //         "idCode" => "VMT200911026070",
            //         "terminalId" => "375539",
            //     ],
            //     "Cavv" => "0000010926000071934977253000000000000000",
            //     "pinData" => [
            //         "securityRelatedControlInfo" => [
            //             "pinBlockFormatCode" => 1,
            //             "zoneKeyIndex" => 1,
            //         ],
            //         "pinDataBlock" => "1cd948f2b961b682",
            //     ],
            //     "acquiringBin" => "408999",
            //     "amount" => 350,
            //     "businessApplicationId" => "AA",
            //     "cardAcceptor" => [
            //         "address" => [
            //             "country" => "USA",
            //             "county" => "San Mateo",
            //             "state" => "CA",
            //             "zipCode" => "94404"
            //         ],
            //         "idCode" => "VMT200911026070",
            //         "name" => "Acceptor 1",
            //         "terminalId" => "365539"
            //     ],
            //     "localTransactionDateTime" => "2018-10-20T22:07:58",
            //     "merchantCategoryCode" => "6012",
            //     "feeProgramIndicator" => 123,
            //     "pointOfServiceData" => [
            //         "motoECIIndicator" => "0",
            //         "panEntryMode" => "90",
            //         "posConditionCode" => "0"
            //     ],
            //     "pushSystemsTraceAuditNumber" => "806805",
            //     "recipientName" => "rohan",
            //     "recipientPrimaryAccountNumber" => "4957030420210462",
            //     "retrievalReferenceNumber" => "330000550000",
            //     "senderAccountNumber" => "4957030420210454",
            //     "senderAddress" => "901 Metro Center Blvd",
            //     "senderCity" => "Foster City",
            //     "senderCountryCode" => "124",
            //     "senderName" => "Mohammed Qasim",
            //     "senderReference" => "",
            //     "senderStateCode" => "CA",
            //     "sourceOfFundsCode" => "05",
            //     "systemsTraceAuditNumber" => "451000",
            //     "transactionCurrencyCode" => "USD",
            //     "transactionIdentifier" => "381228649430011"
            // ]
        );
    }

    /**
     * @throws LocalizedException
     * @return DonationsInterface[]
     */
    private function getAllNonPostedDonations() 
    {
        try {
            $this->searchBuilder->addFilter('posted', false);
            /** @var DonationsSearchResultsInterface $donations */
            $donations = $this->donationsRepo->getList($this->searchBuilder->create());
            return $donations->getItems();
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return [];
        }
    }
}
