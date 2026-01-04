<?php
/**
 * Queue Manager for Podbaz Robot
 */

if (!defined('ABSPATH')) exit;

class PBR_Queue_Manager {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pbr_queue';
    }
    
    /**
     * Add single item to queue
     */
    public function add_item($title, $keywords, $item_type = 'product', $priority = 0) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'title' => sanitize_text_field($title),
                'keywords' => sanitize_textarea_field($keywords),
                'item_type' => sanitize_text_field($item_type),
                'priority' => intval($priority),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            throw new Exception('خطا در افزودن به صف');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Add bulk items to queue
     */
    public function add_bulk_items($items) {
        $added = 0;
        $errors = [];
        
        foreach ($items as $item) {
            try {
                $this->add_item(
                    $item['title'],
                    $item['keywords'] ?? '',
                    $item['item_type'] ?? 'product',
                    $item['priority'] ?? 0
                );
                $added++;
            } catch (Exception $e) {
                $errors[] = $item['title'] . ': ' . $e->getMessage();
            }
        }
        
        return [
            'added' => $added,
            'errors' => $errors
        ];
    }
    
    /**
     * Get queue items with filtering
     */
    public function get_items($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        $sql = "SELECT * FROM {$this->table_name} {$where} 
                ORDER BY priority DESC, created_at ASC 
                LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit, $offset), ARRAY_A);
    }
    
    /**
     * Get single item by ID
     */
    public function get_item($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ), ARRAY_A);
    }
    
    /**
     * Update item status
     */
    public function update_status($id, $status, $result_id = null, $error_message = null) {
        global $wpdb;
        
        $data = [
            'status' => $status,
            'processed_at' => current_time('mysql')
        ];
        $format = ['%s', '%s'];
        
        if ($result_id !== null) {
            $data['result_id'] = $result_id;
            $format[] = '%d';
        }
        
        if ($error_message !== null) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            $format,
            ['%d']
        );
    }
    
    /**
     * Delete item from queue
     */
    public function delete_item($id) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
    }
    
    /**
     * Clear completed items
     */
    public function clear_completed() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$this->table_name} WHERE status = 'completed'");
    }
    
    /**
     * Get queue statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        ];
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $stats[$row['status']] = intval($row['count']);
            $stats['total'] += intval($row['count']);
        }
        
        return $stats;
    }
    
    /**
     * Process next item in queue
     */
    public function process_next_item() {
        global $wpdb;
        
        // Get next pending item
        $item = $wpdb->get_row(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             ORDER BY priority DESC, created_at ASC 
             LIMIT 1",
            ARRAY_A
        );
        
        if (!$item) {
            return null;
        }
        
        // Mark as processing
        $this->update_status($item['id'], 'processing');
        
        try {
            // Process based on type
            if ($item['item_type'] === 'product') {
                $result = $this->process_product($item);
            } else {
                $result = $this->process_post($item);
            }
            
            // Mark as completed
            $this->update_status($item['id'], 'completed', $result['id']);
            
            return [
                'success' => true,
                'item_id' => $item['id'],
                'result_id' => $result['id'],
                'title' => $item['title']
            ];
            
        } catch (Exception $e) {
            // Mark as failed
            $this->update_status($item['id'], 'failed', null, $e->getMessage());
            
            return [
                'success' => false,
                'item_id' => $item['id'],
                'title' => $item['title'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process batch of items
     */
    public function process_batch($count = 5) {
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $result = $this->process_next_item();
            if (!$result) {
                break;
            }
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Retry failed item
     */
    public function retry_item($id) {
        return $this->update_status($id, 'pending');
    }
    
    /**
     * Process product item
     */
    private function process_product($item) {
        // Research
        $tavily = new PBR_Tavily_API();
        $research_data = $tavily->research_product($item['title'], $item['keywords']);
        
        // Generate content
        $blackbox = new PBR_Blackbox_API();
        $content = $blackbox->generate_product_content($research_data, $item['title'], $item['keywords']);
        
        // Create product
        $handler = new PBR_Product_Handler();
        $result = $handler->create_product($content, get_option('pbr_auto_publish', 'draft'));
        
        return [
            'id' => $result['product_id'],
            'title' => $result['title']
        ];
    }
    
    /**
     * Process post item
     */
    private function process_post($item) {
        // Research
        $tavily = new PBR_Tavily_API();
        $research_data = $tavily->research_topic($item['title'], $item['keywords']);
        
        // Generate content
        $blackbox = new PBR_Blackbox_API();
        $content = $blackbox->generate_post_content($research_data, $item['title'], $item['keywords']);
        
        // Create post
        $handler = new PBR_Post_Handler();
        $result = $handler->create_post($content, get_option('pbr_auto_publish', 'draft'));
        
        return [
            'id' => $result['post_id'],
            'title' => $result['title']
        ];
    }
    
    /**
     * Parse bulk text input
     */
    public static function parse_bulk_text($text) {
        $items = [];
        $lines = explode("\n", trim($text));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Format: Title | keyword1, keyword2
            $parts = explode('|', $line);
            $title = trim($parts[0]);
            $keywords = isset($parts[1]) ? trim($parts[1]) : '';
            
            if (!empty($title)) {
                $items[] = [
                    'title' => $title,
                    'keywords' => $keywords,
                    'item_type' => 'product'
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Parse CSV file
     */
    public static function parse_csv($file_path) {
        $items = [];
        
        if (!file_exists($file_path)) {
            throw new Exception('فایل CSV یافت نشد');
        }
        
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            throw new Exception('خطا در خواندن فایل CSV');
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 1 && !empty($data[0])) {
                $items[] = [
                    'title' => $data[0],
                    'keywords' => $data[1] ?? '',
                    'item_type' => $data[2] ?? 'product'
                ];
            }
        }
        
        fclose($handle);
        return $items;
    }
}
