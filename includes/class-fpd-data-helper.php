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

    public static function get_all_designs_list() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_designs';
        
        $suppress = $wpdb->suppress_errors( true );
        $results = $wpdb->get_results( "SELECT ID, title FROM {$table_name} ORDER BY title ASC" );
        $wpdb->suppress_errors( $suppress );

        $designs = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $row ) {
                $designs[ $row->ID ] = $row->title;
            }
        }
        return $designs;
    }

    public static function get_printing_box( $base_product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fpd_views';
        
        $suppress = $wpdb->suppress_errors( true );
        $view = $wpdb->get_row( $wpdb->prepare( "SELECT elements, options FROM {$table_name} WHERE product_id = %d ORDER BY view_order ASC LIMIT 1", $base_product_id ) );
        $wpdb->suppress_errors( $suppress );
        
        $stage_width = 800;
        $stage_height = 800;
        
        if ( $view && ! empty( $view->options ) ) {
            $options = is_string($view->options) ? json_decode($view->options, true) : $view->options;
            if (isset($options['stageWidth'])) $stage_width = floatval($options['stageWidth']);
            if (isset($options['stageHeight'])) $stage_height = floatval($options['stageHeight']);
        }

        if ( $view && ! empty( $view->elements ) ) {
            $elements = is_string($view->elements) ? json_decode( $view->elements, true ) : $view->elements;
            if ( is_array( $elements ) ) {
                foreach ( $elements as $element ) {
                    if ( isset( $element['title'] ) && stripos( $element['title'], 'printing box' ) !== false ) {
                        $left = isset($element['parameters']['left']) ? floatval($element['parameters']['left']) : 0;
                        $top = isset($element['parameters']['top']) ? floatval($element['parameters']['top']) : 0;
                        $width = isset($element['parameters']['width']) ? floatval($element['parameters']['width']) : 100;
                        $height = isset($element['parameters']['height']) ? floatval($element['parameters']['height']) : 100;
                        $originX = isset($element['parameters']['originX']) ? $element['parameters']['originX'] : 'left';
                        $originY = isset($element['parameters']['originY']) ? $element['parameters']['originY'] : 'top';

                        if ($originX === 'center') {
                            $left = $left - ($width / 2);
                        }
                        if ($originY === 'center') {
                            $top = $top - ($height / 2);
                        }

                        return [
                            'x' => $left,
                            'y' => $top,
                            'width' => $width,
                            'height' => $height,
                            'stage_width' => $stage_width,
                            'stage_height' => $stage_height
                        ];
                    }
                }
            }
        }
        
        return [
            'x' => 0, 
            'y' => 0, 
            'width' => $stage_width, 
            'height' => $stage_height,
            'stage_width' => $stage_width,
            'stage_height' => $stage_height
        ];
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

        if ( ! empty( $args['source'] ) && $args['source'] === 'specific_designs' && ! empty( $args['designs'] ) ) {
            $designs_arr = is_string($args['designs']) ? explode(',', $args['designs']) : (array)$args['designs'];
            $where[] = "ID IN (" . implode(',', array_map('intval', $designs_arr)) . ")";
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
