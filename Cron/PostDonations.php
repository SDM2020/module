<?php

namespace Money\DisasterRelief\Cron;

use Experius\DonationProduct\Api\DonationsRepositoryInterface;
use Experius\DonationProduct\Api\Data\DonationsSearchResultsInterface;
use Experius\DonationProduct\Api\Data\DonationsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface 
use Psr\Log\LoggerInterface;

class PostDonations
{
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
        DonationsRepositoryInterface $donationsRepo,
        SearchCriteriaBuilder $searchBuilder,
        LoggerInterface $logger
    ) {
        $this->donationsRepo = $donationsRepo;
        $this->searchBuilder = $searchBuilder;
        $this->logger = $logger;
    }

    public function execute()
    {
        /** @var DonationsInterface[] $donations */
        $donations = $this->getAllNonPostedDonations();
        if (empty($donations)) {
            return;
        }
        
        $payloadBody = [];
        foreach ($donations as $donation) {
            $payloadBody[] = $donation->getData();
        }

        // $this->client->post($payloadBody);
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
