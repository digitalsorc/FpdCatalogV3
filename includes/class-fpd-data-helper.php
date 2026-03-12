<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FPD_Data_Helper_V3 {

    public static function get_fpd_version() {
        if ( defined( 'FANCY_PRODUCT_DESIGNER_VERSION' ) ) {
            return FANCY_PRODUCT_DESIGNER_VERSION;
        }
        return 'Unknown';
    }

    public static function get_base_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_products';
        
        $suppress = $wpdb->suppress_errors( true );
        $results = $wpdb->get_results( "SELECT ID, title, options FROM {$table_name} ORDER BY title ASC" );
        $wpdb->suppress_errors( $suppress );

        $products = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $row ) {
                $products[ $row->ID ] = $row->title;
            }
        }
        return $products;
    }

    public static function get_design_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_categories';
        
        $suppress = $wpdb->suppress_errors( true );
        $results = $wpdb->get_results( "SELECT ID, title FROM {$table_name} ORDER BY title ASC" );
        $wpdb->suppress_errors( $suppress );

        $categories = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $row ) {
                $categories[ $row->ID ] = $row->title;
            }
        }
        return $categories;
    }

    public static function get_printing_box( $base_product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_views';
        
        $suppress = $wpdb->suppress_errors( true );
        $view = $wpdb->get_row( $wpdb->prepare( "SELECT elements FROM {$table_name} WHERE product_id = %d ORDER BY view_order ASC LIMIT 1", $base_product_id ) );
        $wpdb->suppress_errors( $suppress );
        
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
        
        return ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100];
    }
    
    public static function get_base_product_image( $base_product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_views';
        
        $suppress = $wpdb->suppress_errors( true );
        $view = $wpdb->get_row( $wpdb->prepare( "SELECT thumbnail FROM {$table_name} WHERE product_id = %d ORDER BY view_order ASC LIMIT 1", $base_product_id ) );
        $wpdb->suppress_errors( $suppress );
        
        return $view ? $view->thumbnail : '';
    }

    public static function query_items( $args ) {
        global $wpdb;
        
        $designs_table = $wpdb->prefix . 'fpd_designs';
        
        $where = ["1=1"];

        if ( ! empty( $args['category'] ) ) {
            $where[] = "category_id IN (" . implode(',', array_map('intval', (array)$args['category'])) . ")";
        }

        $where_clause = implode( ' AND ', $where );
        
        $orderby = 'ID';
        if ( $args['orderby'] === 'title' ) $orderby = 'title';
        if ( $args['orderby'] === 'date' ) $orderby = 'ID';
        
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        
        $limit = intval( $args['per_page'] );
        $offset = ( intval( $args['page'] ) - 1 ) * $limit;

        $sql = "SELECT * FROM {$designs_table} WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $prepared_sql = $wpdb->prepare( $sql, $limit, $offset );
        
        $suppress = $wpdb->suppress_errors( true );
        $designs = $wpdb->get_results( $prepared_sql );
        $wpdb->suppress_errors( $suppress );
        
        $items = [];
        $base_products = !empty($args['base_product']) ? (array)$args['base_product'] : array_keys(self::get_base_products());
        
        if (empty($base_products)) {
            return [];
        }

        $base_product_id = $base_products[0];
        $printing_box = self::get_printing_box( $base_product_id );
        $base_product_image = self::get_base_product_image( $base_product_id );
        
        $base_product_titles = self::get_base_products();
        $base_product_title = isset($base_product_titles[$base_product_id]) ? $base_product_titles[$base_product_id] : '';

        if ( is_array( $designs ) ) {
            foreach ( $designs as $design ) {
                $design_image = isset($design->image) ? $design->image : (isset($design->thumbnail) ? $design->thumbnail : '');
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
                    'editor_url' => home_url( '/?fpd_product=' . $base_product_id . '&fpd_design=' . $design->ID ),
                    'category_id' => isset($design->category_id) ? $design->category_id : 0,
                ];
            }
        }

        return $items;
    }
}
