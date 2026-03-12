<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FPD_Catalog_V3_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'fpd_catalog_v3';
	}

	public function get_title() {
		return __( 'FPD Catalog V3', 'fpd-catalog-v3' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_script_depends() {
		return [ 'fpd-catalog-v3-render-js', 'fpd-catalog-v3-filter-js' ];
	}

	public function get_style_depends() {
		return [ 'fpd-catalog-v3-css' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'fpd-catalog-v3' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'fpd_source',
			[
				'label' => __( 'Source', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'all',
				'options' => [
					'all'  => __( 'All Designs', 'fpd-catalog-v3' ),
					'category' => __( 'By Category', 'fpd-catalog-v3' ),
					'base_product' => __( 'By Base Product', 'fpd-catalog-v3' ),
				],
			]
		);

        $base_products = FPD_Data_Helper_V3::get_base_products();
		$this->add_control(
			'fpd_base_products',
			[
				'label' => __( 'Base Products', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => $base_products,
				'condition' => [
					'fpd_source' => [ 'base_product', 'all', 'category' ], // Usually needed for compositing anyway
				],
			]
		);

        $categories = FPD_Data_Helper_V3::get_design_categories();
		$this->add_control(
			'fpd_design_categories',
			[
				'label' => __( 'Design Categories', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => $categories,
				'condition' => [
					'fpd_source' => 'category',
				],
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label' => __( 'Items Per Page', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 24,
			]
		);

		$this->add_control(
			'orderby',
			[
				'label' => __( 'Order By', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date' => __( 'Date Added', 'fpd-catalog-v3' ),
					'title' => __( 'Title', 'fpd-catalog-v3' ),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label' => __( 'Order', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC' => __( 'ASC', 'fpd-catalog-v3' ),
					'DESC' => __( 'DESC', 'fpd-catalog-v3' ),
				],
			]
		);

		$this->add_control(
			'show_design_title',
			[
				'label' => __( 'Show Design Title', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_base_product_label',
			[
				'label' => __( 'Show Base Product Label', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'link_action',
			[
				'label' => __( 'Link Action', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'editor',
				'options' => [
					'editor' => __( 'Open FPD Editor', 'fpd-catalog-v3' ),
					'lightbox' => __( 'Lightbox Preview', 'fpd-catalog-v3' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			[
				'label' => __( 'Style', 'fpd-catalog-v3' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label' => __( 'Columns', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options' => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'selectors' => [
					'{{WRAPPER}} .fpd-catalog-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'gap',
			[
				'label' => __( 'Gap', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range' => [
					'px' => [ 'min' => 0, 'max' => 100 ],
				],
				'default' => [ 'unit' => 'px', 'size' => 20 ],
				'selectors' => [
					'{{WRAPPER}} .fpd-catalog-grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'advanced_auto_detect_section',
			[
				'label' => __( 'Advanced Auto-Detection', 'fpd-catalog-v3' ),
				'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'auto_detect_fpd_version',
			[
				'label' => __( 'FPD Version', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => 'Detected Version: <strong>' . FPD_Data_Helper_V3::get_fpd_version() . '</strong>',
			]
		);

		$this->add_control(
			'canvas_render_mode',
			[
				'label' => __( 'Render Mode', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'canvas',
				'options' => [
					'canvas' => __( 'Canvas API', 'fpd-catalog-v3' ),
					'css' => __( 'CSS Layers', 'fpd-catalog-v3' ),
				],
			]
		);

		$this->add_control(
			'lazy_load',
			[
				'label' => __( 'Lazy Load', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'cache_ttl',
			[
				'label' => __( 'Cache TTL (minutes)', 'fpd-catalog-v3' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 60,
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$widget_id = $this->get_id();
		
		$config = [
			'source' => $settings['fpd_source'],
			'categories' => $settings['fpd_design_categories'],
			'baseProducts' => $settings['fpd_base_products'],
			'perPage' => $settings['posts_per_page'],
			'orderBy' => $settings['orderby'],
			'order' => $settings['order'],
			'showTitle' => $settings['show_design_title'] === 'yes',
			'showLabel' => $settings['show_base_product_label'] === 'yes',
			'linkAction' => $settings['link_action'],
			'renderMode' => $settings['canvas_render_mode'],
			'lazyLoad' => $settings['lazy_load'] === 'yes',
			'cacheTtl' => $settings['cache_ttl'],
		];

		$this->add_render_attribute( 'wrapper', 'class', 'fpd-catalog-wrapper' );
		$this->add_render_attribute( 'wrapper', 'data-config', wp_json_encode( $config ) );

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<div class="fpd-catalog-grid" id="fpd-catalog-grid-<?php echo esc_attr( $widget_id ); ?>">
				<!-- Items will be rendered here by JS -->
			</div>
			<div class="fpd-catalog-loader" style="display: none;">
				<?php echo esc_html__( 'Loading...', 'fpd-catalog-v3' ); ?>
			</div>
		</div>
		<?php
	}
}
