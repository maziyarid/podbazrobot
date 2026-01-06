<?php
/**
 * Product Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Product_Handler {
    
    private $parser;

    public function __construct() {
        $this->parser = new PBR_HTML_Parser();
    }
    
    /**
     * Extract title from HTML content as fallback
     */
    private function extract_title_fallback($html_content) {
        // Try h1 tag
        if (preg_match('/<h1[^>]*>(.+?)<\/h1>/is', $html_content, $match)) {
            $title = strip_tags($match[1]);
            return html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        }
        return '';
    }
    
    /**
     * Generate slug from title
     */
    private function generate_slug($title) {
        if (empty($title)) return 'product-' . time();
        
        $slug = strtolower($title);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        return trim($slug, '-') ?: 'product-' . time();
    }

    /**
     * Create new WooCommerce product
     */
        public function create_product($raw_content, $status = 'draft') {
        if (!class_exists('WC_Product_Simple')) {
            throw new Exception('ووکامرس نصب نیست.');
        }
        
        // Parse content
        $parsed = $this->parser->parse($raw_content);
        
        // Check if short desc duplicates content
        if (!empty($parsed['short_description']) && !empty($parsed['html_content'])) {
            if (strlen($parsed['short_description']) > 500 || 
                strpos($parsed['html_content'], $parsed['short_description']) === 0) {
                $parsed['short_description'] = mb_substr(strip_tags($parsed['short_description']), 0, 300) . '...';
            }
        }
        
        // Clean HTML content before saving
        if (!empty($parsed['html_content'])) {
            // Remove section markers
            $parsed['html_content'] = preg_replace('/^##?\s*بخش\s*[۱۲۳۴]+[:\s].*?\n+/u', '', $parsed['html_content']);
            
            // Remove separators
            $parsed['html_content'] = preg_replace('/^\s*-{3,}\s*$/m', '', $parsed['html_content']);
        }
        
        // Log parsed data for debugging
        if (get_option('pbr_enable_logging') === 'yes') {
            update_option('pbr_last_parsed_data', [
                'has_html' => !empty($parsed['html_content']),
                'html_length' => strlen($parsed['html_content'] ?? ''),
                'has_h1' => !empty($parsed['h1_title']),
                'h1_title' => $parsed['h1_title'] ?? '',
                'has_short_desc' => !empty($parsed['short_description']),
                'custom_fields_count' => count($parsed['custom_fields'] ?? []),
                'raw_content_length' => strlen($raw_content),
                'timestamp' => current_time('mysql')
            ], false);
        }
        
        // Validate HTML content
        if (empty($parsed['html_content'])) {
            throw new Exception('محتوای HTML یافت نشد. لطفاً لاگ‌ها را بررسی کنید: get_option(\'pbr_last_parsed_data\')');
        }
        
        // Get title - be more flexible
        if (empty($parsed['h1_title'])) {
            // Try to extract from HTML using fallback
            $parsed['h1_title'] = $this->extract_title_fallback($parsed['html_content']);
        }
        
        // Still no title? Use a default with timestamp
        if (empty($parsed['h1_title'])) {
            $parsed['h1_title'] = 'محصول جدید - ' . current_time('Y-m-d H:i');
            $this->log_action('create_product_warning', $parsed['h1_title'], 'warning', 'عنوان محصول خودکار تنظیم شد');
        }
        
        // Create product
        $product = new WC_Product_Simple();
        
        $product->set_name($parsed['h1_title']);
        
        // Generate slug if empty
        if (empty($parsed['slug'])) {
            $parsed['slug'] = $this->generate_slug($parsed['h1_title']);
        }
        $product->set_slug($parsed['slug']);
        
        $product->set_status($status);
        $product->set_catalog_visibility('visible');
        $product->set_description($parsed['html_content']);
        $product->set_short_description($parsed['short_description'] ?? '');
        
        // Save product
        $product_id = $product->save();
        
        if (!$product_id) {
            throw new Exception('خطا در ایجاد محصول.');
        }
        
        // Set SEO meta
        $this->set_seo_meta($product_id, $parsed);
        
        // Set custom fields
        if (!empty($parsed['custom_fields'])) {
            PBR_Custom_Fields::save_product_fields($product_id, $parsed['custom_fields']);
        }
        
        // Store additional data
        $this->store_additional_data($product_id, $parsed, $raw_content);
        
        // Log action
        $this->log_action('create_product', $parsed['h1_title'], 'success', "محصول #{$product_id} ایجاد شد");
        
        return [
            'product_id' => $product_id,
            'title' => $parsed['h1_title'],
            'slug' => $parsed['slug'],
            'edit_link' => admin_url("post.php?post={$product_id}&action=edit"),
            'view_link' => get_permalink($product_id),
            'html_length' => strlen($parsed['html_content']),
            'custom_fields_count' => count($parsed['custom_fields'] ?? [])
        ];
    }

    /**
     * Update existing product
     */
    public function update_product($product_id, $raw_content) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            throw new Exception('محصول یافت نشد.');
        }
        
        // Parse new content
        $parsed = $this->parser->parse($raw_content);
        
        // Update product
        if (!empty($parsed['h1_title'])) {
            $product->set_name($parsed['h1_title']);
        }
        
        if (!empty($parsed['html_content'])) {
            $product->set_description($parsed['html_content']);
        }
        
        if (!empty($parsed['short_description'])) {
            $product->set_short_description($parsed['short_description']);
        }
        
        if (!empty($parsed['slug'])) {
            $product->set_slug($parsed['slug']);
        }
        
        $product->save();
        
        // Update SEO meta
        $this->set_seo_meta($product_id, $parsed);
        
        // Update custom fields
        PBR_Custom_Fields::save_product_fields($product_id, $parsed['custom_fields']);
        
        // Store update history
        update_post_meta($product_id, '_pbr_last_updated', current_time('mysql'));
        update_post_meta($product_id, '_pbr_raw_content', $raw_content);
        
        // Log action
        $this->log_action('update_product', $product->get_name(), 'success', "محصول #{$product_id} به‌روزرسانی شد");
        
        return [
            'product_id' => $product_id,
            'updated' => true,
            'title' => $product->get_name(),
            'edit_link' => admin_url("post.php?post={$product_id}&action=edit")
        ];
    }

    /**
     * Set SEO metadata
     */
    private function set_seo_meta($product_id, $parsed) {
        // Yoast SEO
        if (!empty($parsed['meta_title'])) {
            update_post_meta($product_id, '_yoast_wpseo_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($product_id, '_yoast_wpseo_metadesc', $parsed['meta_description']);
        }
        
        // Rank Math
        if (!empty($parsed['meta_title'])) {
            update_post_meta($product_id, 'rank_math_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($product_id, 'rank_math_description', $parsed['meta_description']);
        }
        
        // All in One SEO
        if (!empty($parsed['meta_title'])) {
            update_post_meta($product_id, '_aioseo_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($product_id, '_aioseo_description', $parsed['meta_description']);
        }
    }

    /**
     * Store additional data
     */
    private function store_additional_data($product_id, $parsed, $raw_content) {
        update_post_meta($product_id, '_pbr_parsed_data', $parsed);
        update_post_meta($product_id, '_pbr_raw_content', $raw_content);
        update_post_meta($product_id, '_pbr_json_data', $parsed['json_data']);
        update_post_meta($product_id, '_pbr_alt_texts', $parsed['alt_texts']);
        update_post_meta($product_id, '_pbr_generated_date', current_time('mysql'));
    }

    /**
     * Get product content for update
     */
    public function get_product_content($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            throw new Exception('محصول یافت نشد.');
        }
        
        return [
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'custom_fields' => PBR_Custom_Fields::get_product_fields($product_id),
            'raw_content' => get_post_meta($product_id, '_pbr_raw_content', true),
            'alt_texts' => get_post_meta($product_id, '_pbr_alt_texts', true)
        ];
    }

    /**
     * Log action
     */
    private function log_action($action_type, $product_name, $status, $message) {
        if (get_option('pbr_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbr_logs';
        
        $wpdb->insert($table_name, [
            'action_type' => $action_type,
            'product_name' => $product_name,
            'status' => $status,
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
}
