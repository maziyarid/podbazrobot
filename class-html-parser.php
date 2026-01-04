<?php
/**
 * HTML Content Parser for Podbaz
 */

if (!defined('ABSPATH')) exit;

class PBR_HTML_Parser {
    
    /**
     * Parse generated content
     */
    public function parse($raw_content) {
        // Preserve FSP shortcodes before processing
        $raw_content = $this->preserve_fsp_shortcodes($raw_content);
        
        $parsed = [
            // SEO Fields
            'h1_title' => '',
            'slug' => '',
            'meta_title' => '',
            'meta_description' => '',
            
            // Content
            'short_description' => '',
            'html_content' => '',
            
            // Alt Texts
            'alt_texts' => [
                'main' => '',
                'colors' => '',
                'box' => '',
                'pod' => ''
            ],
            
            // Custom Fields
            'custom_fields' => [],
            
            // JSON Data
            'json_data' => null,
            
            // FAQ
            'faq' => []
        ];
        
        // Extract JSON
        $parsed['json_data'] = $this->extract_json($raw_content);
        
        // Populate from JSON
        if ($parsed['json_data']) {
            $this->populate_from_json($parsed);
        }
        
        // Extract meta table
        $this->extract_meta_table($raw_content, $parsed);
        
        // Extract short description
        $this->extract_short_description($raw_content, $parsed);
        
        // Extract HTML content
        $this->extract_html_content($raw_content, $parsed);
        
        // Extract FAQ
        $this->extract_faq($raw_content, $parsed);
        
        // If HTML content is still empty, generate fallback
        if (empty($parsed['html_content'])) {
            $parsed['html_content'] = $this->generate_fallback_html($parsed);
        }
        
        return $parsed;
    }
    
    /**
     * Extract JSON from content
     */
    private function extract_json($content) {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/m', $content, $match)) {
            $json_str = trim($match[1]);
            $data = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        return null;
    }
    
    /**
     * Populate fields from JSON
     */
    private function populate_from_json(&$parsed) {
        $json = $parsed['json_data'];
        
        // SEO fields
        if (isset($json['seo'])) {
            $parsed['meta_title'] = $json['seo']['metaTitle'] ?? '';
            $parsed['meta_description'] = $json['seo']['metaDescription'] ?? '';
            $parsed['h1_title'] = $json['seo']['h1Title'] ?? '';
            $parsed['slug'] = $json['seo']['slug'] ?? '';
        }
        
        // Alt texts
        if (isset($json['altTexts'])) {
            $parsed['alt_texts'] = array_merge($parsed['alt_texts'], $json['altTexts']);
        }
        
        // Short description
        if (isset($json['shortDescription'])) {
            $parsed['short_description'] = $json['shortDescription'];
        }
        
        // Custom fields
        if (isset($json['customFields'])) {
            $parsed['custom_fields'] = $json['customFields'];
        }
    }
    
    /**
     * Extract meta table values
     */
    private function extract_meta_table($content, &$parsed) {
        // Meta title - multiple patterns
        if (empty($parsed['meta_title'])) {
            $patterns = [
                '/متا تایتل\s*\|\s*([^\|\n]+)/u',
                '/Meta Title\s*[:|]\s*([^\|\n]+)/i',
                '/عنوان متا\s*\|\s*([^\|\n]+)/u',
                '/<title[^>]*>([^<]+)<\/title>/i'
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $match)) {
                    $parsed['meta_title'] = trim($match[1]);
                    break;
                }
            }
        }
        
        // Meta description - multiple patterns
        if (empty($parsed['meta_description'])) {
            $patterns = [
                '/متا دسکریپشن\s*\|\s*([^\|\n]+)/u',
                '/Meta Description\s*[:|]\s*([^\|\n]+)/i',
                '/توضیحات متا\s*\|\s*([^\|\n]+)/u',
                '/<meta name="description" content="([^"]+)"/i'
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $match)) {
                    $parsed['meta_description'] = trim($match[1]);
                    break;
                }
            }
        }
        
        // H1 title - multiple patterns
        if (empty($parsed['h1_title'])) {
            $patterns = [
                '/عنوان \(H1\)\s*\|\s*([^\|\n]+)/u',
                '/H1 Title\s*[:|]\s*([^\|\n]+)/i',
                '/عنوان اصلی\s*\|\s*([^\|\n]+)/u'
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $match)) {
                    $parsed['h1_title'] = trim($match[1]);
                    break;
                }
            }
        }
        
        // Slug
        if (empty($parsed['slug'])) {
            if (preg_match('/پیوند یکتا\s*\|\s*([a-z0-9\-]+)/ui', $content, $match)) {
                $parsed['slug'] = strtolower(trim($match[1]));
            } elseif (preg_match('/Slug\s*[:|]\s*([a-z0-9\-]+)/i', $content, $match)) {
                $parsed['slug'] = strtolower(trim($match[1]));
            }
        }
        
        // Alt texts
        if (empty($parsed['alt_texts']['main'])) {
            if (preg_match('/متن جایگزین عکس.*?اصلی.*?\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['alt_texts']['main'] = trim($match[1]);
            } elseif (preg_match('/متن جایگزین عکس\s*\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['alt_texts']['main'] = trim($match[1]);
            } elseif (preg_match('/Alt Text\s*[:|]\s*([^\|\n]+)/i', $content, $match)) {
                $parsed['alt_texts']['main'] = trim($match[1]);
            }
        }
        
        if (preg_match('/متن جایگزین عکس رنگ.*?\|\s*([^\|\n]+)/u', $content, $match)) {
            $parsed['alt_texts']['colors'] = trim($match[1]);
        }
        
        if (preg_match('/متن جایگزین عکس جعبه\s*\|\s*([^\|\n]+)/u', $content, $match)) {
            $parsed['alt_texts']['box'] = trim($match[1]);
        }
        
        if (preg_match('/متن جایگزین عکس پاد\s*\|\s*([^\|\n]+)/u', $content, $match)) {
            $parsed['alt_texts']['pod'] = trim($match[1]);
        }
    }
    
    /**
     * Extract short description
     */
    private function extract_short_description($content, &$parsed) {
        if (!empty($parsed['short_description'])) {
            return;
        }
        
        // Look for short description section (supports multiple prompt formats)
        if (preg_match('/###\s*۲\s*[\.|\)]\s*توضیح کوتاه محصول\s*\n+(.+?)(?=\n{2,}###|\n{2,}##|\n{2,}```|\n{2,}<div|$)/us', $content, $match)) {
            $parsed['short_description'] = trim($match[1]);
        } elseif (preg_match('/##\s*توضیح کوتاه محصول\s*\n+(.+?)(?=\n{2,}##|\n{2,}```|\n{2,}<div|$)/us', $content, $match)) {
            $parsed['short_description'] = trim($match[1]);
        } elseif (preg_match('/توضیح کوتاه محصول\s*\n+(.+?)(?=\n{2,}###|\n{2,}##|\n{2,}```|\n{2,}<div|$)/us', $content, $match)) {
            $parsed['short_description'] = trim($match[1]);
        }
        
        // Clean up the short description
        if (!empty($parsed['short_description'])) {
            // Remove any markdown formatting
            $parsed['short_description'] = preg_replace('/\*\*([^*]+)\*\*/', '$1', $parsed['short_description']);
            $parsed['short_description'] = preg_replace('/\*([^*]+)\*/', '$1', $parsed['short_description']);
            // Remove extra whitespace
            $parsed['short_description'] = preg_replace('/\s+/', ' ', $parsed['short_description']);
            $parsed['short_description'] = trim($parsed['short_description']);
        }
    }
    
    /**
     * Extract HTML content block
     */
    private function extract_html_content($content, &$parsed) {
        // Method 1: Find the main HTML div block with Tahoma font
        if (preg_match('/<div style="font-family:\s*Tahoma.*?<\/div>\s*$/s', $content, $match)) {
            $parsed['html_content'] = trim($match[0]);
        }
        
        // Method 2: Find HTML within code block
        if (empty($parsed['html_content'])) {
            if (preg_match('/```html\s*([\s\S]*?)\s*```/m', $content, $match)) {
                $parsed['html_content'] = trim($match[1]);
            }
        }
        
        // Method 3: Find any div with RTL direction
        if (empty($parsed['html_content'])) {
            if (preg_match('/<div[^>]*direction:\s*rtl[^>]*>[\s\S]*<\/div>\s*$/s', $content, $match)) {
                $parsed['html_content'] = trim($match[0]);
            }
        }
        
        // Method 4: Extract from ### ۳. کد HTML section
        if (empty($parsed['html_content'])) {
            if (preg_match('/### ۳\. کد HTML.*?\n\n(<div[\s\S]*<\/div>)/us', $content, $match)) {
                $parsed['html_content'] = trim($match[1]);
            }
        }
        
        // Validate and clean HTML
        if (!empty($parsed['html_content'])) {
            $parsed['html_content'] = $this->validate_and_clean_html($parsed['html_content']);
        }
        
        // Extract H1 title from HTML if not found
        if (empty($parsed['h1_title']) && !empty($parsed['html_content'])) {
            if (preg_match('/<h1[^>]*>([^<]+)/i', $parsed['html_content'], $h1_match)) {
                $parsed['h1_title'] = strip_tags($h1_match[1]);
                $parsed['h1_title'] = trim($parsed['h1_title']);
            }
        }
    }
    
    /**
     * Validate and clean HTML content
     */
    private function validate_and_clean_html($html) {
        // Ensure proper RTL direction
        if (strpos($html, 'direction: rtl') === false && strpos($html, 'direction:rtl') === false) {
            $html = str_replace('<div style="', '<div style="direction: rtl; ', $html);
        }
        
        // Ensure font-family for Persian text
        if (strpos($html, 'font-family') === false) {
            $html = str_replace('<div style="', '<div style="font-family: Tahoma, Arial, sans-serif; ', $html);
        }
        
        // Remove empty tags
        $html = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>\s*<\/\1>/i', '', $html);
        
        // Fix unclosed tags (basic)
        $html = $this->fix_unclosed_tags($html);
        
        // Remove any PHP code that might have slipped through
        $html = preg_replace('/<\?php.*?\?>/s', '', $html);
        $html = preg_replace('/<\?.*?\?>/s', '', $html);
        
        // Remove script tags for security
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        
        return trim($html);
    }
    
    /**
     * Fix unclosed HTML tags
     */
    private function fix_unclosed_tags($html) {
        // List of self-closing tags
        $self_closing = ['br', 'hr', 'img', 'input', 'meta', 'link'];
        
        // Track opened tags
        $opened_tags = [];
        
        // Find all tags
        preg_match_all('/<\/?([a-z][a-z0-9]*)\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $index => $match) {
            $tag = strtolower($matches[1][$index][0]);
            $full_tag = $match[0];
            
            // Skip self-closing tags
            if (in_array($tag, $self_closing)) {
                continue;
            }
            
            // Check if opening or closing tag
            if (strpos($full_tag, '</') === 0) {
                // Closing tag
                if (!empty($opened_tags) && end($opened_tags) === $tag) {
                    array_pop($opened_tags);
                }
            } else {
                // Opening tag (not self-closed)
                if (substr($full_tag, -2) !== '/>') {
                    $opened_tags[] = $tag;
                }
            }
        }
        
        // Close any remaining open tags
        $opened_tags = array_reverse($opened_tags);
        foreach ($opened_tags as $tag) {
            $html .= "</{$tag}>";
        }
        
        return $html;
    }
    
    /**
     * Extract custom fields from research data
     */
    public function extract_custom_fields_from_research($research_data) {
        $fields = [];
        
        // Battery capacity
        if (preg_match('/(\d+)\s*mAh/i', $research_data, $match)) {
            $fields['batteryCapacity'] = $match[1] . ' mAh';
        }
        
        // Output power
        if (preg_match('/(\d+(?:\.\d+)?)\s*[Ww](?:att)?/i', $research_data, $match)) {
            $fields['outputPower'] = $match[1] . 'W';
        }
        
        // Tank/Pod capacity
        if (preg_match('/(\d+(?:\.\d+)?)\s*ml/i', $research_data, $match)) {
            $fields['tankCapacity'] = $match[1] . ' ml';
        }
        
        // Brand extraction
        $brands = ['VOOPOO', 'Vaporesso', 'UWELL', 'GeekVape', 'SMOK', 'Aspire', 'Innokin', 'Lost Vape', 'Eleaf', 'OXVA'];
        foreach ($brands as $brand) {
            if (stripos($research_data, $brand) !== false) {
                $fields['brand'] = $brand;
                break;
            }
        }
        
        // Charging type
        if (stripos($research_data, 'Type-C') !== false || stripos($research_data, 'USB-C') !== false) {
            $fields['chargingType'] = 'USB Type-C';
        } elseif (stripos($research_data, 'Micro USB') !== false) {
            $fields['chargingType'] = 'Micro USB';
        }
        
        // Country
        if (stripos($research_data, 'Shenzhen') !== false || stripos($research_data, 'China') !== false) {
            $fields['country'] = 'چین';
        }
        
        // Chipset
        $chipsets = ['GENE.AI', 'GENE.TT', 'GENE', 'AXON', 'AS', 'GT'];
        foreach ($chipsets as $chipset) {
            if (stripos($research_data, $chipset) !== false) {
                $fields['chipset'] = $chipset;
                break;
            }
        }
        
        // Coil resistance
        if (preg_match('/(\d+\.\d+)\s*(?:Ω|ohm)/i', $research_data, $match)) {
            $fields['coilResistance'] = $match[1] . 'Ω';
        }
        
        // Dimensions
        if (preg_match('/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*mm/i', $research_data, $match)) {
            $fields['dimensions'] = $match[1] . ' × ' . $match[2] . ' × ' . $match[3] . ' mm';
        }
        
        // Weight
        if (preg_match('/(\d+(?:\.\d+)?)\s*g(?:ram)?/i', $research_data, $match)) {
            $fields['weight'] = $match[1] . ' g';
        }
        
        return $fields;
    }
    
    /**
     * Convert Persian numbers to English
     */
    public function persian_to_english_numbers($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($persian, $english, $string);
    }
    
    /**
     * Convert English numbers to Persian
     */
    public function english_to_persian_numbers($string) {
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace($english, $persian, $string);
    }
    
    /**
     * Preserve FSP shortcodes in content
     */
    private function preserve_fsp_shortcodes($content) {
        // FSP Auto Poster shortcodes should be preserved
        // [fsp-auto-poster id="123"]
        return $content;
    }
    
    /**
     * Convert markdown lists to HTML
     */
    private function convert_markdown_lists($content) {
        // Convert unordered lists
        $content = preg_replace_callback('/^[\s]*[-*+]\s+(.+)$/m', function($matches) {
            return '<li>' . trim($matches[1]) . '</li>';
        }, $content);
        
        // Wrap consecutive list items
        $content = preg_replace('/<li>.*?<\/li>(\s*<li>.*?<\/li>)+/s', '<ul>$0</ul>', $content);
        
        // Convert ordered lists
        $content = preg_replace_callback('/^\s*\d+\.\s+(.+)$/m', function($matches) {
            return '<li>' . trim($matches[1]) . '</li>';
        }, $content);
        
        return $content;
    }
    
    /**
     * Wrap paragraphs in HTML
     */
    private function wrap_paragraphs($content) {
        // Split by double newlines
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $wrapped = '';
        
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (!empty($para) && !preg_match('/^<[a-z]/i', $para)) {
                $wrapped .= '<p>' . nl2br($para) . '</p>' . "\n";
            } else {
                $wrapped .= $para . "\n";
            }
        }
        
        return $wrapped;
    }
    
    /**
     * Extract FAQ from content
     */
    private function extract_faq($content, &$parsed) {
        $faq = [];
        
        // Look for FSP FAQ shortcode format
        if (preg_match_all('/\[fsp-faq question="([^"]+)" answer="([^"]+)"\]/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $faq[] = [
                    'question' => $match[1],
                    'answer' => $match[2]
                ];
            }
        }
        
        // Alternative: Look for FAQ sections in content
        if (empty($faq) && preg_match('/###?\s*سؤالات متداول.*?\n(.*?)(?=\n###|\n##|$)/s', $content, $match)) {
            $faq_text = $match[1];
            
            // Parse Q&A pairs
            if (preg_match_all('/(?:سؤال|پرسش|Q):\s*(.+?)\n(?:پاسخ|جواب|A):\s*(.+?)(?=\n(?:سؤال|پرسش|Q):|$)/s', $faq_text, $qa_matches, PREG_SET_ORDER)) {
                foreach ($qa_matches as $qa) {
                    $faq[] = [
                        'question' => trim($qa[1]),
                        'answer' => trim($qa[2])
                    ];
                }
            }
        }
        
        $parsed['faq'] = $faq;
    }
    
    /**
     * Generate fallback HTML when extraction fails
     */
    private function generate_fallback_html($parsed) {
        $primary_color = get_option('pbr_primary_color', '#29853a');
        
        $html = '<div style="font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; line-height: 1.8; color: #333;">';
        
        if (!empty($parsed['h1_title'])) {
            $html .= '<h1 style="color: ' . esc_attr($primary_color) . '; font-size: 28px; margin-bottom: 20px;">' 
                   . esc_html($parsed['h1_title']) . '</h1>';
        }
        
        if (!empty($parsed['short_description'])) {
            $html .= '<div style="background: #f8f9fa; padding: 20px; border-right: 4px solid ' . esc_attr($primary_color) . '; margin-bottom: 30px;">';
            $html .= '<p style="font-size: 16px; line-height: 1.8; margin: 0;">' . esc_html($parsed['short_description']) . '</p>';
            $html .= '</div>';
        }
        
        // Add a default section
        $html .= '<div style="margin-top: 30px;">';
        $html .= '<h2 style="color: ' . esc_attr($primary_color) . '; font-size: 24px; margin-bottom: 15px;">توضیحات</h2>';
        $html .= '<p style="font-size: 15px; line-height: 1.8;">محتوای تولید شده در حال پردازش است. لطفاً منتظر بمانید...</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}
