<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class to fetch FPD data
 */
class FPD_Data_Helper {

    /**
     * Get FPD version
     */
    public static function get_fpd_version() {
        if ( defined( 'FANCY_PRODUCT_DESIGNER_VERSION' ) ) {
            return FANCY_PRODUCT_DESIGNER_VERSION;
        }
        return 'Unknown';
    }

    /**
     * Get all FPD base products
     */
    public static function get_base_products() {
        global $wpdb;
        // ASSUMPTION: FPD products are stored in fpd_products table
        $table_name = $wpdb->prefix . 'fpd_products';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            return [];
        }

        $results = $wpdb->get_results( "SELECT ID, title, options FROM $table_name ORDER BY title ASC" );
        $products = [];
        foreach ( $results as $row ) {
            $products[ $row->ID ] = $row->title;
        }
        return $products;
    }

    /**
     * Get all FPD design categories
     */
    public static function get_design_categories() {
        global $wpdb;
        // ASSUMPTION: FPD categories are stored in fpd_categories table
        $table_name = $wpdb->prefix . 'fpd_categories';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            return [];
        }

        $results = $wpdb->get_results( "SELECT ID, title FROM $table_name ORDER BY title ASC" );
        $categories = [];
        foreach ( $results as $row ) {
            $categories[ $row->ID ] = $row->title;
        }
        return $categories;
    }

    /**
     * Get printing box for a base product
     */
    public static function get_printing_box( $base_product_id ) {
        global $wpdb;
        // ASSUMPTION: printing box is stored in the views table or options of the product
        $table_name = $wpdb->prefix . 'fpd_views';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            return ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100];
        }

        // Get the first view of the product
        $view = $wpdb->get_row( $wpdb->prepare( "SELECT elements FROM $table_name WHERE product_id = %d ORDER BY view_order ASC LIMIT 1", $base_product_id ) );
        
        if ( $view && ! empty( $view->elements ) ) {
            $elements = json_decode( $view->elements, true );
            if ( is_array( $elements ) ) {
                foreach ( $elements as $element ) {
                    if ( isset( $element['title'] ) && strtolower( $element['title'] ) === 'printing box' ) {
                        return [
                            'x' => isset($element['parameters']['left']) ? floatval($element['parameters']['left']) : 0,
                            'y' => isset($element['parameters']['top']) ? floatval($element['parameters']['top']) : 0,
                            'width' => isset($element['parameters']['width']) ? floatval($element['parameters']['width']) : 100,
                            'height' => isset($element['parameters']['height']) ? floatval($element['parameters']['height']) : 100,
                        ];
                    }
                }
            }
        }
        
        // Fallback
        return ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100];
    }
    
    /**
     * Get base product image
     */
    public static function get_base_product_image( $base_product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_views';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            return '';
        }
        $view = $wpdb->get_row( $wpdb->prepare( "SELECT thumbnail FROM $table_name WHERE product_id = %d ORDER BY view_order ASC LIMIT 1", $base_product_id ) );
        return $view ? $view->thumbnail : '';
    }

    /**
     * Query items based on args
     */
    public static function query_items( $args ) {
        global $wpdb;
        
        $designs_table = $wpdb->prefix . 'fpd_designs';
        
        // Return empty if tables don't exist
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$designs_table'" ) != $designs_table ) {
            return [];
        }

        $where = ["1=1"];

        if ( ! empty( $args['category'] ) ) {
            // ASSUMPTION: category is stored in category_id column
            $where[] = "category_id IN (" . implode(',', array_map('intval', (array)$args['category'])) . ")";
        }

        $where_clause = implode( ' AND ', $where );
        
        $orderby = 'ID';
        if ( $args['orderby'] === 'title' ) $orderby = 'title';
        if ( $args['orderby'] === 'date' ) $orderby = 'ID'; // Assuming ID correlates with date
        
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        
        $limit = intval( $args['per_page'] );
        $offset = ( intval( $args['page'] ) - 1 ) * $limit;

        $sql = "SELECT * FROM $designs_table WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $prepared_sql = $wpdb->prepare( $sql, $limit, $offset );
        
        $designs = $wpdb->get_results( $prepared_sql );
        
        $items = [];
        $base_products = !empty($args['base_product']) ? (array)$args['base_product'] : array_keys(self::get_base_products());
        
        // If no base products, we can't composite
        if (empty($base_products)) {
            return [];
        }

        $base_product_id = $base_products[0];
        $printing_box = self::get_printing_box( $base_product_id );
        $base_product_image = self::get_base_product_image( $base_product_id );
        
        // Get base product title
        $base_product_titles = self::get_base_products();
        $base_product_title = isset($base_product_titles[$base_product_id]) ? $base_product_titles[$base_product_id] : '';

        foreach ( $designs as $design ) {
            // ASSUMPTION: design image URL is stored in 'image' column or similar
            $design_image = isset($design->image) ? $design->image : (isset($design->thumbnail) ? $design->thumbnail : '');
            // Some versions store it as JSON in parameters
            if (isset($design->parameters)) {
                $params = json_decode($design->parameters, true);
                if (isset($params['source'])) {
                    $design_image = $params['source'];
                }
            }

            $items[] = [
                'design_id' => $design->ID,
                'design_title' => $design->title,
                'design_image_url' => $design_image,
                'base_product_id' => $base_product_id,
                'base_product_title' => $base_product_title,
                'base_product_image_url' => $base_product_image,
                'printing_box' => $printing_box,
                'editor_url' => home_url( '/?fpd_product=' . $base_product_id . '&fpd_design=' . $design->ID ), // Example URL
                'category_id' => isset($design->category_id) ? $design->category_id : 0,
            ];
        }

        return $items;
    }
}
