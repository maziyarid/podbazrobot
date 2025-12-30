<?php
/**
 * AJAX Handlers for Podbaz Robot
 */

if (!defined('ABSPATH')) exit;

class PBR_Ajax_Handlers {
    
    public function __construct() {
        // Product generation
        add_action('wp_ajax_pbr_generate_product', [$this, 'generate_product']);
        
        // Post generation
        add_action('wp_ajax_pbr_generate_post', [$this, 'generate_post']);
        
        // Update content
        add_action('wp_ajax_pbr_update_content', [$this, 'update_content']);
        
        // Load existing content
        add_action('wp_ajax_pbr_load_content', [$this, 'load_content']);
        
        // Settings
        add_action('wp_ajax_pbr_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_pbr_save_prompts', [$this, 'save_prompts']);
        add_action('wp_ajax_pbr_reset_prompt', [$this, 'reset_prompt']);
        
        // API tests
        add_action('wp_ajax_pbr_test_api', [$this, 'test_api']);
        add_action('wp_ajax_pbr_check_api_status', [$this, 'check_api_status']);
        
        // Logs
        add_action('wp_ajax_pbr_clear_logs', [$this, 'clear_logs']);
        add_action('wp_ajax_pbr_export_logs', [$this, 'export_logs']);
    }
    
    /**
     * Generate new product
     */
    public function generate_product() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $product_name = sanitize_text_field($_POST['product_name'] ?? '');
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $research_method = sanitize_text_field($_POST['research_method'] ?? 'auto');
        $manual_research = sanitize_textarea_field($_POST['manual_research'] ?? '');
        $publish_status = sanitize_text_field($_POST['publish_status'] ?? 'draft');
        
        if (empty($product_name)) {
            wp_send_json_error(['message' => 'نام محصول الزامی است']);
        }
        
        try {
            $research_data = '';
            
            // Step 1: Research
            if ($research_method === 'auto') {
                $tavily = new PBR_Tavily_API();
                $research_data = $tavily->research_product($product_name, $keywords);
            } else {
                $research_data = $manual_research;
            }
            
            if (empty($research_data)) {
                wp_send_json_error(['message' => 'داده تحقیق در دسترس نیست']);
            }
            
            // Step 2: Generate HTML content
            $blackbox = new PBR_Blackbox_API();
            $content = $blackbox->generate_product_content($research_data, $product_name, $keywords);
            
            // Step 3: Create product
            $handler = new PBR_Product_Handler();
            $result = $handler->create_product($content, $publish_status);
            
            wp_send_json_success([
                'message' => '✅ محصول با موفقیت ایجاد شد!',
                'product_id' => $result['product_id'],
                'title' => $result['title'],
                'edit_link' => $result['edit_link'],
                'view_link' => $result['view_link'],
                'html_length' => $result['html_length'],
                'custom_fields_count' => $result['custom_fields_count']
            ]);
            
        } catch (Exception $e) {
            $this->log_error('create_product', $product_name, $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Generate new post
     */
    public function generate_post() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $research_method = sanitize_text_field($_POST['research_method'] ?? 'auto');
        $manual_research = sanitize_textarea_field($_POST['manual_research'] ?? '');
        $publish_status = sanitize_text_field($_POST['publish_status'] ?? 'draft');
        
        if (empty($topic)) {
            wp_send_json_error(['message' => 'موضوع پست الزامی است']);
        }
        
        try {
            $research_data = '';
            
            if ($research_method === 'auto') {
                $tavily = new PBR_Tavily_API();
                $research_data = $tavily->research_topic($topic, $keywords);
            } else {
                $research_data = $manual_research;
            }
            
            $blackbox = new PBR_Blackbox_API();
            $content = $blackbox->generate_post_content($research_data, $topic, $keywords);
            
            $handler = new PBR_Post_Handler();
            $result = $handler->create_post($content, $publish_status);
            
            wp_send_json_success([
                'message' => '✅ پست با موفقیت ایجاد شد!',
                'post_id' => $result['post_id'],
                'title' => $result['title'],
                'edit_link' => $result['edit_link'],
                'view_link' => $result['view_link']
            ]);
            
        } catch (Exception $e) {
            $this->log_error('create_post', $topic, $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Update existing content
     */
    public function update_content() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $update_type = sanitize_text_field($_POST['update_type'] ?? 'product');
        $item_id = intval($_POST['item_id'] ?? 0);
        $instructions = sanitize_textarea_field($_POST['instructions'] ?? '');
        $refresh_research = sanitize_text_field($_POST['refresh_research'] ?? 'no');
        
        if (!$item_id) {
            wp_send_json_error(['message' => 'شناسه مورد الزامی است']);
        }
        
        try {
            $current_content = '';
            $item_name = '';
            
            if ($update_type === 'product') {
                $handler = new PBR_Product_Handler();
                $data = $handler->get_product_content($item_id);
                $current_content = $data['raw_content'] ?: $data['description'];
                $item_name = $data['title'];
            } else {
                $handler = new PBR_Post_Handler();
                $data = $handler->get_post_content($item_id);
                $current_content = $data['raw_content'] ?: $data['content'];
                $item_name = $data['title'];
            }
            
            $research_data = '';
            if ($refresh_research === 'yes') {
                $tavily = new PBR_Tavily_API();
                $research_data = $tavily->research_product($item_name);
            }
            
            $blackbox = new PBR_Blackbox_API();
            $new_content = $blackbox->update_content($current_content, $research_data, $instructions);
            
            if ($update_type === 'product') {
                $result = $handler->update_product($item_id, $new_content);
            } else {
                $result = $handler->update_post($item_id, $new_content);
            }
            
            wp_send_json_success([
                'message' => '✅ محتوا با موفقیت به‌روزرسانی شد!',
                'item_id' => $result['product_id'] ?? $result['post_id'],
                'edit_link' => $result['edit_link']
            ]);
            
        } catch (Exception $e) {
            $this->log_error('update_' . $update_type, $item_name ?? 'Unknown', $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Load existing content
     */
    public function load_content() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $type = sanitize_text_field($_POST['type'] ?? 'product');
        $item_id = intval($_POST['item_id'] ?? 0);
        
        try {
            if ($type === 'product') {
                $handler = new PBR_Product_Handler();
                $data = $handler->get_product_content($item_id);
            } else {
                $handler = new PBR_Post_Handler();
                $data = $handler->get_post_content($item_id);
            }
            
            wp_send_json_success($data);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        if (isset($_POST['blackbox_api_key'])) {
            update_option('pbr_blackbox_api_key', sanitize_text_field($_POST['blackbox_api_key']));
        }
        if (isset($_POST['tavily_api_key'])) {
            update_option('pbr_tavily_api_key', sanitize_text_field($_POST['tavily_api_key']));
        }
        if (isset($_POST['claude_model'])) {
            update_option('pbr_claude_model', sanitize_text_field($_POST['claude_model']));
        }
        if (isset($_POST['auto_publish'])) {
            update_option('pbr_auto_publish', sanitize_text_field($_POST['auto_publish']));
        }
        
        $enable_logging = isset($_POST['enable_logging']) ? 'yes' : 'no';
        update_option('pbr_enable_logging', $enable_logging);
        
        // Save field mappings if provided
        if (isset($_POST['field_mapping']) && is_array($_POST['field_mapping'])) {
            $mappings = [];
            foreach ($_POST['field_mapping'] as $key => $field) {
                $mappings[sanitize_key($key)] = [
                    'meta_key' => sanitize_text_field($field['meta_key']),
                    'enabled' => isset($field['enabled']),
                    'label' => PBR_Custom_Fields::get_product_fields_mapping()[$key]['label'] ?? $key
                ];
            }
            update_option('pbr_field_mappings', $mappings);
        }
        
        wp_send_json_success(['message' => '✅ تنظیمات ذخیره شد']);
    }
    
    /**
     * Save prompts
     */
    public function save_prompts() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $prompts = ['research', 'content', 'post', 'update'];
        
        foreach ($prompts as $prompt_type) {
            $key = 'prompt_' . $prompt_type;
            if (isset($_POST[$key])) {
                update_option('pbr_' . $prompt_type . '_prompt', wp_kses_post($_POST[$key]));
            }
        }
        
        wp_send_json_success(['message' => '✅ پرامپت‌ها ذخیره شدند']);
    }
    
    /**
     * Reset prompt to default
     */
    public function reset_prompt() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $prompt_type = sanitize_text_field($_POST['prompt_type'] ?? '');
        
        $defaults = [
            'research' => PBR_Prompts::get_default_research_prompt(),
            'content' => PBR_Prompts::get_default_content_prompt(),
            'post' => PBR_Prompts::get_default_post_prompt(),
            'update' => PBR_Prompts::get_default_update_prompt(),
        ];
        
        if (isset($defaults[$prompt_type])) {
            update_option('pbr_' . $prompt_type . '_prompt', $defaults[$prompt_type]);
            wp_send_json_success([
                'message' => '✅ پرامپت به پیش‌فرض بازگردانی شد',
                'content' => $defaults[$prompt_type]
            ]);
        }
        
        wp_send_json_error(['message' => 'نوع پرامپت نامعتبر']);
    }
    
    /**
     * Test API connection
     */
    public function test_api() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        
        if ($api_type === 'blackbox') {
            $api = new PBR_Blackbox_API();
            $result = $api->test_connection();
        } elseif ($api_type === 'tavily') {
            $api = new PBR_Tavily_API();
            $result = $api->test_connection();
        } else {
            $result = ['success' => false, 'message' => 'نوع API نامعتبر'];
        }
        
        wp_send_json($result);
    }
    
    /**
     * Check all API status
     */
    public function check_api_status() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        $blackbox = new PBR_Blackbox_API();
        $tavily = new PBR_Tavily_API();
        
        wp_send_json_success([
            'blackbox' => $blackbox->test_connection(),
            'tavily' => $tavily->test_connection()
        ]);
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbr_logs';
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        wp_send_json_success(['message' => '✅ گزارش‌ها پاک شدند']);
    }
    
    /**
     * Export logs as CSV
     */
    public function export_logs() {
        check_ajax_referer('pbr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbr_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
        
        if (empty($logs)) {
            wp_send_json_error(['message' => 'گزارشی برای خروجی وجود ندارد']);
        }
        
        // Generate CSV content
        $csv_content = implode(',', array_keys($logs[0])) . "\n";
        foreach ($logs as $log) {
            $csv_content .= implode(',', array_map(function($val) {
                return '"' . str_replace('"', '""', $val) . '"';
            }, $log)) . "\n";
        }
        
        wp_send_json_success([
            'csv' => base64_encode($csv_content),
            'filename' => 'podbaz-logs-' . date('Y-m-d') . '.csv'
        ]);
    }
    
    /**
     * Log error
     */
    private function log_error($action, $name, $message) {
        if (get_option('pbr_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbr_logs';
        
        $wpdb->insert($table_name, [
            'action_type' => $action,
            'product_name' => $name,
            'status' => 'failed',
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
}
