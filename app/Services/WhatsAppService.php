<?php
// app/Services/WhatsAppService.php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService  
{
    protected $apiToken;
    protected $phoneNumberId;
    protected $apiVersion;
    protected $isEnabled;

    public function __construct()
    {
        $this->isEnabled = SystemSetting::getValue('whatsapp_enabled', false);
        $this->apiToken = SystemSetting::getValue('whatsapp_api_key', '');
        $this->phoneNumberId = SystemSetting::getValue('whatsapp_phone_number_id', '');
        $this->apiVersion = SystemSetting::getValue('whatsapp_api_version', 'v17.0');
    }

    /**
     * Send WhatsApp message using WhatsApp Cloud API
     */
    public function sendMessage($to, $message, $templateName = null, $templateData = [])
    {
        if (!$this->isEnabled) {
            Log::info('WhatsApp is disabled. Message would be sent: ' . $message);
            return [
                'success' => true,
                'mock' => true,
                'message' => 'WhatsApp is disabled (mock mode)'
            ];
        }

        if (empty($this->apiToken) || empty($this->phoneNumberId)) {
            Log::error('WhatsApp API credentials not configured');
            return [
                'success' => false,
                'error' => 'WhatsApp API credentials not configured'
            ];
        }

        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            // Format phone number (remove any non-numeric characters)
            $to = $this->formatPhoneNumber($to);

            // Prepare payload
            if ($templateName) {
                // Send template message
                $payload = $this->prepareTemplatePayload($to, $templateName, $templateData);
            } else {
                // Send text message
                $payload = $this->prepareTextPayload($to, $message);
            }

            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'message_id' => $responseData['messages'][0]['id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                Log::error('WhatsApp API Error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'Unknown error',
                    'response' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send template message (for notifications)
     */
    public function sendTemplateMessage($to, $templateName, $language = 'en', $components = [])
    {
        if (!$this->isEnabled) {
            Log::info('WhatsApp is disabled. Template would be sent: ' . $templateName);
            return [
                'success' => true,
                'mock' => true,
                'message' => 'WhatsApp is disabled (mock mode)'
            ];
        }

        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
            $to = $this->formatPhoneNumber($to);

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language
                    ]
                ]
            ];

            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'message_id' => $responseData['messages'][0]['id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                Log::error('WhatsApp Template API Error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp Template Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send order created notification
     */
    public function sendOrderCreated($order)
    {
        $customer = $order->customer;
        $message = "✅ *ORDER CONFIRMATION*\n\n";
        $message .= "Dear {$customer->name},\n\n";
        $message .= "Your tailoring order #{$order->order_number} has been received.\n";
        $message .= "📅 Order Date: " . $order->order_date->format('d-m-Y') . "\n";
        $message .= "💰 Total Amount: ₹" . number_format($order->total_amount, 2) . "\n";
        $message .= "💵 Advance Paid: ₹" . number_format($order->advance_paid, 2) . "\n";
        $message .= "⚖️ Balance: ₹" . number_format($order->balance_amount, 2) . "\n\n";
        $message .= "We will notify you once it's ready for trial.\n\n";
        $message .= "Thank you for choosing us! 🙏";

        return $this->sendMessage($customer->phone, $message);
    }

    /**
     * Send trial reminder
     */
    public function sendTrialReminder($order)
    {
        $customer = $order->customer;
        $message = "🔔 *TRIAL REMINDER*\n\n";
        $message .= "Dear {$customer->name},\n\n";
        $message .= "Reminder: Your tailoring order #{$order->order_number} trial is scheduled on:\n";
        $message .= "📅 *" . $order->trial_date->format('d-m-Y') . "*\n\n";
        $message .= "📍 Store Address: " . SystemSetting::getValue('shop_address', 'Our Store') . "\n";
        $message .= "⏰ Time: " . SystemSetting::getValue('shop_hours', '10 AM - 8 PM') . "\n\n";
        $message .= "Please visit our store for trial.\n\n";
        $message .= "Thank you! 👔";

        return $this->sendMessage($customer->phone, $message);
    }

    /**
     * Send order completed notification
     */
    public function sendOrderCompleted($order)
    {
        $customer = $order->customer;
        $message = "✅ *ORDER READY FOR PICKUP*\n\n";
        $message .= "Dear {$customer->name},\n\n";
        $message .= "Great news! Your tailoring order #{$order->order_number} is complete and ready for pickup.\n\n";
        $message .= "📍 *Store Address:*\n" . SystemSetting::getValue('shop_address', 'Our Store') . "\n\n";
        $message .= "⏰ *Store Hours:* " . SystemSetting::getValue('shop_hours', '10 AM - 8 PM') . "\n\n";
        $message .= "Please collect at your earliest convenience.\n\n";
        $message .= "Thank you for your patience! 🎉";

        return $this->sendMessage($customer->phone, $message);
    }

    /**
     * Send delivery thank you message
     */
    public function sendDeliveryThanks($order)
    {
        $customer = $order->customer;
        $shopName = SystemSetting::getValue('shop_name', 'Tailoring Shop');
        
        $message = "🙏 *THANK YOU*\n\n";
        $message .= "Dear {$customer->name},\n\n";
        $message .= "Thank you for choosing our tailoring service!\n";
        $message .= "We hope you love your new outfit.\n\n";
        $message .= "We value your feedback. Please share your experience with us.\n\n";
        $message .= "Have a great day!\n\n";
        $message .= "- {$shopName} Team 🌟";

        return $this->sendMessage($customer->phone, $message);
    }

    /**
     * Send custom message
     */
    public function sendCustomMessage($phone, $message)
    {
        return $this->sendMessage($phone, $message);
    }

    /**
     * Prepare text message payload
     */
    private function prepareTextPayload($to, $message)
    {
        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];
    }

    /**
     * Prepare template payload
     */
    private function prepareTemplatePayload($to, $templateName, $components = [])
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en'
                ]
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $payload;
    }

    /**
     * Format phone number for WhatsApp API
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove leading zero if present
        $phone = ltrim($phone, '0');
        
        // Add country code if not present (assuming India +91)
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return $phone;
    }

    /**
     * Get message status from WhatsApp
     */
    public function getMessageStatus($messageId)
    {
        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$messageId}";
            
            $response = Http::withToken($this->apiToken)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json()['status'] ?? 'unknown'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to get message status'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Webhook handler for incoming messages
     */
    public function handleWebhook($payload)
    {
        try {
            // Parse the incoming webhook
            if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
                $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];
                $from = $message['from'];
                $text = $message['text']['body'] ?? '';
                
                // Process the incoming message
                // Here you can implement auto-reply or other logic
                
                Log::info('WhatsApp webhook received', [
                    'from' => $from,
                    'message' => $text
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Webhook processed',
                    'data' => $message
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid webhook payload'
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}