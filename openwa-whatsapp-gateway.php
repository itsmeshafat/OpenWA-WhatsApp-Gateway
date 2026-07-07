<?php
/**
 * Plugin Name:       OpenWA - WhatsApp Gateway for WooCommerce
 * Plugin URI:        https://github.com/itsmeshafat/OpenWA-WhatsApp-Gateway
 * Description:       Send WhatsApp notifications for WooCommerce orders, OTP verification, and more using the OpenWA self-hosted WhatsApp API gateway.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Shafat Mahmud Khan
 * Author URI:        https://itsmeshafat.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       openwa-whatsapp-gateway
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 6.0
 * WC tested up to:   9.7
 */

defined( 'ABSPATH' ) || exit;

define( 'OPENWA_VERSION', '1.0.1' );
define( 'OPENWA_PLUGIN_FILE', __FILE__ );
define( 'OPENWA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPENWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', 'openwa_declare_woocommerce_compatibility' );
function openwa_declare_woocommerce_compatibility() {
	if ( class_exists( Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
	}
}

require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-logger.php';
require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-api.php';
require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-session.php';
require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-message.php';
require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-notifier.php';
require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-otp.php';
require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-invoice.php';
require_once OPENWA_PLUGIN_DIR . 'admin/class-openwa-admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once OPENWA_PLUGIN_DIR . 'includes/class-openwa-cli.php';
}

add_action( 'init', 'openwa_load_textdomain' );
function openwa_load_textdomain() {
	load_plugin_textdomain( 'openwa-whatsapp-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

register_activation_hook( __FILE__, 'openwa_activate' );
function openwa_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'OpenWA Gateway requires WooCommerce to be installed and activated.', 'openwa-whatsapp-gateway' ) );
	}
	add_option( 'openwa_settings', array(
		'base_url'      => '',
		'api_key'       => '',
		'session_id'    => '',
		'enable_otp'    => 'no',
		'default_code'  => '',
		'admin_phone'   => '',
		'enable_digest' => 'no',
	) );
	openwa_save_default_templates();
}

function openwa_get_default_templates() {
	return array(
		'order_pending' => array(
			'name'    => __( 'Order - Pending Payment', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Thank you for your order #{order_id}!

We have received your order and are waiting for the payment to be confirmed.

Items:
{items_detail}

Subtotal: {subtotal}
Total: {order_total}

Payment method: {payment_method}

Once payment is confirmed, we will start processing your order.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'order_processing' => array(
			'name'    => __( 'Order - Processing', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Great news! Your order #{order_id} is now being processed.

Items:
{items_detail}

Subtotal: {subtotal}
Shipping: {shipping_method} ({shipping_total})
Total: {order_total}

Ship to:
{shipping_address}

We will notify you once it ships.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'order_completed' => array(
			'name'    => __( 'Order - Completed', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Your order #{order_id} has been completed!

Items:
{items_detail}

Total: {order_total}
Payment: {payment_method}
Shipping: {shipping_method}

Thank you for shopping with us! We hope to see you again soon.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'order_on-hold' => array(
			'name'    => __( 'Order - On Hold', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Your order #{order_id} is currently on hold.

Items:
{items_detail}

Total: {order_total}

We need to verify some details before we can proceed. We will contact you shortly.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => false,
		),
		'order_cancelled' => array(
			'name'    => __( 'Order - Cancelled', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Your order #{order_id} has been cancelled.

Items:
{items_detail}

Total: {order_total}

If you did not request this cancellation or have any questions, please contact us.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'order_refunded' => array(
			'name'    => __( 'Order - Refunded', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Your order #{order_id} has been refunded.

Items:
{items_detail}

Total refunded: {order_total}
Payment method: {payment_method}

The refund has been processed and the amount will be returned to your original payment method within 5-10 business days.

If you have any questions, please contact us.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'order_failed' => array(
			'name'    => __( 'Order - Failed', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Unfortunately, the payment for your order #{order_id} has failed.

Items:
{items_detail}

Total: {order_total}
Payment method: {payment_method}

Please try placing the order again or use a different payment method.

If you need assistance, please contact us.

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => false,
		),
		'new_order_admin' => array(
			'name'    => __( 'New Order (Admin)', 'openwa-whatsapp-gateway' ),
			'message' => __( 'New order received!

Order: #{order_id}
Date: {order_date} {order_time}
Status: {order_status}

Customer: {customer_name}
Phone: {customer_phone}
Email: {customer_email}

Items:
{items_detail}

Subtotal: {subtotal}
Shipping: {shipping_method} ({shipping_total})
Discount: -{discount_total}
Total: {order_total}

Payment: {payment_method}

Billing:
{customer_address}

Shipping:
{shipping_address}

Customer note: {order_note}

{site_url}', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'customer_registered' => array(
			'name'    => __( 'Customer Registered', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Welcome {customer_name}! Thank you for registering at {site_name}. We are excited to have you!', 'openwa-whatsapp-gateway' ),
			'enabled' => false,
		),
		'otp' => array(
			'name'    => __( 'OTP Verification', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Your OTP is: {otp}. It expires in 5 minutes. Do not share this code with anyone.', 'openwa-whatsapp-gateway' ),
			'enabled' => true,
		),
		'daily_digest' => array(
			'name'    => __( 'Daily Digest (Admin)', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Daily Summary for {site_name} - {date}

Orders: {total_orders}
Revenue: {total_revenue}', 'openwa-whatsapp-gateway' ),
			'enabled' => false,
		),
		'invoice' => array(
			'name'    => __( 'Invoice (with PDF)', 'openwa-whatsapp-gateway' ),
			'message' => __( 'Hi {customer_name},

Your invoice #{order_id} from {site_name} is attached.

Items:
{items_detail}

Subtotal: {subtotal}
Shipping: {shipping_total}
Total: {order_total}

Payment method: {payment_method}
Order date: {order_date}

Thank you for your business!

{site_name}', 'openwa-whatsapp-gateway' ),
			'enabled' => false,
		),
	);
}

function openwa_save_default_templates() {
	update_option( 'openwa_message_templates', openwa_get_default_templates() );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'openwa_plugin_action_links' );
function openwa_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=openwa-settings' ) ) . '">' . esc_html__( 'Settings', 'openwa-whatsapp-gateway' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'plugin_row_meta', 'openwa_plugin_row_meta', 10, 2 );
function openwa_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) !== $file ) {
		return $links;
	}
	$bmc_link = '<a href="https://www.buymeacoffee.com/itsmeshafat" target="_blank" style="color:#ff813f;font-weight:600;">' . esc_html__( 'Buy Me a Coffee', 'openwa-whatsapp-gateway' ) . '</a>';
	$links[] = $bmc_link;
	return $links;
}

register_deactivation_hook( __FILE__, 'openwa_deactivate' );
function openwa_deactivate() {
	wp_clear_scheduled_hook( 'openwa_health_check' );
}

add_action( 'plugins_loaded', 'openwa_init' );
function openwa_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'openwa_missing_woocommerce_notice' );
		return;
	}

	OpenWA_Logger::init();
	OpenWA_Admin::init();

	add_action( 'admin_notices', 'openwa_missing_pdf_invoices_notice' );

	$settings = get_option( 'openwa_settings', array() );
	if ( ! empty( $settings['base_url'] ) && ! empty( $settings['api_key'] ) ) {
		OpenWA_Notifier::init();
		OpenWA_OTP::init();
	}
}

function openwa_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$wc_url = admin_url( 'plugin-install.php?tab=search&s=woocommerce' );
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php esc_html_e( 'OpenWA - WhatsApp Gateway for WooCommerce', 'openwa-whatsapp-gateway' ); ?></strong>
			<?php esc_html_e( 'requires WooCommerce to be installed and activated.', 'openwa-whatsapp-gateway' ); ?>
			<a href="<?php echo esc_url( $wc_url ); ?>"><?php esc_html_e( 'Install WooCommerce', 'openwa-whatsapp-gateway' ); ?></a>
		</p>
	</div>
	<?php
}

function openwa_missing_pdf_invoices_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	if ( function_exists( 'wcpdf_get_document' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && strpos( $screen->id, 'openwa' ) === false && strpos( $screen->id, 'woocommerce' ) === false ) {
		return;
	}
	$pdf_url = admin_url( 'plugin-install.php?tab=search&s=woocommerce+pdf+invoices' );
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'OpenWA - WhatsApp Gateway', 'openwa-whatsapp-gateway' ); ?></strong>
			<?php esc_html_e( 'Invoice PDF attachment requires', 'openwa-whatsapp-gateway' ); ?>
			<a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">WooCommerce PDF Invoices & Packing Slips</a>.
			<a href="<?php echo esc_url( $pdf_url ); ?>"><?php esc_html_e( 'Install plugin', 'openwa-whatsapp-gateway' ); ?></a>
		</p>
	</div>
	<?php
}
