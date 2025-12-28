<?php
/**
 * Blackbox API Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Blackbox_API {
    
    private $api_key;
    private $model;
    private $base_url = 'https://api.blackbox.ai/chat/completions';
    private $timeout = 300;

    public function __construct() {
        $this->api_key = get_option('pbr_blackbox_api_key', '');
        $this->model = get_option('pbr_claude_model', 'gpt-4o');
    }

    /**
     * Generate content using Blackbox API
     */
    public function generate($prompt, $user_message, $max_tokens = 16000) {
        if (empty($this->api_key)) {
            throw new Exception('کلید API بلک‌باکس تنظیم نشده است.');
        }
        
        $full_message = $prompt . "\n\n---\n\n" . $user_message;
        
        $response = wp_remote_post($this->base_url, [
            'timeout' => $this->timeout,
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
                'temperature' => 0.7
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
     * Get available models for the API key
     */
    public function get_available_models() {
        if (empty($this->api_key)) {
            return [];
        }
        
        $response = wp_remote_get('https://api.blackbox.ai/v1/models', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return [];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['data']) || !is_array($body['data'])) {
            return [];
        }
        
        $models = [];
        foreach ($body['data'] as $model) {
            if (isset($model['id'])) {
                $models[] = $model['id'];
            }
        }
        
        return $models;
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            // First, try to get available models
            $available_models = $this->get_available_models();
            
            // Determine which model to use for testing
            $test_model = $this->model;
            if (!empty($available_models)) {
                // If current model is not in available models, use the first available one
                if (!in_array($this->model, $available_models)) {
                    $test_model = $available_models[0];
                }
            }
            
            $response = wp_remote_post($this->base_url, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say: Connection successful']
                    ],
                    'model' => $test_model,
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
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($code === 200) {
                $success_msg = '✅ اتصال به Blackbox API برقرار است';
                if (!empty($available_models)) {
                    $success_msg .= ' - ' . count($available_models) . ' مدل در دسترس';
                }
                if ($test_model !== $this->model) {
                    $success_msg .= ' (استفاده از مدل: ' . $test_model . ')';
                }
                return [
                    'success' => true,
                    'message' => $success_msg,
                    'available_models' => $available_models
                ];
            }
            
            // Return detailed error message from API
            $error_msg = "خطای HTTP {$code}";
            if (isset($body['error'])) {
                if (is_string($body['error'])) {
                    $error_msg .= ': ' . $body['error'];
                } elseif (isset($body['error']['message'])) {
                    $error_msg .= ': ' . $body['error']['message'];
                }
            }
            
            // Add available models info to error message
            if (!empty($available_models)) {
                $error_msg .= ' - مدل‌های موجود: ' . implode(', ', array_slice($available_models, 0, 5));
                if (count($available_models) > 5) {
                    $error_msg .= ' و ' . (count($available_models) - 5) . ' مدل دیگر';
                }
            }
            
            return [
                'success' => false,
                'message' => $error_msg
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}