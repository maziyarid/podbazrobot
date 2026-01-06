<?php
/**
 * HTML Content Parser for Podbaz
 */

if (!defined('ABSPATH')) exit;

class PBR_HTML_Parser {
    
    // Constants for content limits
    const SHORT_DESC_MAX_LENGTH = 300;
    
    /**
     * Clean extracted text from markdown artifacts
     */
    private function clean_extracted_text($text) {
        // Remove code block markers
        $text = preg_replace('/^```\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        
        // Remove separator lines
        $text = preg_replace('/^-+\s*/', '', $text);
        $text = preg_replace('/\s*-+$/', '', $text);
        
        // Remove markdown bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        
        $text = trim($text);
        
        // Return empty if just separators
        if ($text === '---' || $text === '```' || empty($text)) {
            return '';
        }
        
        return $text;
    }
    
    /**
     * Truncate description to maximum length
     */
    private function truncate_description($text, $max_length = null) {
        if ($max_length === null) {
            $max_length = self::SHORT_DESC_MAX_LENGTH;
        }
        
        if (mb_strlen($text, 'UTF-8') > $max_length) {
            return mb_substr($text, 0, $max_length - 3, 'UTF-8') . '...';
        }
        
        return $text;
    }
    
    /**
     * Parse generated content
     */
    public function parse($raw_content) {
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
            'json_data' => null
        ];
        
        // Extract JSON
        $parsed['json_data'] = $this->extract_json($raw_content);
        
        // Remove JSON from content BEFORE further processing
        $raw_content = $this->remove_json_block($raw_content);
        
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
        // Meta title
        if (empty($parsed['meta_title'])) {
            if (preg_match('/متا تایتل\s*\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['meta_title'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // Meta description
        if (empty($parsed['meta_description'])) {
            if (preg_match('/متا دسکریپشن\s*\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['meta_description'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // H1 title - Multiple extraction patterns
        if (empty($parsed['h1_title'])) {
            // Pattern 1: From table
            if (preg_match('/عنوان \(H1\)\s*\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['h1_title'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // Pattern 2: With markdown bold formatting
        if (empty($parsed['h1_title'])) {
            if (preg_match('/\*\*بخش\s*[۱1].*?\*\*\s*```\s*\n?(.+?)\n?```/us', $content, $match)) {
                $parsed['h1_title'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // Pattern 3: Standard markdown heading
        if (empty($parsed['h1_title'])) {
            if (preg_match('/##?\s*بخش\s*[۱1][:\s].*?(?:عنوان|H1).*?\n+([^\n]+?)(?:\n|$)/u', $content, $match)) {
                $parsed['h1_title'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // Pattern 4: After label
        if (empty($parsed['h1_title'])) {
            if (preg_match('/عنوان محصول\s*\(H1\)\s*\n+([^\n]+)/u', $content, $match)) {
                $parsed['h1_title'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // Pattern 5: Simple markdown heading at start
        if (empty($parsed['h1_title'])) {
            if (preg_match('/^#\s+([^\n]+)$/m', $content, $match)) {
                $parsed['h1_title'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        // Slug
        if (empty($parsed['slug'])) {
            if (preg_match('/پیوند یکتا\s*\|\s*([a-z0-9\-]+)/ui', $content, $match)) {
                $parsed['slug'] = strtolower($this->clean_extracted_text(trim($match[1])));
            }
        }
        
        // Alt texts
        if (empty($parsed['alt_texts']['main'])) {
            if (preg_match('/متن جایگزین عکس.*?اصلی.*?\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['alt_texts']['main'] = $this->clean_extracted_text(trim($match[1]));
            } elseif (preg_match('/متن جایگزین عکس\s*\|\s*([^\|\n]+)/u', $content, $match)) {
                $parsed['alt_texts']['main'] = $this->clean_extracted_text(trim($match[1]));
            }
        }
        
        if (preg_match('/متن جایگزین عکس رنگ.*?\|\s*([^\|\n]+)/u', $content, $match)) {
            $parsed['alt_texts']['colors'] = $this->clean_extracted_text(trim($match[1]));
        }
        
        if (preg_match('/متن جایگزین عکس جعبه\s*\|\s*([^\|\n]+)/u', $content, $match)) {
            $parsed['alt_texts']['box'] = $this->clean_extracted_text(trim($match[1]));
        }
        
        if (preg_match('/متن جایگزین عکس پاد\s*\|\s*([^\|\n]+)/u', $content, $match)) {
            $parsed['alt_texts']['pod'] = $this->clean_extracted_text(trim($match[1]));
        }
    }
    
    /**
     * Extract short description
     */
    private function extract_short_description($content, &$parsed) {
        if (!empty($parsed['short_description'])) {
            // Limit to max length if already set from JSON
            $parsed['short_description'] = $this->truncate_description($parsed['short_description']);
            return;
        }
        
        // Look for short description section (supports multiple prompt formats)
        if (preg_match('/###\s*۲\s*[\.|\)]\s*توضیح کوتاه محصول\s*\n+(.+?)(?=\n{2,}###|\n{2,}##|\n{2,}```|\n{2,}<div|$)/us', $content, $match)) {
            $parsed['short_description'] = $this->clean_extracted_text(trim($match[1]));
        } elseif (preg_match('/##\s*توضیح کوتاه محصول\s*\n+(.+?)(?=\n{2,}##|\n{2,}```|\n{2,}<div|$)/us', $content, $match)) {
            $parsed['short_description'] = $this->clean_extracted_text(trim($match[1]));
        } elseif (preg_match('/توضیح کوتاه محصول\s*\n+(.+?)(?=\n{2,}###|\n{2,}##|\n{2,}```|\n{2,}<div|$)/us', $content, $match)) {
            $parsed['short_description'] = $this->clean_extracted_text(trim($match[1]));
        }
        
        // Clean up the short description
        if (!empty($parsed['short_description'])) {
            // Remove any markdown formatting
            $parsed['short_description'] = preg_replace('/\*\*([^*]+)\*\*/', '$1', $parsed['short_description']);
            $parsed['short_description'] = preg_replace('/\*([^*]+)\*/', '$1', $parsed['short_description']);
            // Remove extra whitespace
            $parsed['short_description'] = preg_replace('/\s+/', ' ', $parsed['short_description']);
            $parsed['short_description'] = trim($parsed['short_description']);
            
            // Limit to max length using helper method
            $parsed['short_description'] = $this->truncate_description($parsed['short_description']);
        }
    }
    
    /**
     * Extract HTML content block
     */
    private function extract_html_content($content, &$parsed) {
        // Method 1: Find the main HTML div block with Tahoma font
        if (preg_match('/<div style="font-family:\s*Tahoma.*?<\/div>\s*$/s', $content, $match)) {
            $parsed['html_content'] = $this->clean_html_content(trim($match[0]));
        }
        
        // Method 2: Find HTML within code block
        if (empty($parsed['html_content'])) {
            if (preg_match('/```html\s*([\s\S]*?)\s*```/m', $content, $match)) {
                $parsed['html_content'] = $this->clean_html_content(trim($match[1]));
            }
        }
        
        // Method 3: Find any div with RTL direction
        if (empty($parsed['html_content'])) {
            if (preg_match('/<div[^>]*direction:\s*rtl[^>]*>[\s\S]*<\/div>\s*$/s', $content, $match)) {
                $parsed['html_content'] = $this->clean_html_content(trim($match[0]));
            }
        }
        
        // Method 4: Extract from ### ۳. کد HTML section
        if (empty($parsed['html_content'])) {
            if (preg_match('/### ۳\. کد HTML.*?\n\n(<div[\s\S]*<\/div>)/us', $content, $match)) {
                $parsed['html_content'] = $this->clean_html_content(trim($match[1]));
            }
        }
        
        // Apply JSON removal to extracted HTML
        if (!empty($parsed['html_content'])) {
            $parsed['html_content'] = $this->remove_json_block($parsed['html_content']);
        }
        
        // Generate fallback HTML if extraction failed
        if (empty($parsed['html_content'])) {
            $parsed['html_content'] = $this->generate_fallback_html($parsed, $content);
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
     * Clean HTML content from artifacts
     */
    private function clean_html_content($html_content) {
        // Remove code block markers if any leaked through
        $html_content = preg_replace('/^```(?:html)?\s*\n/m', '', $html_content);
        $html_content = preg_replace('/\n```\s*$/m', '', $html_content);
        
        // Clean up malformed HTML - fix broken style attributes
        $html_content = preg_replace('/;direction:\s*rtl;[^"]*?">/u', '; direction: rtl; text-align: right;">', $html_content);
        $html_content = preg_replace('/(?<!")style="[^"]*$/m', '', $html_content);
        
        return trim($html_content);
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
     * Remove JSON blocks from content
     */
    private function remove_json_block($content) {
        // Remove fenced JSON code blocks (```json ... ```)
        $content = preg_replace('/```json\s*[\s\S]*?\s*```/m', '', $content);
        
        // Remove JSON after HTML closing tag (most specific pattern first)
        $content = preg_replace('/<\/div>\s*\n*\s*\{[\s\S]*\}\s*$/u', '</div>', $content);
        
        // Remove inline JSON objects at the end of content
        // This pattern matches a complete JSON object at the end, using balanced braces
        $content = $this->remove_trailing_json_object($content);
        
        return trim($content);
    }
    
    /**
     * Remove trailing JSON object from content
     */
    private function remove_trailing_json_object($content) {
        // Limit search scope to last 5000 characters to improve performance
        $search_len = min(5000, strlen($content));
        $search_content = substr($content, -$search_len);
        
        // Find the last opening brace followed by JSON-like content
        // Look for patterns that indicate a JSON object (key-value pairs with quotes)
        // Using a more specific pattern with limited scope
        if (preg_match('/\s*\{\s*"[^"]+"\s*:[^}]{0,4000}\}?\s*$/u', $search_content, $match, PREG_OFFSET_CAPTURE)) {
            $json_start_in_search = $match[0][1];
            $json_start = strlen($content) - $search_len + $json_start_in_search;
            $possible_json = substr($content, $json_start);
            
            // Try to decode it to verify it's valid JSON
            // Only remove if it decodes successfully (ensuring proper structure)
            if (json_decode($possible_json) !== null && json_last_error() === JSON_ERROR_NONE) {
                // It's valid JSON, remove it
                $content = substr($content, 0, $json_start);
            }
        }
        
        return $content;
    }
    
    /**
     * Generate fallback HTML from available data
     */
    private function generate_fallback_html($parsed, $raw_content = '') {
        $primary_color = get_option('pbr_primary_color', '#29853a');
        
        $html = '<div style="font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; padding: 20px; line-height: 1.8;">';
        
        $has_content = false;
        
        // Add H1 title
        if (!empty($parsed['h1_title'])) {
            $html .= '<h1 style="color: ' . esc_attr($primary_color) . '; text-align: center; margin-bottom: 20px;">' . esc_html($parsed['h1_title']) . '</h1>';
            $has_content = true;
        }
        
        // Add short description
        if (!empty($parsed['short_description'])) {
            $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 20px 0; border-right: 4px solid ' . esc_attr($primary_color) . ';">';
            $html .= '<p style="font-size: 16px; margin: 0;">' . nl2br(esc_html($parsed['short_description'])) . '</p>';
            $html .= '</div>';
            $has_content = true;
        }
        
        // Add custom fields if available
        if (!empty($parsed['custom_fields']) && is_array($parsed['custom_fields'])) {
            $html .= '<h2 style="color: ' . esc_attr($primary_color) . '; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 10px; margin-top: 30px;">مشخصات فنی</h2>';
            $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
            $html .= '<thead><tr style="background-color: ' . esc_attr($primary_color) . '; color: white;">';
            $html .= '<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">مشخصه</th>';
            $html .= '<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">مقدار</th>';
            $html .= '</tr></thead><tbody>';
            
            foreach ($parsed['custom_fields'] as $key => $value) {
                if (!empty($value) && is_string($value)) {
                    $html .= '<tr>';
                    $html .= '<td style="padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">' . esc_html($key) . '</td>';
                    $html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($value) . '</td>';
                    $html .= '</tr>';
                    $has_content = true;
                }
            }
            
            $html .= '</tbody></table>';
        }
        
        // If no structured content, try to extract plain text from raw content
        if (!$has_content && !empty($raw_content)) {
            // Remove HTML/markdown syntax
            $clean_content = strip_tags($raw_content);
            $clean_content = preg_replace('/```[a-z]*\s*[\s\S]*?```/m', '', $clean_content);
            $clean_content = preg_replace('/\{[\s\S]*?\}/', '', $clean_content);
            $clean_content = preg_replace('/\|[\s\S]*?\|/', '', $clean_content);
            $clean_content = trim($clean_content);
            
            if (!empty($clean_content) && strlen($clean_content) > 50) {
                // Split into paragraphs
                $paragraphs = array_filter(explode("\n", $clean_content), function($p) {
                    return strlen(trim($p)) > 20;
                });
                
                if (!empty($paragraphs)) {
                    $html .= '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">';
                    foreach (array_slice($paragraphs, 0, 5) as $para) {
                        $html .= '<p style="margin-bottom: 15px;">' . esc_html(trim($para)) . '</p>';
                    }
                    $html .= '</div>';
                    $has_content = true;
                }
            }
        }
        
        // Add a message if still no content
        if (!$has_content) {
            $html .= '<div style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffc107;">';
            $html .= '<p style="margin: 0; color: #856404;"><strong>⚠️ توجه:</strong> محتوای کامل در حال آماده‌سازی است. لطفاً بعداً بررسی کنید.</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
