<?php
/**
 * Custom Fields Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Custom_Fields {
    
    /**
     * Get product fields mapping
     */
    public static function get_product_fields_mapping() {
    return [
        'brand' => [
            'meta_key' => '_product_brand',
            'label' => 'برند',
            'type' => 'text'
        ],
        'model' => [
            'meta_key' => '_product_model',
            'label' => 'مدل',
            'type' => 'text'
        ],
        'country' => [
            'meta_key' => '_product_country',
            'label' => 'کشور سازنده',
            'type' => 'text'
        ],
        'batteryCapacity' => [
            'meta_key' => '_battery_capacity',
            'label' => 'ظرفیت باتری',
            'type' => 'text'
        ],
        'outputPower' => [
            'meta_key' => '_output_power',
            'label' => 'توان خروجی',
            'type' => 'text'
        ],
        'tankCapacity' => [
            'meta_key' => '_tank_capacity',
            'label' => 'ظرفیت پاد/تانک',
            'type' => 'text'
        ],
        'coilResistance' => [
            'meta_key' => '_coil_resistance',
            'label' => 'مقاومت کویل',
            'type' => 'text'
        ],
        'chargingType' => [
            'meta_key' => '_charging_type',
            'label' => 'نوع شارژ',
            'type' => 'text'
        ],
        'displayType' => [
            'meta_key' => '_display_type',
            'label' => 'نوع نمایشگر',
            'type' => 'text'
        ],
        'weight' => [
            'meta_key' => '_product_weight_custom',
            'label' => 'وزن',
            'type' => 'text'
        ],
        'dimensions' => [
            'meta_key' => '_product_dimensions_custom',
            'label' => 'ابعاد',
            'type' => 'text'
        ],
        'materials' => [
            'meta_key' => '_product_materials',
            'label' => 'مواد سازنده',
            'type' => 'textarea'
        ],
        'chipset' => [
            'meta_key' => '_chipset',
            'label' => 'چیپست',
            'type' => 'text'
        ],
        'colors' => [
            'meta_key' => '_available_colors',
            'label' => 'رنگ‌های موجود',
            'type' => 'array'
        ],
        'airflow' => [
            'meta_key' => '_airflow_type',
            'label' => 'جریان هوا',
            'type' => 'text'
        ],
        'vapeType' => [
            'meta_key' => '_vape_type',
            'label' => 'نوع ویپ',
            'type' => 'text'
        ],
            ];
    }

    /**
     * Save custom fields to product
     */
    public static function save_product_fields($product_id, $custom_fields) {
        if (empty($custom_fields) || !is_array($custom_fields)) {
            return;
        }
        
        $mapping = self::get_product_fields_mapping();
        
        foreach ($custom_fields as $key => $value) {
            if (isset($mapping[$key]) && !empty($value)) {
                $meta_key = $mapping[$key]['meta_key'];
                
                if ($mapping[$key]['type'] === 'array' && is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                update_post_meta($product_id, $meta_key, sanitize_text_field($value));
            }
        }
        
        // Save raw custom fields
        update_post_meta($product_id, '_pbr_custom_fields', $custom_fields);
    }

    /**
     * Get custom fields from product
     */
    public static function get_product_fields($product_id) {
        $mapping = self::get_product_fields_mapping();
        $fields = [];
        
        foreach ($mapping as $key => $config) {
            $value = get_post_meta($product_id, $config['meta_key'], true);
            if (!empty($value)) {
                $fields[$key] = $value;
            }
        }
        
        return $fields;
    }
}