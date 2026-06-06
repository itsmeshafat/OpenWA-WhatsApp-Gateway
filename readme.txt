=== OpenWA - WhatsApp Gateway for WooCommerce ===
Contributors: openwa
Donate link: https://github.com/sponsors/rmyndharis
Tags: whatsapp, woocommerce, sms, notification, order, otp, messaging, openwa
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WooCommerce order notifications, OTP verification, and customer messages via WhatsApp using the self-hosted OpenWA API gateway.

== Description ==

OpenWA Gateway integrates your WooCommerce store with the OpenWA self-hosted WhatsApp API, enabling you to send order status updates, new order alerts, OTP codes for login, and custom messages directly to your customers' WhatsApp.

**Key Features:**

* **Order Notifications:** Automatically send WhatsApp messages when order status changes (pending, processing, completed, cancelled, refunded, on-hold, failed).
* **Admin Alerts:** Get notified on your WhatsApp when new orders arrive.
* **OTP Login Verification:** Send one-time passwords via WhatsApp for secure customer login.
* **Customizable Templates:** Full control over message content with shortcodes like `{order_id}`, `{customer_name}`, `{order_total}`, and more.
* **Manual Sending:** Send custom WhatsApp messages directly from the WooCommerce order edit screen.
* **Test Messages:** Send test messages to verify your setup.
* **Daily Digest:** Optional daily order summary sent to admin WhatsApp.
* **Message Logging:** Full audit trail of all sent messages and errors.

**Shortcodes Available:**

`{order_id}`, `{order_status}`, `{order_total}`, `{customer_name}`, `{customer_email}`, `{customer_phone}`, `{payment_method}`, `{items}`, `{site_name}`, `{otp}`

**Requirements:**

* Self-hosted OpenWA server (see https://github.com/rmyndharis/OpenWA)
* WooCommerce 6.0 or later
* PHP 7.4 or later

= Disclaimer =

This plugin requires a self-hosted OpenWA server. You are responsible for complying with WhatsApp's Terms of Service and applicable laws regarding messaging and customer communications. OpenWA is not affiliated with WhatsApp or Meta.

== Installation ==

1. Upload the `openwa-whatsapp-gateway` folder to `/wp-content/plugins/` or install via WordPress plugin search.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the new **OpenWA** menu in your admin sidebar.
4. Enter your OpenWA server URL and API key in **Settings**.
5. Click **Test Connection** to verify.
6. Go to **Sessions**, create and start a WhatsApp session, and scan the QR code with your phone.
7. Customize your message templates in **Templates**.
8. Enable desired notifications and OTP features in **Settings**.

== Frequently Asked Questions ==

= What is OpenWA? =

OpenWA is a free, self-hosted WhatsApp API gateway. It provides a REST API and WebSocket interface to send and receive WhatsApp messages programmatically. It uses whatsapp-web.js and requires a Chrome/Chromium browser.

= Do I need any external service? =

Yes, you need to run your own OpenWA server instance. See the OpenWA GitHub repository for setup instructions: https://github.com/rmyndharis/OpenWA

= Will this work with the WhatsApp Business API? =

No. OpenWA uses whatsapp-web.js which connects to WhatsApp Web, not the official WhatsApp Business API. It works with a regular WhatsApp account.

= How do I get an API key? =

After installing OpenWA, access the OpenWA web dashboard, go to API Keys, and create a new key with at least the "operator" role.

= Is OTP login secure? =

OTP codes are generated server-side, stored as WordPress transients with a 5-minute expiry, and sent directly to the user's registered phone number via the OpenWA API.

= How do I format phone numbers? =

Include the country code without + or 00. Example: 628123456789 for an Indonesian number. The plugin automatically appends @c.us for WhatsApp format.

== Screenshots ==

1. OpenWA dashboard showing connection status and session info
2. Session management with QR code scanning
3. Message template editor with shortcode insertion
4. WooCommerce order screen with WhatsApp message box
5. Test message page for verification

== Changelog ==

= 1.0.0 =
* Initial release
* WooCommerce order status notifications
* Admin new order alerts
* OTP verification for login
* Customizable message templates
* Session management with QR code pairing
* Manual message sending from order screen
* Test message functionality
* Daily order digest
* Full activity logging

== Upgrade Notice ==

= 1.0.0 =
Initial release.
