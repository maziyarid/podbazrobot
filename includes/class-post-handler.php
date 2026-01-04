<?php
/**
 * Post Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Post_Handler {
    
    private $parser;

    public function __construct() {
        $this->parser = new PBR_HTML_Parser();
    }

    /**
     * Create new blog post
     */
        public function create_post($raw_content, $status = 'draft') {
        // Parse content
        $parsed = $this->parse_post_content($raw_content);
        
        // Validate title
        if (empty($parsed['title'])) {
            throw new Exception('عنوان پست یافت نشد.');
        }
        
        // No longer throw exception for empty HTML - use fallback
        if (empty($parsed['html_content'])) {
            error_log('PBR Warning: HTML content is empty for post: ' . $parsed['title']);
            // Generate basic fallback if we have excerpt
            if (!empty($parsed['excerpt'])) {
                $parsed['html_content'] = '<div style="font-family: Tahoma, Arial, sans-serif; direction: rtl;">';
                $parsed['html_content'] .= '<p>' . esc_html($parsed['excerpt']) . '</p>';
                $parsed['html_content'] .= '</div>';
            }
        }
        
        // Create post
        $post_data = [
            'post_title' => $parsed['title'],
            'post_content' => $parsed['html_content'],
            'post_excerpt' => $parsed['excerpt'],
            'post_status' => $status,
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        ];

        if (!empty($parsed['slug'])) {
            $post_data['post_name'] = $parsed['slug'];
        }
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception('خطا در ایجاد پست: ' . $post_id->get_error_message());
        }
        
        // Set SEO meta
        $this->set_seo_meta($post_id, $parsed);
        
        // Set taxonomies
        $this->set_taxonomies($post_id, $parsed);
        
        // Store data
        update_post_meta($post_id, '_pbr_post_data', $parsed);
        update_post_meta($post_id, '_pbr_raw_content', $raw_content);
        update_post_meta($post_id, '_pbr_generated_date', current_time('mysql'));
        
        // Log action
        $this->log_action('create_post', $parsed['title'], 'success', "پست #{$post_id} ایجاد شد");
        
        return [
            'post_id' => $post_id,
            'title' => $parsed['title'],
            'slug' => $parsed['slug'],
            'edit_link' => admin_url("post.php?post={$post_id}&action=edit"),
            'view_link' => get_permalink($post_id)
        ];
    }

    /**
     * Update existing post
     */
        public function update_post($post_id, $raw_content) {
        $post = get_post($post_id);
        
        if (!$post) {
            throw new Exception('پست یافت نشد.');
        }
        
        // Parse content
        $parsed = $this->parse_post_content($raw_content);
        
        // Update post
        $post_data = [
            'ID' => $post_id,
            'post_title' => !empty($parsed['title']) ? $parsed['title'] : $post->post_title,
            'post_content' => !empty($parsed['html_content']) ? $parsed['html_content'] : $post->post_content,
            'post_excerpt' => !empty($parsed['excerpt']) ? $parsed['excerpt'] : $post->post_excerpt,
        ];
        
        if (!empty($parsed['slug'])) {
            $post_data['post_name'] = $parsed['slug'];
        }
        
        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            throw new Exception('خطا در به‌روزرسانی پست: ' . $result->get_error_message());
        }
        
        // Update SEO meta
        $this->set_seo_meta($post_id, $parsed);
        
        // Store data
        update_post_meta($post_id, '_pbr_last_updated', current_time('mysql'));
        update_post_meta($post_id, '_pbr_raw_content', $raw_content);
        
        // Log action
        $this->log_action('update_post', $post_data['post_title'], 'success', "پست #{$post_id} به‌روزرسانی شد");
        
        return [
            'post_id' => $post_id,
            'updated' => true,
            'title' => $post_data['post_title'],
            'edit_link' => admin_url("post.php?post={$post_id}&action=edit")
        ];
    }

    /**
     * Parse post content
     */
    private function parse_post_content($raw_content) {
        $parsed = [
            'title' => '',
            'slug' => '',
            'meta_title' => '',
            'meta_description' => '',
            'html_content' => '',
            'excerpt' => '',
            'category' => '',
            'tags' => [],
        ];

        // 1) Try to extract JSON first
        if (preg_match('/```json\s*([\s\S]*?)\s*```/m', $raw_content, $match)) {
            $json = json_decode(trim($match[1]), true);

            if (is_array($json) && isset($json['post']) && is_array($json['post'])) {
                $post = $json['post'];
                $parsed['title'] = $post['title'] ?? '';
                $parsed['slug'] = $post['slug'] ?? '';
                $parsed['meta_title'] = $post['metaTitle'] ?? '';
                $parsed['meta_description'] = $post['metaDescription'] ?? '';
                $parsed['category'] = $post['category'] ?? '';
                $parsed['tags'] = isset($post['tags']) && is_array($post['tags']) ? $post['tags'] : [];
            }
        }

        // 2) Extract meta from markdown table rows
        if (empty($parsed['meta_title']) && preg_match('/\|\s*متا تایتل\s*\|\s*(.*?)\s*\|/u', $raw_content, $m)) {
            $parsed['meta_title'] = trim($m[1]);
        }
        if (empty($parsed['meta_description']) && preg_match('/\|\s*متا دسکریپشن\s*\|\s*(.*?)\s*\|/u', $raw_content, $m)) {
            $parsed['meta_description'] = trim($m[1]);
        }
        if (empty($parsed['title']) && preg_match('/\|\s*عنوان\s*\(H1\)\s*\|\s*(.*?)\s*\|/u', $raw_content, $m)) {
            $parsed['title'] = trim($m[1]);
        }
        if (empty($parsed['slug']) && preg_match('/\|\s*پیوند یکتا\s*\|\s*([a-z0-9\-]+)\s*\|/ui', $raw_content, $m)) {
            $parsed['slug'] = strtolower(trim($m[1]));
        }

        // 3) Backward-compatible (older text formats)
        if (empty($parsed['meta_title']) && preg_match('/متا تایتل\s*\|\s*([^\|\n]+)/u', $raw_content, $m)) {
            $parsed['meta_title'] = trim($m[1]);
        }
        if (empty($parsed['meta_description']) && preg_match('/متا دسکریپشن\s*\|\s*([^\|\n]+)/u', $raw_content, $m)) {
            $parsed['meta_description'] = trim($m[1]);
        }
        if (empty($parsed['title']) && preg_match('/عنوان\s*\(H1\)\s*\|\s*([^\|\n]+)/u', $raw_content, $m)) {
            $parsed['title'] = trim($m[1]);
        }
        if (empty($parsed['slug']) && preg_match('/پیوند یکتا\s*\|\s*([a-z0-9\-]+)/ui', $raw_content, $m)) {
            $parsed['slug'] = strtolower(trim($m[1]));
        }

        // 4) Extract HTML content (prefer fenced code)
        if (preg_match('/```html\s*([\s\S]*?)\s*```/m', $raw_content, $m)) {
            $parsed['html_content'] = trim($m[1]);
        } elseif (preg_match('/<div[^>]*style="[^"]*font-family:\s*Tahoma[^"]*"[^>]*>[\s\S]*<\/div>/is', $raw_content, $m)) {
            $parsed['html_content'] = trim($m[0]);
        } elseif (preg_match('/<div[^>]*style="[^"]*direction:\s*rtl[^"]*"[^>]*>[\s\S]*<\/div>/is', $raw_content, $m)) {
            $parsed['html_content'] = trim($m[0]);
        }

        // 5) Extract title from HTML if not found
        if (empty($parsed['title'])) {
            $h1_source = !empty($parsed['html_content']) ? $parsed['html_content'] : $raw_content;
            if (preg_match('/<h1[^>]*>([\s\S]*?)<\/h1>/i', $h1_source, $m)) {
                $parsed['title'] = trim(wp_strip_all_tags($m[1]));
            }
        }

        // 6) Extract excerpt (prefer introduction paragraph)
        $excerpt_source = !empty($parsed['html_content']) ? $parsed['html_content'] : $raw_content;
        if (preg_match('/<!--\s*مقدمه\s*-->[\s\S]*?<p[^>]*>([\s\S]*?)<\/p>/iu', $excerpt_source, $m)) {
            $parsed['excerpt'] = trim(wp_strip_all_tags($m[1]));
        } elseif (preg_match('/<p[^>]*>([\s\S]*?)<\/p>/i', $excerpt_source, $m)) {
            $parsed['excerpt'] = trim(wp_strip_all_tags($m[1]));
        }

        return $parsed;
    }

    /**
     * Set SEO metadata
     */
    private function set_seo_meta($post_id, $parsed) {
        // Yoast
        if (!empty($parsed['meta_title'])) {
            update_post_meta($post_id, '_yoast_wpseo_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $parsed['meta_description']);
        }
        
        // Rank Math
        if (!empty($parsed['meta_title'])) {
            update_post_meta($post_id, 'rank_math_title', $parsed['meta_title']);
        }
        if (!empty($parsed['meta_description'])) {
            update_post_meta($post_id, 'rank_math_description', $parsed['meta_description']);
        }
    }

    /**
     * Set taxonomies
     */
    private function set_taxonomies($post_id, $parsed) {
        // Set category
        if (!empty($parsed['category'])) {
            $cat = get_cat_ID($parsed['category']);
            if (!$cat) {
                $cat = wp_create_category($parsed['category']);
            }
            if ($cat) {
                wp_set_post_categories($post_id, [$cat]);
            }
        }
        
        // Set tags
        if (!empty($parsed['tags']) && is_array($parsed['tags'])) {
            wp_set_post_tags($post_id, $parsed['tags']);
        }
    }

    /**
     * Get post content for update
     */
    public function get_post_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            throw new Exception('پست یافت نشد.');
        }
        
        return [
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'raw_content' => get_post_meta($post_id, '_pbr_raw_content', true)
        ];
    }

    /**
     * Log action
     */
    private function log_action($action_type, $title, $status, $message) {
        if (get_option('pbr_enable_logging') !== 'yes') {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbr_logs';
        
        $wpdb->insert($table_name, [
            'action_type' => $action_type,
            'product_name' => $title,
            'status' => $status,
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
}