<?php
/**
 * Custom WordPress Customizer controls ( toggle, slider, buttonset, image-radio ),
 * ported to match the reference plugin's control UX.
 *
 * Loaded only inside the Customizer, where WP_Customize_Control exists.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'ACFW_Customize_Toggle' ) ) {

	/**
	 * On/off switch storing 'yes' / 'no'.
	 */
	class ACFW_Customize_Toggle extends WP_Customize_Control {

		public $type = 'acfw-toggle';

		public function to_json() {
			parent::to_json();
			$this->json['id']    = $this->id;
			$this->json['value'] = $this->value();
			$this->json['link']  = $this->get_link();
		}

		public function render_content() {}

		public function content_template() {
			?>
			<div class="acfw-toggle-control">
				<# if ( data.label ) { #><span class="customize-control-title">{{{ data.label }}}</span><# } #>
				<label class="acfw-switch-wrapper" for="acfw-toggle-{{ data.id }}">
					<input id="acfw-toggle-{{ data.id }}" type="checkbox" class="screen-reader-text" <# if ( 'yes' === data.value ) { #>checked="checked"<# } #> />
					<span class="acfw-cz-switch"><span class="acfw-cz-switch-thumb"></span></span>
				</label>
			</div>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>
			<?php
		}
	}

	/**
	 * Range slider ( number value ).
	 */
	class ACFW_Customize_Slider extends WP_Customize_Control {

		public $type = 'acfw-slider';

		public function to_json() {
			parent::to_json();
			$this->json['id']    = $this->id;
			$this->json['value'] = $this->value();
			$this->json['link']  = $this->get_link();

			$this->json['inputAttrs'] = '';
			foreach ( $this->input_attrs as $attr => $value ) {
				$this->json['inputAttrs'] .= $attr . '="' . esc_attr( $value ) . '" ';
			}
		}

		public function render_content() {}

		public function content_template() {
			?>
			<# if ( data.label ) { #><label class="customize-control-title">{{{ data.label }}}</label><# } #>
			<div class="acfw-cz-slider-wrap">
				<input type="range" class="acfw-cz-slider" {{{ data.inputAttrs }}} value="{{ data.value }}" />
				<span class="acfw-cz-slider-value">
					<input type="number" {{{ data.inputAttrs }}} value="{{ data.value }}" {{{ data.link }}} />
					<span class="acfw-cz-slider-unit">px</span>
				</span>
			</div>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>
			<?php
		}
	}

	/**
	 * Segmented button set ( radio group ).
	 */
	class ACFW_Customize_ButtonSet extends WP_Customize_Control {

		public $type = 'acfw-buttonset';

		public function to_json() {
			parent::to_json();
			$this->json['id']      = $this->id;
			$this->json['value']   = $this->value();
			$this->json['link']    = $this->get_link();
			$this->json['choices'] = $this->choices;
		}

		public function render_content() {}

		public function content_template() {
			?>
			<# if ( data.label ) { #><label class="customize-control-title">{{{ data.label }}}</label><# } #>
			<div class="acfw-cz-buttonset">
				<# Object.keys( data.choices ).forEach( function ( key ) { #>
					<input id="acfw-bs-{{ data.id }}-{{ key }}" type="radio" name="acfw-bs-{{ data.id }}" value="{{ key }}" {{{ data.link }}} {{{ ( data.value === key ) ? 'checked="checked"' : '' }}} />
					<label class="acfw-cz-buttonset-label" for="acfw-bs-{{ data.id }}-{{ key }}">{{{ data.choices[ key ] }}}</label>
				<# } ); #>
			</div>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>
			<?php
		}
	}

	/**
	 * Image radio ( thumbnail option picker ).
	 */
	class ACFW_Customize_ImageRadio extends WP_Customize_Control {

		public $type    = 'acfw-image_radio';
		public $columns = 3;

		public function to_json() {
			parent::to_json();
			$this->json['id']      = $this->id;
			$this->json['value']   = $this->value();
			$this->json['link']    = $this->get_link();
			$this->json['choices'] = $this->choices;
			$this->json['columns'] = $this->columns;
		}

		public function render_content() {}

		public function content_template() {
			?>
			<# if ( data.label ) { #><label class="customize-control-title">{{{ data.label }}}</label><# } #>
			<ul class="acfw-cz-image-radio" data-columns="{{ data.columns }}">
				<# Object.keys( data.choices ).forEach( function ( key ) { #>
					<li>
						<input id="acfw-ir-{{ data.id }}-{{ key }}" type="radio" name="acfw-ir-{{ data.id }}" value="{{ key }}" {{{ data.link }}} {{{ ( data.value === key ) ? 'checked="checked"' : '' }}} />
						<label class="acfw-cz-image-item" title="{{{ data.choices[ key ].name }}}" for="acfw-ir-{{ data.id }}-{{ key }}">
							<# if ( data.choices[ key ].image ) { #>
								<img src="{{{ data.choices[ key ].image }}}" alt="{{{ data.choices[ key ].name }}}" />
							<# } else { #>
								<span>{{{ data.choices[ key ].name }}}</span>
							<# } #>
							<span class="acfw-cz-image-label">{{{ data.choices[ key ].name }}}</span>
						</label>
					</li>
				<# } ); #>
			</ul>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>
			<?php
		}
	}
}
