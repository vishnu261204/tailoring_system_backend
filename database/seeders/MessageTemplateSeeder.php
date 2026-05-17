<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MessageTemplate;

class MessageTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            ['template_type' => 'order_created', 'body' => 'Dear {customer_name},\n\nYour tailoring order #{order_number} has been received. Total amount: ₹{total_amount}. We will notify you once it\'s ready.\n\nThank you for choosing us!', 'language' => 'en'],
            ['template_type' => 'order_created_ta', 'body' => 'அன்புள்ள {customer_name},\n\nஉங்கள் தயாரிப்பு ஆர்டர் #{order_number} பெறப்பட்டது. மொத்த தொகை: ₹{total_amount}. ஆர்டர் தயாரானதும் உங்களுக்கு தெரிவிக்கப்படும்.\n\nநன்றி!', 'language' => 'ta'],
            ['template_type' => 'trial_reminder', 'body' => 'Dear {customer_name},\n\nReminder: Your tailoring order #{order_number} trial is scheduled on {trial_date}. Please visit our store.\n\nThank you.', 'language' => 'en'],
            ['template_type' => 'order_completed', 'body' => 'Dear {customer_name},\n\nGreat news! Your tailoring order #{order_number} is complete and ready for pickup.\n\nPlease collect at your earliest convenience.', 'language' => 'en'],
            ['template_type' => 'delivery_thanks', 'body' => 'Dear {customer_name},\n\nThank you for choosing our tailoring service! We hope you love your new outfit.\n\nHave a great day!', 'language' => 'en'],
        ];

        foreach ($templates as $template) {
            MessageTemplate::create($template);
        }
    }
}