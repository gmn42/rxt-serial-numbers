<?php

namespace WooCommerceSerialNumbers\Lib;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * @since 1.0.0
 * @version 1.0.4
 * @subpackage WooCommerceSerialNumbers\Lib\Settings
 * @package WooCommerceSerialNumbers\Lib
 */
abstract class Settings {
	/**
	 * Init settings.
	 *
	 * @since 1.0.3
	 * @return self
	 */
	public static function instance() {
		static $instance = null;
		$class_name      = get_called_class();
		if ( null === $instance ) {
			$instance = new $class_name();
		}

		return $instance;
	}

	/**
	 * Init settings.
	 *
	 * @since 1.0.0
	 * @depecated 1.0.3
	 * @return self
	 */
	public static function get_instance() {
		_doing_it_wrong( __FUNCTION__, 'Use static::instance() instead.', '1.0.3' );
		return static::instance();
	}

	/**
	 * Settings constructor.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'buffer_start' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ), 1 );
	}

	/**
	 * Buffer start.
	 *
	 * @since 1.0.0
	 */
	public function buffer_start() {
		ob_start();
	}

	/**
	 * Get settings tabs.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	abstract public function get_tabs();

	/**
	 * Get settings.
	 *
	 * @param string $tab Tab name.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	abstract public function get_settings( $tab );

	/**
	 * Save settings.
	 *
	 * @since 1.0.0
	 * @return bool True if saved, false otherwise.
	 */
	public function save_settings() {
		$class_name = get_called_class();
		if ( empty( $_POST ) || ! isset( $_POST[ $class_name ] ) ) {
			return false;
		}
		check_admin_referer( $class_name );
		$current_tab = $this->get_current_tab();
		$settings    = $this->get_settings( $current_tab );
		if ( class_exists( '\WC_Admin_Settings' ) && ! empty( $settings ) && \WC_Admin_Settings::save_fields( $settings ) ) {
			add_settings_error( $class_name, 'response', __( 'Settings saved.', 'framework-text-domain' ), 'updated' );

			return true;
		}

		return false;
	}

	/**
	 * Output settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_settings() {
		$tabs        = $this->get_tabs();
		$current_tab = $this->get_current_tab();
		$tab_exists  = isset( $tabs[ $current_tab ] );
		$settings    = $this->get_settings( $current_tab );
		if ( ! empty( $tabs ) && ! $tab_exists && ! headers_sent() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_current_page() ) );
			exit();
		}
		?>
		<div class="wrap pev-wrap woocommerce">
			<nav class="nav-tab-wrapper pev-navbar">
				<?php $this->output_tabs( $tabs ); ?>
			</nav>
			<hr class="wp-header-end">
			<div class="pev-poststuff">
				<div class="column-1">
					<?php $this->output_form( $settings ); ?>
				</div>
				<div class="column-2">
					<?php $this->output_widgets(); ?>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function () {
				document.querySelectorAll('[data-cond-id]').forEach(function (element) {
					var $this = element;
					var conditional_id = $this.getAttribute('data-cond-id');
					var conditional_value = $this.getAttribute('data-cond-value') || '';
					var conditional_operator = $this.getAttribute('data-cond-operator') || '==';
					var $conditional_field = document.getElementById(conditional_id);
					$conditional_field.addEventListener('change', function () {
						var value = this.value.trim();
						if (this.type === 'checkbox' || this.type === 'radio') {
							conditional_operator = 'checked';
						}

						var show = false;
						if (conditional_operator === '==') {
							show = value == conditional_value ? true : false; // eslint-disable-line eqeqeq
						} else if (conditional_operator === '!=') {
							show = value != conditional_value; // eslint-disable-line eqeqeq
						} else if (conditional_operator === 'contains') {
							show = value.indexOf(conditional_value) > -1;
						} else if (conditional_operator === 'checked') {
							show = this.checked;
						} else {
							show = false;
						}

						if (show) {
							$this.closest('tr').style.display = 'table-row';
						} else {
							$this.closest('tr').style.display = 'none';
						}
					});

					$conditional_field.dispatchEvent(new Event('change'));
				});

				// if Jquery is not loaded, return.
				if (typeof jQuery === 'undefined') {
					return;
				}

				// trigger change event on load.
				jQuery(document).ready(function ($) {
					// check if iris is loaded.
					if (typeof $.fn.iris !== 'undefined') {
						// Color picker.
						$('.colorpick')
							.iris({
								change: function (event, ui) {
									$(this)
										.parent()
										.find('.colorpickpreview')
										.css({backgroundColor: ui.color.toString()});
								},
								hide: true,
								border: true,
							})
							.on('click focus', function (event) {
								event.stopPropagation();
								$('.iris-picker').hide();
								$(this).closest('td').find('.iris-picker').show();
								$(this).data('originalValue', $(this).val());
							})
							.on('change', function () {
								if ($(this).is('.iris-error')) {
									var original_value = $(this).data('originalValue');
									if (
										original_value.match(
											/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/
										)
									) {
										$(this)
											.val($(this).data('originalValue'))
											.trigger('change');
									} else {
										$(this).val('').trigger('change');
									}
								}
							});

						$('body').on('click', function () {
							$('.iris-picker').hide();
						});
					}
				});
			});
		</script>

		<?php
	}

	/**
	 * Output tabs.
	 *
	 * @param array $tabs Tabs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_tabs( $tabs ) {
		foreach ( $tabs as $tab_id => $tab_name ) {
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->get_current_page() . '&tab=' . $tab_id ) ); ?>" class="nav-tab <?php echo esc_attr( $this->get_current_tab() === $tab_id ? 'nav-tab-active' : '' ); ?>">
				<?php echo esc_html( $tab_name ); ?>
			</a>
			<?php
		}
	}

	/**
	 * Output settings form.
	 *
	 * @param array $settings Settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_form( $settings ) {
		if ( ! empty( $settings ) ) {
			$class_name = get_called_class();
			settings_errors( $class_name );
			?>
			<form method="post" id="mainform" action="" enctype="multipart/form-data">
				<?php
				if ( function_exists( 'woocommerce_admin_fields' ) ) {
					woocommerce_admin_fields( $settings );
				}
				?>
				<?php wp_nonce_field( $class_name ); ?>
				<?php submit_button( null, 'primary', $class_name ); ?>
			</form>
			<?php
		}
	}

	/**
	 * Output settings sidebar.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_widgets() {
		$this->output_premium_widget();
		$this->output_plugins_widget();
		$this->output_support_widget();
	}

	/**
	 * Output premium widget.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_premium_widget() {
		// Premium widget.
	}

	/**
	 * Output promo plugins.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_plugins_widget() {
		$promo_plugins = $this->get_promo_plugins();
		if ( ! empty( $promo_plugins ) ) {
			$installed = get_plugins();
			foreach ( $promo_plugins as $promo_plugin ) {
				$promo_plugin = wp_parse_args(
					$promo_plugin,
					array(
						'name'        => '',
						'description' => '',
						'basename'    => '',
						'slug'        => '',
						'badge'       => esc_html__( 'Recommended', 'framework-text-domain' ),
						'button'      => esc_html__( 'Install Now', 'framework-text-domain' ),
						'installed'   => false,
					)
				);
				// If basename or slug is not set, skip.
				if ( empty( $promo_plugin['basename'] ) && empty( $promo_plugin['slug'] ) ) {
					continue;
				}
				if ( ! empty( $promo_plugin['basename'] ) ) {
					$basename = $promo_plugin['basename'];
				} else {
					$basename = $promo_plugin['slug'] . '/' . $promo_plugin['slug'] . '.php';
				}
				if ( isset( $installed[ $basename ] ) ) {
					continue;
				}
				// get file name from basename.
				$basename_parts = explode( '/', $basename );
				$slug           = current( $basename_parts );
				$install_url    = add_query_arg(
					array(
						'action' => 'install-plugin',
						'plugin' => $slug,
					),
					network_admin_url( 'update.php' )
				);
				$install_url    = wp_nonce_url( $install_url, 'install-plugin_' . $slug );
				?>
				<div class="pev-panel">
					<?php if ( ! empty( $promo_plugin['badge'] ) ) : ?>
						<span class="pev-panel__legend"><?php echo esc_html( $promo_plugin['badge'] ); ?></span>
					<?php endif; ?>
					<div class="pev-panel__group">
						<span class="icon dashicons dashicons-admin-plugins"></span>
						<h3>
							<?php echo esc_html( $promo_plugin['name'] ); ?>
						</h3>
					</div>
					<?php echo wp_kses_post( wpautop( $promo_plugin['description'] ) ); ?>
					<a href="<?php echo esc_url( $install_url ); ?>" class="button" target="_blank">
						<?php echo esc_html( $promo_plugin['button'] ); ?>
					</a>
				</div>
				<?php
			}
		}
	}

	/**
	 * Output sidebar links.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_support_widget() {
		$support_links = $this->get_support_links();
		if ( ! empty( $support_links ) ) {
			?>
			<div class="pev-panel">
				<h3><?php esc_html_e( 'Need Help?', 'framework-text-domain' ); ?></h3>
				<ul>
					<?php foreach ( $support_links as $support_link ) : ?>
						<li>
							<a href="<?php echo esc_url( $support_link['url'] ); ?>" target="_blank">
								<?php echo esc_html( $support_link['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}
	}

	/**
	 * Get promo plugins.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_promo_plugins() {
		return array(
			array(
				'name'        => 'WC Min Max Quantities',
				'slug'        => 'wc-min-max-quantities',
				'description' => 'Set minimum and maximum price or quantity for WooCommerce products.',
				'link'        => 'https://wordpress.org/plugins/wc-min-max-quantities/',
				'badge'       => esc_html__( 'Recommended', 'framework-text-domain' ),
				'button'      => esc_html__( 'Install Now', 'framework-text-domain' ),
			),
			array(
				'name'        => 'Product Category Showcase for WooCommerce',
				'slug'        => 'wc-category-showcase',
				'description' => 'Display WooCommerce categories in a beautiful way.',
				'link'        => 'https://wordpress.org/plugins/wc-category-showcase/',
				'badge'       => esc_html__( 'Recommended', 'framework-text-domain' ),
				'button'      => esc_html__( 'Install Now', 'framework-text-domain' ),
			),
			array(
				'name'        => 'Product Category Slider for WooCommerce',
				'basename'    => 'woo-category-slider-by-pluginever/woo-category-slider.php',
				'description' => 'Display WooCommerce categories in a beautiful way.',
				'link'        => 'https://wordpress.org/plugins/woo-category-slider-by-pluginever/',
				'badge'       => esc_html__( 'Recommended', 'framework-text-domain' ),
				'button'      => esc_html__( 'Install Now', 'framework-text-domain' ),
			),
		);
	}

	/**
	 * Get support links.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_support_links() {
		return array(
			'facebook'        => array(
				'label' => __( 'Join our Community', 'framework-text-domain' ),
				'url'   => 'https://www.facebook.com/groups/pluginever',
			),
			'feature-request' => array(
				'label' => __( 'Request a Feature', 'framework-text-domain' ),
				'url'   => 'https://www.pluginever.com/contact/',
			),
			'bug-report'      => array(
				'label' => __( 'Report a Bug', 'framework-text-domain' ),
				'url'   => 'https://www.pluginever.com/contact/',
			),
		);
	}

	/**
	 * Get current page.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_current_page() {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS );
		return ! empty( $page ) ? sanitize_text_field( wp_unslash( $page ) ) : '';
	}

	/**
	 * Get the current tab.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_current_tab() {
		$tabs = $this->get_tabs();
		$tab  = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_SPECIAL_CHARS );
		$tab  = ! empty( $tab ) ? sanitize_text_field( wp_unslash( $tab ) ) : '';

		if ( ! array_key_exists( $tab, $tabs ) ) {
			$tab = key( $tabs );
		}

		return $tab;
	}

	/**
	 * Save default settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_defaults() {
		$tabs = $this->get_tabs();
		foreach ( $tabs as $tab => $label ) {
			$options = $this->get_settings( $tab );

			foreach ( $options as $option ) {
				if ( isset( $option['default'] ) && isset( $option['id'] ) ) {
					$autoload = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;
					add_option( $option['id'], $option['default'], '', $autoload );
				}
			}
		}
	}

	/**
	 * Output settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function output() {
		self::instance()->output_settings();
	}
}
