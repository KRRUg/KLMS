<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class SumupService
{
    private readonly SettingService $settingService;
    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, SettingService $settingService)
    {
        $this->logger = $logger;
        $this->settingService = $settingService;
    }

    private function init(): ?\SumUp\SumUp
    {
        if ($this->settingService->get('lan.signup.payment_sumupenabled') == true) {
            return (new \SumUp\SumUp([
                'app_id'     => $this->settingService->get('lan.signup.payment_sumupclientid'),
                'app_secret' => $this->settingService->get('lan.signup.payment_sumupclientsecret'),
                'grant_type' => 'client_credentials'
            ]));
        } else {
            $this->logger->info('SumUp is not enabled in settings.');
            return null;
        }
    }

    /**
     * Create a new checkout at SumUp.
     * Returns the Checkout-Id as string or throws on error.
     */
    public function createCheckout(float $amount, string $currency, string $checkoutRef, string $payToEmail, string $description, string $payFromEmail): ?string
    {
        try {
            $sumup = $this->init();
            if ($sumup !== null) {
                $checkoutService = $sumup->getCheckoutService();
                $response = $checkoutService->create($amount, $currency, $checkoutRef, $payToEmail, $description, $payFromEmail);
                return $response->getBody()->id;
            } else {
                return null;
            }   
        } catch (\Throwable $e) {
            $this->logger->error('SumUp createCheckout failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve checkout details from SumUp.
     */
    public function retrieveCheckout(string $checkoutId)
    {
        try {
            $sumup = $this->init();
            if ($sumup !== null) 
            {
                $checkoutService = $sumup->getCheckoutService();
                $response = $checkoutService->findById($checkoutId);
                return $response->getBody();
            } else {
                return null;
            }
        } catch (\Throwable $e) {
            $this->logger->error('SumUp retrieveCheckout failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a checkout at SumUp and return the raw SDK response.
     */
    public function delete(string $checkoutId)
    {
        try {
            $sumup = $this->init();
            if ($sumup !== null) 
            {
                $checkoutService = $sumup->getCheckoutService();
                $response = $checkoutService->delete($checkoutId);
            }
        } catch (\Throwable $e) {
            $this->logger->error('SumUp delete checkout failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
