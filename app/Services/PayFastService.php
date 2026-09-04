<?php

declare(strict_types=1);

namespace Unwinded\Services;

class PayFastService
{
    private string $merchantId;
    private string $merchantKey;
    private string $passphrase;
    private bool   $sandbox;

    public function __construct()
    {
        $this->merchantId  = (string) setting('payfast.merchant_id', '');
        $this->merchantKey = (string) setting('payfast.merchant_key', '');
        $this->passphrase  = (string) setting('payfast.passphrase', '');
        $this->sandbox     = (bool) setting('payfast.sandbox', '1');
    }

    public function gatewayUrl(): string
    {
        return $this->sandbox
            ? 'https://sandbox.payfast.co.za/eng/process'
            : 'https://www.payfast.co.za/eng/process';
    }

    /**
     * Build the signed field array to POST to PayFast.
     * All amounts in Rand (e.g. "150.00"), never cents.
     */
    public function buildPaymentFields(
        string $amountRand,
        string $itemName,
        string $itemDescription,
        string $buyerFirstName,
        string $buyerLastName,
        string $buyerEmail,
        string $returnUrl,
        string $cancelUrl,
        string $notifyUrl,
        string $customStr1 = '',
    ): array {
        $fields = [
            'merchant_id'   => $this->merchantId,
            'merchant_key'  => $this->merchantKey,
            'return_url'    => $returnUrl,
            'cancel_url'    => $cancelUrl,
            'notify_url'    => $notifyUrl,
            'name_first'    => substr($buyerFirstName, 0, 100),
            'name_last'     => substr($buyerLastName, 0, 100),
            'email_address' => $buyerEmail,
            'amount'        => number_format((float) $amountRand, 2, '.', ''),
            'item_name'     => substr($itemName, 0, 255),
            'item_description' => substr($itemDescription, 0, 255),
        ];

        if ($customStr1 !== '') {
            $fields['custom_str1'] = substr($customStr1, 0, 255);
        }

        $fields['signature'] = $this->signature($fields);
        return $fields;
    }

    /**
     * Verify a PayFast ITN request.
     * Returns true only if ALL checks pass.
     * Never trusts the browser — only the server-to-server POST is considered valid.
     */
    public function verifyItn(array $postData, string $rawPost, string $remoteIp): bool
    {
        // 1. Validate source IP (PayFast IP ranges)
        if (!$this->isPayFastIp($remoteIp)) {
            return false;
        }

        // 2. Validate signature
        $receivedSignature = $postData['signature'] ?? '';
        unset($postData['signature']);
        $expectedSignature = $this->signature($postData);
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            return false;
        }

        // 3. Server-side validation request back to PayFast
        $validateUrl = $this->sandbox
            ? 'https://sandbox.payfast.co.za/eng/query/validate'
            : 'https://www.payfast.co.za/eng/query/validate';

        $response = $this->curlPost($validateUrl, $rawPost);
        if (strtoupper(trim($response)) !== 'VALID') {
            return false;
        }

        return true;
    }

    public function signature(array $fields): string
    {
        ksort($fields);
        $parts = [];
        foreach ($fields as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $parts[] = urlencode($k) . '=' . urlencode($v);
        }
        $str = implode('&', $parts);
        if ($this->passphrase !== '') {
            $str .= '&passphrase=' . urlencode($this->passphrase);
        }
        return md5($str);
    }

    private function isPayFastIp(string $ip): bool
    {
        // PayFast production IP ranges (updated as of 2024); sandbox also included
        $allowed = [
            '197.97.145.144/28',
            '41.74.179.192/27',
            '41.203.25.240/29',
            '154.0.166.192/27',
            '196.33.227.224/27',
            '196.34.121.16/28',
            '41.203.25.241',  // sandbox
            '127.0.0.1',      // local dev / sandbox
        ];

        foreach ($allowed as $range) {
            if (str_contains($range, '/')) {
                if ($this->cidrMatch($ip, $range)) {
                    return true;
                }
            } elseif ($ip === $range) {
                return true;
            }
        }

        // Fallback for sandbox mode: allow any IP
        return $this->sandbox;
    }

    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $mask = -1 << (32 - (int) $bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function curlPost(string $url, string $body): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => !$this->sandbox,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return is_string($result) ? $result : '';
    }
}
