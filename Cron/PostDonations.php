<?php

namespace Money\DisasterRelief\Cron;

use Money\DisasterRelief\Lib\Client;
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
        DonationsRepositoryInterface $donationsRepo,
        SearchCriteriaBuilder $searchBuilder,
        LoggerInterface $logger
    ) {
        $this->client = $client;
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
            $donation->setPosted(true);
            $total += $donation->getAmount();
            $this->donationsRepo->save($donation);
            $payloadBody[] = $donation->getData();
        }

        if ($total === 0) {
            $this->logger->notice("Money_DisasterRelief: A total of " . count($donations) . " donations were parsed but no money was sent");
            return;
        }
        $payloadBody['total'] = $total;
        $this->client->post($payloadBody);
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
