/* global wp, jQuery */
( function ( $, api ) {
	'use strict';

	// Toggle: stores 'yes' / 'no'.
	api.controlConstructor['acfw-toggle'] = api.Control.extend( {
		ready: function () {
			var control = this;
			control.container.on( 'change', 'input[type=checkbox]', function () {
				control.setting.set( this.checked ? 'yes' : 'no' );
			} );
		}
	} );

	// Slider: keep range + number inputs in sync.
	api.controlConstructor['acfw-slider'] = api.Control.extend( {
		ready: function () {
			var control = this;
			var $range = control.container.find( '.acfw-cz-slider' );
			var $num = control.container.find( '.acfw-cz-slider-value input[type=number]' );
			if ( ! $range.length || ! $num.length ) {
				return;
			}
			$range.on( 'input change', function () {
				$num.val( this.value );
				control.setting.set( this.value );
			} );
			$num.on( 'input change', function () {
				$range.val( this.value );
				control.setting.set( this.value );
			} );
		}
	} );

	// Show dependent controls only while their master toggle is on ( live in
	// the panel, before the preview refreshes ).
	function dependsOn( master, deps ) {
		api( master, function ( setting ) {
			function apply( value ) {
				var on = 'yes' === value;
				deps.forEach( function ( id ) {
					api.control( id, function ( control ) {
						control.active.set( on );
					} );
				} );
			}
			apply( setting.get() );
			setting.bind( apply );
		} );
	}

	dependsOn( 'acfw_avatar_enable', [
		'acfw_avatar_image',
		'acfw_avatar_shape',
		'acfw_avatar_align',
		'acfw_avatar_size',
		'acfw_avatar_show_name',
		'acfw_avatar_show_role',
		'acfw_avatar_upload',
		'acfw_avatar_upload_max'
	] );

	dependsOn( 'acfw_dashboard_stats', [
		'acfw_stat_orders',
		'acfw_stat_pending',
		'acfw_stat_spent',
		'acfw_stat_downloads',
		'acfw_stat_refunds',
		'acfw_stat_points',
		'acfw_stat_latest',
		'acfw_stat_piechart'
	] );

} )( jQuery, wp.customize );
