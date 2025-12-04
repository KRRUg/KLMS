<?php

namespace App\Twig;

use App\Service\PaymentQrCodeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaymentExtension extends AbstractExtension
{
    private PaymentQrCodeService $qrCodeService;

    public function __construct(PaymentQrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('payment_qr_code', [$this, 'generatePaymentQrCode']),
        ];
    }

    public function generatePaymentQrCode(
        string $accountHolder,
        string $iban,
        string $bic,
        float $amount,
        string $reference
    ): string {
        return $this->qrCodeService->generateBankingQrCode(
            $accountHolder,
            $iban,
            $bic,
            $amount,
            $reference
        );
    }
}
