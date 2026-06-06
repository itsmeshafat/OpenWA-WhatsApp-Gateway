<p align="center">
  <img src="assets/logo.svg" alt="OpenWA Logo" width="200"/>
</p>

<h1 align="center">OpenWA — WhatsApp Gateway for WooCommerce</h1>

<p align="center">
  Send WhatsApp notifications for WooCommerce orders, OTP verification, and invoice PDFs — powered by the <a href="https://github.com/rmyndharis/OpenWA">OpenWA</a> self-hosted API gateway.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version"/>
  <img src="https://img.shields.io/badge/WooCommerce-6.0%2B-96588A.svg" alt="WooCommerce"/>
  <img src="https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg" alt="WordPress"/>
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg" alt="PHP"/>
  <img src="https://img.shields.io/badge/license-GPLv2-green.svg" alt="License"/>
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs Welcome"/>
</p>

---

## Features

| Feature | Description |
|---------|-------------|
| Order Status Notifications | Automatic WhatsApp alerts for pending, processing, completed, on-hold, cancelled, refunded, and failed orders |
| Admin Notifications | New order alerts sent to the store owner's WhatsApp |
| Invoice PDF via WhatsApp | Attach a PDF invoice to order notifications or send on demand |
| OTP Verification | Verify customer phone numbers with one-time passwords |
| Daily Digest | Scheduled daily summary of orders and revenue |
| Customer Welcome | Automated welcome message on customer registration |
| Customizable Templates | Full control over every message with rich shortcode variables |
| Order Action Buttons | Send invoice or resend notification from the order edit screen |
| WP-CLI Commands | Test PDF sending, check configuration, and more |

---

## Requirements

- **WordPress** 5.8+
- **WooCommerce** 6.0+
- **PHP** 7.4+
- **OpenWA Server** (self-hosted) — [Installation Guide](https://github.com/rmyndharis/OpenWA)
- **WhatsApp Account** — to connect via QR code

### Optional

- **WooCommerce PDF Invoices & Packing Slips** plugin — required for PDF invoice attachment feature

---

## Installation

1. Download the plugin ZIP and upload via **Plugins → Add New → Upload Plugin**, or copy the `wp-openwa-gateway` folder into `wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen.
3. Go to **OpenWA → Settings** and configure your OpenWA server connection:
   - **Server URL**: `https://your-openwa-server:2785`
   - **API Key**: The API key from your OpenWA server
4. Go to **OpenWA → Sessions**, create a session, and scan the QR code with WhatsApp.
5. Configure your templates at **OpenWA → Templates**.
6. Set the admin notification phone under **OpenWA → Settings**.

---

## Configuration

### OpenWA → Dashboard

Overview of plugin status, session connection, and recent activity.

### OpenWA → Settings

| Setting | Description |
|---------|-------------|
| Server URL | Your OpenWA instance URL (e.g., `https://example.com:2785` or `http://localhost:2785` for local dev) |
| API Key | The API key from your OpenWA server settings |
| Default Country Code | Country code for phone number normalization (e.g., `880` for Bangladesh) |
| Admin Notification Phone | Phone number to receive new order alerts and daily digests |
| Enable Daily Digest | Toggle the scheduled daily summary |
| Enable OTP | Toggle OTP verification via WhatsApp |

> **Local Development**: If using a self-signed SSL certificate, start OpenWA with:
> ```bash
> NODE_TLS_REJECT_UNAUTHORIZED=0 npm run dev
> ```

### OpenWA → Sessions

Create a new WhatsApp session, scan the QR code with your phone, and manage active sessions.

### OpenWA → Templates

Customize every message sent via WhatsApp. Each template supports rich shortcode variables for dynamic content.

---

## Template Shortcodes Reference

| Shortcode | Description | Available In |
|-----------|-------------|--------------|
| `{order_id}` | Order number | All order templates |
| `{order_status}` | Status display name | All order templates |
| `{order_total}` | Formatted total with currency | All order templates |
| `{subtotal}` | Order subtotal | All order templates |
| `{shipping_total}` | Shipping cost (empty if free) | All order templates |
| `{discount_total}` | Discount amount (empty if none) | Admin new order |
| `{tax_total}` | Tax amount (empty if none) | All order templates |
| `{order_date}` | Order date | All order templates |
| `{order_time}` | Order time | All order templates |
| `{customer_name}` | Full billing name | All order templates |
| `{customer_first_name}` | Billing first name only | All order templates |
| `{customer_email}` | Billing email | All order templates |
| `{customer_phone}` | Billing phone | Admin new order |
| `{customer_address}` | Formatted billing address | All order templates |
| `{shipping_address}` | Formatted shipping address | Order processing, Admin new order |
| `{payment_method}` | Payment method title | All order templates |
| `{shipping_method}` | Shipping method name | Order processing/completed |
| `{items}` | Items summary (e.g., T-shirt x2, Mug x1) | All order templates |
| `{items_detail}` | Items detail per line with prices | All order templates |
| `{order_note}` | Customer order note (empty if none) | Admin new order |
| `{site_name}` | Site/blog name | All templates |
| `{site_url}` | Site homepage URL | Admin new order |
| `{otp}` | One-time password | OTP template only |
| `{date}` | Current date | Daily digest |
| `{total_orders}` | Today's order count | Daily digest |
| `{total_revenue}` | Today's total revenue | Daily digest |

### Default Templates

Each order status has a default template pre-loaded on activation. The default messages include full order breakdown — items, subtotal, shipping, payment method, and addresses. You can customize any template from the admin panel.

### Template Events

| Event | Trigger | Recipient |
|-------|---------|-----------|
| Order - Pending Payment | Order status set to pending | Customer |
| Order - Processing | Order status set to processing | Customer |
| Order - Completed | Order status set to completed | Customer |
| Order - On Hold | Order status set to on-hold | Customer |
| Order - Cancelled | Order status set to cancelled | Customer |
| Order - Refunded | Order status set to refunded | Customer |
| Order - Failed | Order status set to failed | Customer |
| New Order (Admin) | Any new order created | Admin |
| Customer Registered | New customer account | Customer |
| OTP Verification | OTP requested | Customer |
| Daily Digest (Admin) | Daily cron schedule | Admin |
| Invoice (with PDF) | Used as caption when PDF is attached to order notifications | Customer |

---

## Invoice PDF Integration

### With Automatic Notifications

When the **Invoice (with PDF)** template is enabled at **OpenWA → Templates**, incoming order status notifications are automatically combined with the invoice PDF. Instead of two separate messages (status text + invoice), the customer receives a single message with the PDF attached.

The PDF caption uses the Invoice template text with all shortcodes replaced. If PDF generation fails, the notification falls back to plain text.

### On-Demand (Order Edit Screen)

From the WooCommerce order edit page, you can:

- **Send Invoice** — Sends the invoice PDF with the invoice template caption to the customer's WhatsApp
- **Resend Notification** — Re-sends the applicable order status notification

These buttons appear in the **OpenWA WhatsApp** meta box on the order edit screen.

### Requirements

The PDF attachment feature requires the [WooCommerce PDF Invoices & Packing Slips](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/) plugin to be installed and active.

---

## OTP Verification

Enable OTP verification under **OpenWA → Settings**. Use the `[openwa_otp]` shortcode to add a phone verification form to any page:

```php
[openwa_otp]
```

The OTP flow:
1. User enters their phone number
2. An OTP is sent via WhatsApp
3. User enters the OTP to verify
4. Verified phone numbers are stored in user meta

---

## WP-CLI Commands

The plugin provides WP-CLI commands under the `wp openwa` namespace:

```bash
# Test PDF invoice sending (dry-run)
wp openwa test-pdf-send <order_id>

# Test PDF invoice sending and actually send
wp openwa test-pdf-send <order_id> --send

# Send to a specific phone number
wp openwa test-pdf-send <order_id> --send --phone=628123456789
```

The test command walks through:
1. Plugin configuration check
2. Order loading and phone validation
3. Session status verification
4. PDF invoice generation
5. URL-based sending (primary method)
6. Base64 fallback (if PDF < 70KB)
7. Helpful error hints for common issues

---

## Local Development Workarounds

### Self-Signed SSL Certificates

If your local OpenWA server uses a self-signed certificate, Node.js will reject the connection. Start OpenWA with:

```bash
NODE_TLS_REJECT_UNAUTHORIZED=0 npm run dev
```

### WordPress Behind Nginx Reverse Proxy

If WordPress and OpenWA run on the same machine behind an nginx reverse proxy:

- Ensure the nginx configuration for your WordPress site properly proxies or allows connections to the OpenWA port
- The plugin's Server URL setting should point to the OpenWA instance (e.g., `https://localhost:2785`)

---

## Troubleshooting

### "No WhatsApp session configured"

- Go to **OpenWA → Sessions** and ensure a session exists and is connected (QR scanned).
- Check the session status — it should show as "ready" or "connected".

### "Failed to send document" / PDF not sending

1. Check that the WooCommerce PDF Invoices plugin is installed and active.
2. Verify the PDF is generated correctly by viewing it from the WooCommerce order admin.
3. Run `wp openwa test-pdf-send <order_id>` for a detailed diagnostic.
4. If using HTTPS, ensure your SSL certificate is valid. For local dev, start OpenWA with `NODE_TLS_REJECT_UNAUTHORIZED=0`.

### Template checkboxes not saving

- This was a known issue (admin defaults were missing the `invoice` key). Update to the latest version.
- If you still experience this, go to **OpenWA → Logs** and check for save errors.

### Messages show escaped slashes (e.g., `We\'ll`)

- The plugin now avoids all contractions with apostrophes in default templates. Update and resave your templates.
- If you have custom templates with apostrophes, edit them in the Templates page and save again.

### "API returned error" in logs

- Verify the OpenWA server is running: `curl http://localhost:2785/api/health`
- Check the API key matches what's configured in OpenWA's settings.
- Ensure the server URL has the correct protocol and port.

### OTP not sending

- Verify OTP is enabled in **OpenWA → Settings**.
- Ensure the session is connected and ready.
- Check the customer has a valid billing phone number.

---

## Plugin Architecture

```
wp-openwa-gateway/
├── openwa-whatsapp-gateway.php    # Plugin bootstrap & activation hooks
├── uninstall.php                   # Cleanup on uninstall
├── admin/
│   ├── class-openwa-admin.php      # Admin pages, settings, templates
│   ├── css/admin.css               # Admin styles
│   └── views/                      # Admin page templates
│       ├── dashboard-page.php
│       ├── settings-page.php
│       ├── sessions-page.php
│       ├── templates-page.php
│       ├── test-page.php
│       └── logs-page.php
└── includes/
    ├── class-openwa-api.php        # OpenWA REST API client
    ├── class-openwa-invoice.php    # PDF invoice generation & sending
    ├── class-openwa-logger.php     # Logging system
    ├── class-openwa-message.php    # Template parsing & message sending
    ├── class-openwa-notifier.php   # Event-driven notification logic
    ├── class-openwa-otp.php        # OTP verification
    ├── class-openwa-session.php    # Session management
    └── class-openwa-cli.php        # WP-CLI commands
```

---

## Changelog

### 1.0.0
- Initial release
- Order status notifications (7 statuses)
- Admin new order alerts
- Invoice PDF attachment (with WooCommerce PDF Invoices)
- Send invoice on-demand from order edit screen
- Customizable templates with rich shortcodes
- OTP verification
- Daily digest
- Customer registration welcome
- WP-CLI test commands
- Comprehensive logging

---

## Author

**Shafat Mahmud Khan**

- Website: [https://itsmeshafat.com](https://itsmeshafat.com)
- GitHub: [https://github.com/itsmeshafat](https://github.com/itsmeshafat)

---

## License

GPL v2 or later — see [LICENSE](./LICENSE) for details.

---

## Links

- [OpenWA Server](https://github.com/itsmeshafat/OpenWA) — Self-hosted WhatsApp API gateway
- [Plugin Repository](https://github.com/itsmeshafat/OpenWA-WhatsApp-Gateway) — Plugin source code
- [Report Bug](https://github.com/itsmeshafat/OpenWA-WhatsApp-Gateway/issues) — GitHub issues
- [WooCommerce PDF Invoices](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/) — Required for PDF attachment
