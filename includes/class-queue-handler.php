<?php
/**
 * Queue Handler for Podbaz Robot
 * Manages queue-based content generation
 */

if (!defined('ABSPATH')) exit;

class PBR_Queue_Handler {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pbr_queue';
    }
    
    /**
     * Add item to queue
     */
    public function add_to_queue($item_name, $item_type = 'product', $keywords = '', $status = 'pending') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'item_name' => sanitize_text_field($item_name),
                'item_type' => sanitize_text_field($item_type),
                'keywords' => sanitize_textarea_field($keywords),
                'status' => $status,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            throw new Exception('خطا در افزودن به صف: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get queue items
     */
    public function get_queue_items($status = null, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_name}";
        
        if ($status) {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $sql .= " ORDER BY created_at ASC LIMIT %d OFFSET %d";
        $sql = $wpdb->prepare($sql, $limit, $offset);
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get queue item by ID
     */
    public function get_queue_item($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );
        
        return $wpdb->get_row($sql, ARRAY_A);
    }
    
    /**
     * Update queue item status
     */
    public function update_status($id, $status, $result = null, $error_message = null) {
        global $wpdb;
        
        $data = [
            'status' => $status
        ];
        
        if ($status === 'completed' || $status === 'failed') {
            $data['processed_at'] = current_time('mysql');
        }
        
        if ($result) {
            $data['result'] = is_array($result) ? json_encode($result) : $result;
        }
        
        if ($error_message) {
            $data['error_message'] = $error_message;
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            array_fill(0, count($data), '%s'),
            ['%d']
        );
    }
    
    /**
     * Get next pending item
     */
    public function get_next_pending() {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                ORDER BY created_at ASC 
                LIMIT 1";
        
        return $wpdb->get_row($sql, ARRAY_A);
    }
    
    /**
     * Process queue item
     */
    public function process_item($id) {
        $item = $this->get_queue_item($id);
        
        if (!$item) {
            throw new Exception('آیتم صف یافت نشد');
        }
        
        if ($item['status'] !== 'pending') {
            throw new Exception('آیتم در وضعیت قابل پردازش نیست');
        }
        
        // Mark as processing
        $this->update_status($id, 'processing');
        
        try {
            // Research phase
            $research_data = '';
            $tavily = new PBR_Tavily_API();
            
            if ($item['item_type'] === 'product') {
                $research_data = $tavily->research_product($item['item_name'], $item['keywords']);
            } else {
                $research_data = $tavily->research_topic($item['item_name'], $item['keywords']);
            }
            
            if (empty($research_data)) {
                throw new Exception('داده تحقیق در دسترس نیست');
            }
            
            // Generate content
            $blackbox = new PBR_Blackbox_API();
            
            if ($item['item_type'] === 'product') {
                $content = $blackbox->generate_product_content($research_data, $item['item_name'], $item['keywords']);
                
                // Create product
                $handler = new PBR_Product_Handler();
                $result = $handler->create_product($content, get_option('pbr_auto_publish', 'draft'));
                
                $this->update_status($id, 'completed', $result);
                
                return $result;
            } else {
                $content = $blackbox->generate_post_content($research_data, $item['item_name'], $item['keywords']);
                
                // Create post
                $handler = new PBR_Post_Handler();
                $result = $handler->create_post($content, get_option('pbr_auto_publish', 'draft'));
                
                $this->update_status($id, 'completed', $result);
                
                return $result;
            }
            
        } catch (Exception $e) {
            $this->update_status($id, 'failed', null, $e->getMessage());
            throw $e;
        }
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
            $stats[$row['status']] = (int) $row['count'];
            $stats['total'] += (int) $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Delete queue item
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
        
        return $wpdb->delete(
            $this->table_name,
            ['status' => 'completed'],
            ['%s']
        );
    }
    
    /**
     * Clear failed items
     */
    public function clear_failed() {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['status' => 'failed'],
            ['%s']
        );
    }
    
    /**
     * Retry failed item
     */
    public function retry_item($id) {
        return $this->update_status($id, 'pending', null, null);
    }
}
