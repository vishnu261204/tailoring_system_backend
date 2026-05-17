<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            ['setting_key' => 'shop_name', 'setting_value' => 'Tailoring Shop', 'setting_type' => 'text'],
            ['setting_key' => 'shop_address', 'setting_value' => '123 Main Street, Your City', 'setting_type' => 'text'],
            ['setting_key' => 'shop_phone', 'setting_value' => '+91 9876543210', 'setting_type' => 'text'],
            ['setting_key' => 'shop_email', 'setting_value' => 'info@tailoringshop.com', 'setting_type' => 'text'],
            ['setting_key' => 'invoice_footer', 'setting_value' => 'Thank you for your business!', 'setting_type' => 'text'],
            ['setting_key' => 'whatsapp_enabled', 'setting_value' => 'false', 'setting_type' => 'boolean'],
            ['setting_key' => 'currency_symbol', 'setting_value' => '₹', 'setting_type' => 'text'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}