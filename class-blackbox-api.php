<?php
/**
 * Blackbox API Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Blackbox_API {
    
    private $api_key;
    private $model;
    private $base_url = 'https://api.blackbox.ai/v1/chat/completions';
    private $timeout = 300;

    public function __construct() {
        $this->api_key = trim(get_option('pbr_blackbox_api_key', ''));
        $this->model = get_option('pbr_claude_model', 'blackboxai/x-ai/grok-code-fast-1:free');
    }
    
    /**
     * Get primary color for content generation
     */
    private function get_primary_color() {
        if (get_option('pbr_use_theme_color') === 'yes') {
            $theme_color = get_theme_mod('primary_color');
            if (!empty($theme_color)) {
                return $theme_color;
            }
        }
        return get_option('pbr_primary_color', '#29853a');
    }

    /**
     * Generate content using Blackbox API
     */
    public function generate($prompt, $user_message, $max_tokens = 16000) {
        if (empty($this->api_key)) {
            throw new Exception('کلید API بلک‌باکس تنظیم نشده است.');
        }
        
        $primary_color = $this->get_primary_color();
        $full_message = $prompt . "\n\n---\n\n" . $user_message;
        $full_message .= "\n\nرنگ اصلی سایت: " . $primary_color;
        
        $response = wp_remote_post($this->base_url, [
            'timeout' => $this->timeout,
            'sslverify' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $full_message
                    ]
                ],
                'model' => $this->model,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7,
                'top_p' => 0.9,
                'stream' => false
            ])
        ]);
        
        return $this->handle_response($response);
    }

    /**
     * Generate HTML content for product
     */
    public function generate_product_content($research_data, $product_name, $keywords) {
        $prompt = PBR_Prompts::get_prompt('content');
        
        $user_message = "## داده‌های تحقیق محصول:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## نام محصول: {$product_name}\n\n";
        $user_message .= "## کلیدواژه‌های هدف:\n{$keywords}\n\n";
        $user_message .= "لطفاً بر اساس پرامپت و داده‌های بالا، خروجی کامل شامل:\n";
        $user_message .= "۱. جدول اطلاعات متا و سئو\n";
        $user_message .= "۲. توضیح کوتاه محصول\n";
        $user_message .= "۳. کد HTML کامل برای ویرایشگر کلاسیک وردپرس\n";
        $user_message .= "۴. خروجی JSON برای فیلدهای سفارشی\n\n";
        $user_message .= "را تولید کن.";
        
        return $this->generate($prompt, $user_message);
    }

    /**
     * Generate HTML content for blog post
     */
    public function generate_post_content($research_data, $topic, $keywords) {
        $prompt = PBR_Prompts::get_prompt('post');
        
        $user_message = "## داده‌های تحقیق:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## موضوع پست: {$topic}\n\n";
        $user_message .= "## کلیدواژه‌های هدف:\n{$keywords}\n\n";
        $user_message .= "لطفاً یک پست بلاگ کامل با کد HTML زیبا و رنگی تولید کن.";
        
        return $this->generate($prompt, $user_message);
    }

    /**
     * Update existing content
     */
    public function update_content($current_content, $research_data, $update_instructions) {
        $prompt = PBR_Prompts::get_prompt('update');
        
        $user_message = "## محتوای HTML فعلی:\n\n";
        $user_message .= $current_content . "\n\n";
        $user_message .= "## داده‌های جدید از تحقیق:\n\n";
        $user_message .= $research_data . "\n\n";
        $user_message .= "## دستورالعمل‌های به‌روزرسانی:\n";
        $user_message .= $update_instructions . "\n\n";
        $user_message .= "لطفاً HTML را به‌روزرسانی کن و ساختار و رنگ‌بندی را حفظ کن.";
        
        return $this->generate($prompt, $user_message);
        }

    /**
     * Handle API response
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            throw new Exception('خطای اتصال: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_msg = isset($body['error']['message']) 
                ? $body['error']['message'] 
                : "خطای HTTP {$status_code}";
            throw new Exception("خطای API: {$error_msg}");
        }
        
        $content = '';
        
        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];
        } elseif (isset($body['response'])) {
            $content = $body['response'];
        } elseif (isset($body['text'])) {
            $content = $body['text'];
        } elseif (isset($body['content'])) {
            $content = is_array($body['content']) ? $body['content'][0]['text'] : $body['content'];
        }
        
        if (empty($content)) {
            throw new Exception('پاسخ خالی از API دریافت شد.');
        }
        
        return $content;
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $response = wp_remote_post($this->base_url, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'messages' => [
                        ['role' => 'user', 'content' => 'بگو: اتصال برقرار شد']
                    ],
                    'model' => $this->model,
                    'max_tokens' => 50
                ])
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => $response->get_error_message()
                ];
            }
            
            $code = wp_remote_retrieve_response_code($response);
            
            if ($code === 200) {
                return [
                    'success' => true,
                    'message' => '✅ اتصال به Blackbox API برقرار است'
                ];
            }
            
            return [
                'success' => false,
                'message' => "خطای HTTP {$code}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}