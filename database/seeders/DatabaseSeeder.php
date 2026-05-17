<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\MessageTemplate;
use App\Models\SystemSetting;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create users
        User::create([
            'username' => 'admin',
            'email' => 'admin@tailoring.com',
            'password' => Hash::make('Admin@123'),
            'full_name' => 'Administrator',
            'role' => 'admin',
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'staff1',
            'email' => 'staff@tailoring.com',
            'password' => Hash::make('Staff@123'),
            'full_name' => 'Staff Member',
            'role' => 'staff',
            'phone' => '9876543211',
            'is_active' => true,
        ]);

        // Create customers
        Customer::create([
            'customer_code' => 'CUST001',
            'name' => 'Rajesh Kumar',
            'phone' => '9876543212',
            'email' => 'rajesh@email.com',
            'address' => '123 Main Street, Chennai',
            'gender' => 'male',
        ]);

        Customer::create([
            'customer_code' => 'CUST002',
            'name' => 'Priya Sharma',
            'phone' => '9876543213',
            'email' => 'priya@email.com',
            'address' => '456 Park Avenue, Mumbai',
            'gender' => 'female',
        ]);

        // Create inventory
        $inventory = [
            ['fabric_code' => 'FAB001', 'fabric_name' => 'Cotton Premium', 'fabric_type' => 'Cotton', 'color' => 'White', 'quantity' => 100, 'cost_per_meter' => 250.00, 'selling_price_per_meter' => 350.00],
            ['fabric_code' => 'FAB002', 'fabric_name' => 'Silk Blend', 'fabric_type' => 'Silk', 'color' => 'Maroon', 'quantity' => 50, 'cost_per_meter' => 500.00, 'selling_price_per_meter' => 750.00],
            ['fabric_code' => 'FAB003', 'fabric_name' => 'Linen Casual', 'fabric_type' => 'Linen', 'color' => 'Beige', 'quantity' => 75, 'cost_per_meter' => 350.00, 'selling_price_per_meter' => 500.00],
        ];

        foreach ($inventory as $item) {
            Inventory::create($item);
        }

        // Create message templates
        $templates = [
            ['template_code' => 'ORDER_CREATED_EN', 'template_type' => 'order_created', 'language' => 'en', 'subject' => 'Order Confirmation', 'body' => 'Dear {customer_name},\n\nYour tailoring order #{order_number} has been received.\n📅 Order Date: {order_date}\n💰 Total Amount: ₹{total_amount}\n💵 Advance Paid: ₹{advance_paid}\n⚖️ Balance: ₹{balance}\n\nWe will notify you once it\'s ready for trial.\n\nThank you for choosing us!', 'variables' => json_encode(['customer_name', 'order_number', 'order_date', 'total_amount', 'advance_paid', 'balance'])],
            ['template_code' => 'ORDER_CREATED_TA', 'template_type' => 'order_created', 'language' => 'ta', 'subject' => 'ஆர்டர் உறுதிப்பாடு', 'body' => 'அன்புள்ள {customer_name},\n\nஉங்கள் தையல் ஆர்டர் #{order_number} பெறப்பட்டது.\n📅 ஆர்டர் தேதி: {order_date}\n💰 மொத்த தொகை: ₹{total_amount}\n💵 முன்பணம்: ₹{advance_paid}\n⚖️ இருப்பு: ₹{balance}\n\nஆர்டர் தயாரானதும் உங்களுக்கு தெரிவிக்கப்படும்.\n\nநன்றி!', 'variables' => json_encode(['customer_name', 'order_number', 'order_date', 'total_amount', 'advance_paid', 'balance'])],
            ['template_code' => 'TRIAL_REMINDER_EN', 'template_type' => 'trial_reminder', 'language' => 'en', 'subject' => 'Trial Reminder', 'body' => 'Dear {customer_name},\n\nReminder: Your tailoring order #{order_number} trial is scheduled on {trial_date}.\n\nPlease visit our store for trial.\n\nThank you.', 'variables' => json_encode(['customer_name', 'order_number', 'trial_date'])],
            ['template_code' => 'ORDER_COMPLETED_EN', 'template_type' => 'order_completed', 'language' => 'en', 'subject' => 'Order Ready for Pickup', 'body' => 'Dear {customer_name},\n\nGreat news! Your tailoring order #{order_number} is complete and ready for pickup.\n\n📍 Store Address: {shop_address}\n⏰ Store Hours: {shop_hours}\n\nPlease collect at your earliest convenience.\n\nThank you for your patience!', 'variables' => json_encode(['customer_name', 'order_number', 'shop_address', 'shop_hours'])],
        ];

        foreach ($templates as $template) {
            MessageTemplate::create($template);
        }

        // Create system settings
        $settings = [
            ['setting_key' => 'shop_name', 'setting_value' => 'Premium Tailoring Shop', 'setting_type' => 'text', 'group_name' => 'general', 'description' => 'Name of the tailoring shop'],
            ['setting_key' => 'shop_address', 'setting_value' => '123 Main Street, Anna Nagar, Chennai - 600001', 'setting_type' => 'text', 'group_name' => 'general', 'description' => 'Shop physical address'],
            ['setting_key' => 'shop_phone', 'setting_value' => '+91 9876543210', 'setting_type' => 'text', 'group_name' => 'general', 'description' => 'Contact phone number'],
            ['setting_key' => 'whatsapp_enabled', 'setting_value' => 'true', 'setting_type' => 'boolean', 'group_name' => 'whatsapp', 'description' => 'Enable WhatsApp notifications'],
            ['setting_key' => 'currency_symbol', 'setting_value' => '₹', 'setting_type' => 'text', 'group_name' => 'general', 'description' => 'Currency symbol'],
            ['setting_key' => 'tax_percentage', 'setting_value' => '0', 'setting_type' => 'number', 'group_name' => 'billing', 'description' => 'Tax percentage to apply on orders'],
            ['setting_key' => 'auto_whatsapp_order_created', 'setting_value' => 'true', 'setting_type' => 'boolean', 'group_name' => 'whatsapp', 'description' => 'Auto send WhatsApp on order creation'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}