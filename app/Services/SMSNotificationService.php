<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SMSNotificationService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('sms.default_provider', 'twilio');
        $this->config = config("sms.providers.{$this->provider}", []);
    }

    /**
     * Send SMS message
     *
     * @param string $phoneNumber Phone number in international format (e.g., +263771234567)
     * @param string $message Message content
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function send(string $phoneNumber, string $message): array
    {
        // Validate phone number
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if (!$this->isValidPhoneNumber($phoneNumber)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Invalid phone number format',
            ];
        }

        try {
            return match ($this->provider) {
                'twilio' => $this->sendViaTwilio($phoneNumber, $message),
                'africas_talking' => $this->sendViaAfricasTalking($phoneNumber, $message),
                'log' => $this->sendViaLog($phoneNumber, $message), // For testing
                default => [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'Invalid SMS provider configured',
                ],
            };
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => $this->provider,
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(string $phoneNumber, string $message): array
    {
        $accountSid = $this->config['account_sid'] ?? '';
        $authToken = $this->config['auth_token'] ?? '';
        $fromNumber = $this->config['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            throw new \Exception('Twilio configuration is incomplete');
        }

        $response = Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $fromNumber,
                'To' => $phoneNumber,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['sid'] ?? null,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'message_id' => null,
            'error' => $response->json()['message'] ?? 'Failed to send SMS via Twilio',
        ];
    }

    /**
     * Send SMS via Africa's Talking
     */
    protected function sendViaAfricasTalking(string $phoneNumber, string $message): array
    {
        $username = $this->config['username'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';
        $from = $this->config['from'] ?? '';

        if (empty($username) || empty($apiKey)) {
            throw new \Exception('Africa\'s Talking configuration is incomplete');
        }

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $username,
            'to' => $phoneNumber,
            'message' => $message,
            'from' => $from,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['SMSMessageData']['Recipients'][0])) {
                $recipient = $data['SMSMessageData']['Recipients'][0];

                if ($recipient['status'] === 'Success') {
                    return [
                        'success' => true,
                        'message_id' => $recipient['messageId'] ?? null,
                        'error' => null,
                    ];
                }

                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => $recipient['status'] ?? 'Unknown error',
                ];
            }
        }

        return [
            'success' => false,
            'message_id' => null,
            'error' => 'Failed to send SMS via Africa\'s Talking',
        ];
    }

    /**
     * Send SMS via Log (for testing)
     */
    protected function sendViaLog(string $phoneNumber, string $message): array
    {
        Log::info('SMS sent (log mode)', [
            'to' => $phoneNumber,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'message_id' => 'log-' . uniqid(),
            'error' => null,
        ];
    }

    /**
     * Normalize phone number to international format
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If number starts with 0, replace with country code (Zimbabwe = +263)
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '+263' . substr($phoneNumber, 1);
        }

        // If number doesn't start with +, add Zimbabwe country code
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+263' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Validate phone number format
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Basic validation: starts with + and has 10-15 digits
        return preg_match('/^\+[0-9]{10,15}$/', $phoneNumber) === 1;
    }

    /**
     * Send bulk SMS to multiple recipients
     *
     * @param array $recipients Array of ['phone' => string, 'message' => string]
     * @return array Results for each recipient
     */
    public function sendBulk(array $recipients): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $phone = $recipient['phone'] ?? '';
            $message = $recipient['message'] ?? '';

            if (empty($phone) || empty($message)) {
                $results[] = [
                    'phone' => $phone,
                    'success' => false,
                    'error' => 'Missing phone or message',
                ];
                continue;
            }

            $result = $this->send($phone, $message);
            $results[] = array_merge(['phone' => $phone], $result);
        }

        return $results;
    }

    /**
     * Generate payslip notification message
     */
    public static function generatePayslipMessage(string $employeeName, string $period, string $downloadUrl): string
    {
        return "Hi {$employeeName}, your payslip for {$period} is ready. Download it securely here: {$downloadUrl} (Link expires in 7 days)";
    }
}
