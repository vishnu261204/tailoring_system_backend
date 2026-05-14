<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    const TEMPLATE_ORDER_CREATED = 'order_created';

    const TEMPLATE_TRIAL_REMINDER = 'trial_reminder';

    const TEMPLATE_ORDER_COMPLETED = 'order_completed';

    const TEMPLATE_ORDER_DELIVERED = 'order_delivered';

    private function baseUrl(): string
    {
        $version = config('services.whatsapp.api_version', 'v22.0');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";
    }

    private function token(): string
    {
        $token = config('services.whatsapp.api_token');

        if (empty($token)) {
            throw new \RuntimeException('WhatsApp API token is not configured');
        }

        return $token;
    }

    public function sendMessage(string $phone, string $template, array $variables, ?int $orderId = null): bool
    {
        $phone = $this->formatPhone($phone);

        if (empty(config('services.whatsapp.api_token'))) {
            Log::info('WhatsApp notification skipped - no API token configured');

            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => [
                    'code' => 'en',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => array_map(fn ($value) => [
                            'type' => 'text',
                            'text' => (string) $value,
                        ], $variables),
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($this->token())
                ->timeout(15)
                ->post($this->baseUrl(), $payload);

            $responseBody = $response->json();

            if ($response->successful()) {
                MessageLog::create([
                    'order_id' => $orderId,
                    'phone' => $phone,
                    'message' => $template,
                    'status' => 'sent',
                    'response' => json_encode($responseBody),
                ]);

                return true;
            }

            MessageLog::create([
                'order_id' => $orderId,
                'phone' => $phone,
                'message' => $template,
                'status' => 'failed',
                'response' => json_encode($responseBody ?? $response->body()),
            ]);

            Log::warning('WhatsApp message failed', [
                'template' => $template,
                'phone' => $phone,
                'response' => $responseBody,
            ]);

            return false;
        } catch (\Throwable $e) {
            MessageLog::create([
                'order_id' => $orderId,
                'phone' => $phone,
                'message' => $template,
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);

            Log::error('WhatsApp message exception', [
                'template' => $template,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendOrderCreated(Order $order): bool
    {
        return $this->sendMessage(
            $order->customer->phone,
            self::TEMPLATE_ORDER_CREATED,
            [$order->customer->name, $order->order_id],
            $order->id
        );
    }

    public function sendTrialReminder(Order $order): bool
    {
        $trialDate = $order->trial_date?->format('d M Y');

        return $this->sendMessage(
            $order->customer->phone,
            self::TEMPLATE_TRIAL_REMINDER,
            [$order->customer->name, $order->order_id, $trialDate],
            $order->id
        );
    }

    public function sendOrderCompleted(Order $order): bool
    {
        return $this->sendMessage(
            $order->customer->phone,
            self::TEMPLATE_ORDER_COMPLETED,
            [$order->customer->name, $order->order_id],
            $order->id
        );
    }

    public function sendOrderDelivered(Order $order): bool
    {
        return $this->sendMessage(
            $order->customer->phone,
            self::TEMPLATE_ORDER_DELIVERED,
            [$order->customer->name, $order->order_id],
            $order->id
        );
    }

    private function formatPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($cleaned) === 10) {
            return '91'.$cleaned;
        }

        return $cleaned;
    }

    public function formatPhoneForWhatsApp(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($cleaned) === 10) {
            return '91'.$cleaned;
        }

        if (str_starts_with($cleaned, '91') && strlen($cleaned) === 12) {
            return $cleaned;
        }

        return $cleaned;
    }
}
