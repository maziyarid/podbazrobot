<?php
/**
 * Tavily API Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Tavily_API {
    
    private $api_key;
    private $base_url = 'https://api.tavily.com/search';
    private $timeout = 60;

    public function __construct() {
        $this->api_key = get_option('pbr_tavily_api_key', '');
    }

    /**
     * Research a product
     */
        public function research_product($product_name, $keywords = '') {
        if (empty($this->api_key)) {
            throw new Exception('کلید API تاویلی تنظیم نشده است.');
        }
        
        $results = [];
        
        // Search 1: Basic specs
        $results['specs'] = $this->search(
            "{$product_name} specifications features review",
            ['voopoo.com', 'vaporesso.com', 'uwell.com', 'geekvape.com']
        );
        
        // Search 2: Technical details
        $results['technical'] = $this->search(
            "{$product_name} coil battery chipset wattage",
            []
        );
        
        // Search 3: Brand info
        $brand = $this->extract_brand($product_name);
        $results['brand'] = $this->search(
            "{$brand} vape brand history {$product_name}",
            []
        );
        
        // Search 4: Comparisons
        $results['comparison'] = $this->search(
            "{$product_name} vs comparison review",
            []
        );
        
        return $this->compile_research($product_name, $results, $keywords);
    }

    /**
     * Research for blog post
     */
    public function research_topic($topic, $keywords = '') {
        if (empty($this->api_key)) {
            throw new Exception('کلید API تاویلی تنظیم نشده است.');
        }
        
        $results = [];
        
        $results['main'] = $this->search($topic . ' guide tutorial', []);
        $results['related'] = $this->search($topic . ' tips best practices', []);
        $results['faq'] = $this->search($topic . ' FAQ questions', []);
        
        return $this->compile_topic_research($topic, $results, $keywords);
    }

    /**
     * Perform Tavily search
     */
    private function search($query, $include_domains = []) {
        $body = [
            'query' => $query,
            'search_depth' => 'advanced',
            'include_answer' => true,
            'include_raw_content' => false,
            'max_results' => 8,
        ];
        
        if (!empty($include_domains)) {
            $body['include_domains'] = $include_domains;
        }
        
        $response = wp_remote_post($this->base_url, [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => json_encode($body)
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message(), 'answer' => '', 'sources' => []];
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'answer' => $result['answer'] ?? '',
            'sources' => array_map(function($r) {
                return [
                    'title' => $r['title'] ?? '',
                    'url' => $r['url'] ?? '',
                    'content' => $r['content'] ?? ''
                ];
            }, $result['results'] ?? [])
        ];
    }

    /**
     * Extract brand name
     */
    private function extract_brand($product_name) {
        $brands = [
            'VOOPOO', 'Vaporesso', 'UWELL', 'GeekVape', 'SMOK', 
            'Aspire', 'Innokin', 'Lost Vape', 'Eleaf', 'OXVA'
        ];
        
        foreach ($brands as $brand) {
            if (stripos($product_name, $brand) !== false) {
                return $brand;
            }
        }
        
        return explode(' ', $product_name)[0];
    }

    /**
     * Compile product research
     */
    private function compile_research($product_name, $results, $keywords) {
        $output = "# گزارش تحقیق محصول: {$product_name}\n\n";
        $output .= "---\n\n";
        
        $output .= "## عنوان:\n{$product_name}\n\n";
        
        if (!empty($keywords)) {
            $output .= "## کلیدواژه‌ها:\n{$keywords}\n\n";
        }
        
        $output .= "## مشخصات فنی:\n";
        if (!empty($results['specs']['answer'])) {
            $output .= $results['specs']['answer'] . "\n\n";
        }
        $output .= "### منابع:\n";
        foreach ($results['specs']['sources'] as $idx => $source) {
            $num = $idx + 1;
            $output .= "**[منبع {$num}]** {$source['title']}\n";
            $output .= $source['content'] . "\n";
            $output .= "لینک: {$source['url']}\n\n";
        }
        
        $output .= "## جزئیات فنی:\n";
        if (!empty($results['technical']['answer'])) {
            $output .= $results['technical']['answer'] . "\n\n";
        }
        foreach ($results['technical']['sources'] as $source) {
            $output .= "- {$source['content']}\n";
        }
        $output .= "\n";
        
        $output .= "## داستان برند:\n";
        if (!empty($results['brand']['answer'])) {
            $output .= $results['brand']['answer'] . "\n\n";
        }
        
        $output .= "## مقایسه:\n";
        if (!empty($results['comparison']['answer'])) {
            $output .= $results['comparison']['answer'] . "\n\n";
        }
        
        $output .= "---\n";
        $output .= "تاریخ تحقیق: " . current_time('Y-m-d H:i:s') . "\n";
        
        return $output;
    }

    /**
     * Compile topic research
     */
    private function compile_topic_research($topic, $results, $keywords) {
        $output = "# گزارش تحقیق: {$topic}\n\n";
        
        if (!empty($keywords)) {
            $output .= "## کلیدواژه‌ها:\n{$keywords}\n\n";
        }
        
        $output .= "## خلاصه:\n";
        $output .= ($results['main']['answer'] ?? '') . "\n\n";
        
        $output .= "## اطلاعات تکمیلی:\n";
        $output .= ($results['related']['answer'] ?? '') . "\n\n";
        
        $output .= "## سوالات متداول:\n";
        $output .= ($results['faq']['answer'] ?? '') . "\n\n";
        
        $output .= "## منابع:\n";
        foreach ($results['main']['sources'] as $source) {
            $output .= "- [{$source['title']}]({$source['url']})\n";
        }
        
        return $output;
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $response = wp_remote_post($this->base_url, [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ],
                'body' => json_encode([
                    'query' => 'vape device test',
                    'max_results' => 1
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
                    'message' => '✅ اتصال به Tavily API برقرار است'
                ];
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return [
                'success' => false,
                'message' => $body['error'] ?? "خطای HTTP {$code}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}