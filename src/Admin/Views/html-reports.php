<?php
/**
 * Show the reports page.
 *
 * @since 1.0.0
 * @package WooCommerceSerialNumbers\Admin\Views
 */

defined( 'ABSPATH' ) || exit;

$page_url = admin_url( 'admin.php?page=wc-serial-numbers-reports' );
?>
<div class="wrap woocommerce">
	<?php if ( is_array( $tabs ) && sizeof( $tabs ) > 1 ) : ?>
		<h2 class="nav-tab-wrapper wcsn-nav-tabs">
			<?php foreach ( $tabs as $tab_id => $tab_title ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $tab_id ), $page_url ) ); ?>" class="nav-tab <?php echo $tab_id === $current_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $tab_title ); ?></a>
			<?php endforeach; ?>
		</h2>
	<?php endif; ?>
	<hr class="wp-header-end">
	<div class="wcsn-reports-tab-content wcsn-reports-tab-<?php echo esc_attr( $current_tab ); ?>">
		<?php do_action( 'wc_serial_numbers_reports_tab_' . $current_tab ); ?>
	</div>
</div>
