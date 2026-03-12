<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FPD_Current_Filter_Label_Tag_V3 extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() { return 'fpd_current_filter_label_v3'; }
    public function get_title() { return __( 'FPD Current Filter Label V3', 'fpd-catalog-v3' ); }
    public function get_group() { return 'site'; }
    public function get_categories() { return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ]; }
    public function render() {
        echo '<span class="fpd-dynamic-filter-label-v3"></span>';
    }
}
