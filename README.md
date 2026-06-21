<p align="center">
  <img src="assets/logo.svg" alt="OpenWA Logo" width="220" />
</p>

<h1 align="center">OpenWA — WhatsApp Gateway for WooCommerce</h1>

<p align="center">
  Send WooCommerce order updates, invoices, OTPs, and admin alerts over WhatsApp through your own self-hosted <a href="https://github.com/rmyndharis/OpenWA">OpenWA</a> gateway.
</p>

<p align="center">
  <a href="https://github.com/rmyndharis/OpenWA-WhatsApp-Gateway"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version" /></a>
  <img src="https://img.shields.io/badge/WooCommerce-6.0%2B-96588A.svg" alt="WooCommerce" />
  <img src="https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg" alt="WordPress" />
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg" alt="PHP" />
  <img src="https://img.shields.io/badge/license-GPLv2-green.svg" alt="License" />
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs Welcome" />
</p>

---

## 🚀 Overview

OpenWA — WhatsApp Gateway for WooCommerce brings your store notifications into WhatsApp. Use a self-hosted OpenWA server for secure, reliable messaging without third-party WhatsApp APIs.

---

## 📸 Screenshots

<table align="center" cellspacing="0" cellpadding="8">
  <tr>
    <td align="center"><img src="Screenshots/Plugin Admin Dashboard Page.png" alt="Admin Dashboard" width="300" /><br/>Admin Dashboard</td>
    <td align="center"><img src="Screenshots/Plugin Settings Page.png" alt="Plugin Settings" width="300" /><br/>Plugin Settings</td>
    <td align="center"><img src="Screenshots/Plugin Template Page.png" alt="Plugin Template Page" width="300" /><br/>Template Page</td>
  </tr>
</table>

---

## ✨ Core Benefits

- Send WhatsApp messages for WooCommerce order updates
- Deliver PDF invoices directly in WhatsApp chat
- Authenticate users with WhatsApp OTP
- Alert admins about new orders and daily sales
- Customize every message with shortcode templates

---

## ✅ Features

- Order status notifications for pending, processing, completed, on-hold, cancelled, refunded, and failed orders
- Admin new order alerts
- Invoice PDF sending from order status notifications or on-demand
- Customizable templates with rich shortcode support
- OTP verification with shortcode form support
- Daily digest summaries for admin
- Session management and QR-code login in WordPress
- WP-CLI commands for debugging and testing

---

## 📌 Requirements

> **Important:** You must have a running **OpenWA** server first to use this plugin.

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+
- OpenWA Server (self-hosted) — [Installation Guide](https://youtu.be/iiKMgT5FlHw)
- WhatsApp account connected via QR code

### Optional

- WooCommerce PDF Invoices & Packing Slips plugin for PDF invoice attachments

---

## 🎬 OpenWA Installation Guide

[![OpenWA Installation Guide](https://img.youtube.com/vi/iiKMgT5FlHw/maxresdefault.jpg)](https://youtu.be/iiKMgT5FlHw)

---

## 🎬 Video Tutorial (of this plugin)

[![Video Tutorial](https://img.youtube.com/vi/0QRfJd-k3dg/maxresdefault.jpg)](https://youtu.be/0QRfJd-k3dg)

---

## ⚙️ Quick Start

1. Download the plugin ZIP and upload via **Plugins → Add New → Upload Plugin**, or copy the `wp-openwa-gateway` folder into `wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen.
3. Go to **OpenWA → Settings** and enter your OpenWA server details:
   - **Server URL**: `https://your-openwa-server:2785`
   - **API Key**: Your OpenWA API key
4. Open **OpenWA → Sessions**, create a session, and scan the QR code with WhatsApp.
5. Configure templates in **OpenWA → Templates**.
6. Set the admin notification phone in **OpenWA → Settings**.

---

## 🔧 Configuration

### OpenWA → Dashboard

A status overview of your plugin, sessions, and recent activity.

### OpenWA → Settings

| Setting | Description |
|---------|-------------|
| Server URL | Your OpenWA instance URL, e.g. `https://example.com:2785` or `http://localhost:2785` |
| API Key | Your OpenWA server API key |
| Default Country Code | Country code for phone normalization, e.g. `880` |
| Admin Notification Phone | Phone number to receive admin alerts and digests |
| Enable Daily Digest | Toggle daily summary notifications |
| Enable OTP | Turn OTP verification on or off |

> **Local dev tip:** For self-signed SSL during development, run:
> ```bash
> NODE_TLS_REJECT_UNAUTHORIZED=0 npm run dev
> ```

### OpenWA → Sessions

Create and manage WhatsApp sessions, scan the QR code, and keep your connection active.

### OpenWA → Templates

Build and fine-tune every WhatsApp message with variables for order data, customer info, and invoice details.

---

## 💬 Template Shortcodes

| Shortcode | Description | Use Case |
|-----------|-------------|----------|
| `{order_id}` | Order number | All order templates |
| `{order_status}` | Status label | All order templates |
| `{order_total}` | Formatted total | All order templates |
| `{subtotal}` | Order subtotal | All order templates |
| `{shipping_total}` | Shipping amount | All order templates |
| `{discount_total}` | Discount amount | Admin new order |
| `{tax_total}` | Tax amount | All order templates |
| `{order_date}` | Order date | All order templates |
| `{order_time}` | Order time | All order templates |
| `{customer_name}` | Billing name | All order templates |
| `{customer_first_name}` | Billing first name | All order templates |
| `{customer_email}` | Billing email | All order templates |
| `{customer_phone}` | Billing phone | Admin new order |
| `{customer_address}` | Billing address | All order templates |
| `{shipping_address}` | Shipping address | Order processing, Admin new order |
| `{payment_method}` | Payment method | All templates |
| `{shipping_method}` | Shipping method | Order processing/Completed |
| `{items}` | Item summary | All order templates |
| `{items_detail}` | Itemized products | All order templates |
| `{order_note}` | Customer note | Admin new order |
| `{site_name}` | Site name | All templates |
| `{site_url}` | Site URL | Admin new order |
| `{otp}` | One-time password | OTP template only |
| `{date}` | Current date | Daily digest |
| `{total_orders}` | Today's order count | Daily digest |
| `{total_revenue}` | Today's revenue | Daily digest |

### Template Events

| Event | Trigger | Recipient |
|-------|---------|-----------|
| Pending Payment | Order status becomes pending | Customer |
| Processing | Order becomes processing | Customer |
| Completed | Order becomes completed | Customer |
| On Hold | Order becomes on-hold | Customer |
| Cancelled | Order becomes cancelled | Customer |
| Refunded | Order becomes refunded | Customer |
| Failed | Order becomes failed | Customer |
| New Order | New order created | Admin |
| Customer Registered | New user register | Customer |
| OTP Verification | OTP request | Customer |
| Daily Digest | Scheduled daily summary | Admin |
| Invoice (PDF) | Invoice sent with attachment | Customer |

---

## 🧾 Invoice PDF Integration

When the **Invoice (with PDF)** template is enabled, order notifications can include the generated invoice PDF in one WhatsApp message.

- If PDF generation succeeds, the invoice is sent with the message caption.
- If PDF generation fails, the plugin falls back to plain text notification.

### On-Demand Invoice Actions

From the WooCommerce order edit screen you can:

- **Send Invoice** — sends invoice PDF with caption text
- **Resend Notification** — resend the order notification message

These controls are available in the **OpenWA WhatsApp** meta box.

### PDF Requirements

Requires [WooCommerce PDF Invoices & Packing Slips](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/).

---

## 🔐 OTP Verification

Enable OTP verification in **OpenWA → Settings** and use the shortcode:

```php
[openwa_otp]
```

OTP flow:
1. User submits phone number
2. WhatsApp OTP is sent
3. User verifies the OTP
4. Verified data is stored in user meta

---

## 🧪 WP-CLI Commands

Run tests and debug with `wp openwa`.

```bash
# Dry-run PDF invoice send
wp openwa test-pdf-send <order_id>

# Send PDF invoice
wp openwa test-pdf-send <order_id> --send
```

---

## ⭐ Star the Repos

If this plugin helps you, please star both repos:

- OpenWA: [https://github.com/rmyndharis/OpenWA](https://github.com/rmyndharis/OpenWA)
- This plugin: [https://github.com/rmyndharis/OpenWA-WhatsApp-Gateway](https://github.com/rmyndharis/OpenWA-WhatsApp-Gateway)

---

## 🤝 Contribution

Issues and pull requests are welcome. Please contribute ideas, bug reports, and improvements.

---

## 📄 License

GPLv2

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
