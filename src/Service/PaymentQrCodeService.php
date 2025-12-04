<?php

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class PaymentQrCodeService
{
    private string $publicDir;

    public function __construct(string $publicDir)
    {
        $this->publicDir = $publicDir;
    }

    /**
     * Generate a banking QR code for EPC069-12 standard (SEPA)
     * 
     * @param string $accountHolder
     * @param string $iban
     * @param string $bic
     * @param float $amount Amount in EUR
     * @param string $reference Payment reference/purpose
     * @return string Path to the generated QR code image (relative to public dir)
     */
    public function generateBankingQrCode(
        string $accountHolder,
        string $iban,
        string $bic,
        float $amount,
        string $reference
    ): string {
        // Create filename based on amount
        $filename = sprintf('banking-qr-%.2fEUR.png', $amount);
        $filePath = $this->publicDir . '/images/banking-qr/' . $filename;

        // Check if file already exists
        if (file_exists($filePath)) {
            return '/images/banking-qr/' . $filename;
        }

        // Ensure directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Format IBAN (remove spaces)
        $iban = str_replace(' ', '', trim($iban));
        
        // Generate EPC QR Code data (SEPA payment standard)
        $qrData = $this->generateEpcQrData($accountHolder, $iban, $bic, $amount, $reference);

        // Create QR code
        $qrCode = new QrCode(
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        // Write to PNG file
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        file_put_contents($filePath, $result->getString());

        return '/images/' . $filename;
    }

    /**
     * Generate EPC069-12 compliant QR code data
     */
    private function generateEpcQrData(
        string $accountHolder,
        string $iban,
        string $bic,
        float $amount,
        string $reference
    ): string {
        $lines = [
            'BCD',                           // Service Tag
            '002',                           // Version
            '1',                             // Character Set (1 = UTF-8)
            'SCT',                           // Identification
            $bic ?: '',                      // BIC (can be empty for SEPA)
            substr($accountHolder, 0, 70),   // Beneficiary Name (max 70 chars)
            $iban,                           // Beneficiary Account (IBAN)
            sprintf('EUR%.2f', $amount),     // Amount (EUR with 2 decimals)
            '',                              // Purpose (empty)
            '',                              // Structured Reference (empty)
            substr($reference, 0, 140),      // Unstructured Remittance (max 140 chars)
            '',                              // Beneficiary to Originator Information
        ];

        return implode("\n", $lines);
    }

    /**
     * Delete all generated QR codes (useful for cleanup or when payment details change)
     */
    public function deleteAllQrCodes(): void
    {
        $pattern = $this->publicDir . '/images/banking-qr/banking-qr-*.png';
        $files = glob($pattern);
        
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
