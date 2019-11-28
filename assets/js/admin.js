var WCSN_Admin = {};
(function ($, window, wp, document, undefined) {
	'use strict';
	WCSN_Admin = {
		init: function () {
			$('.select-2').select2();

			$('.wcsn-select-date').datepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'yy-mm-dd',
				firstDay: 7,
				minDate: new Date()
			});
			WCSN_Admin.handleAutoGeneratedKey();
		},
		showWsnSerialKey: function (e) {
			e.preventDefault();
			var self = $(this);
			var id = self.data('serial-id');
			var nonce = self.data('nonce') || null;
			wp.ajax.send('wcsn_show_serial_key', {
				data: {
					serial_id: id,
					nonce: nonce
				},
				success: function (res) {
					console.log(res.message);
					$('#wsn-admin-serial-key-' + id).text(res.message);
				},
				error: function () {

				}
			});
		},
		handleAutoGeneratedKey: function () {
			var value = $('#_serial_key_source').val();
			if ('custom_source' === value) {
				$('.serial-numbers-custom-generated').show();
				$('._serial_number_key_prefix_field, ._activation_limit_field, ._validity_field').hide();
			} else if ('auto_generated' === value) {
				$('.serial-numbers-custom-generated').hide();
				$('._serial_number_key_prefix_field, ._activation_limit_field, ._validity_field').show();
			}

		}
	};

	$(document).ready(WCSN_Admin.init);
	$(document).on('click', '.wsn-show-serial-key', WCSN_Admin.showWsnSerialKey);
	$(document).on('change', '#_serial_key_source', WCSN_Admin.handleAutoGeneratedKey);

})(jQuery, window, window.wp, document, undefined);
