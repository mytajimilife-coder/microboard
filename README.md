# MicroBoard

<div align="center">

**MicroBoard**

<!-- <img src="![이미지주소.png](https://mytajimilife-coder.github.io/microboard/img/logo.svg)" /> -->
<!-- MicroBoard -->

### A Lightweight, High-Performance Community Platform

_Simple, Secure, and Extensible Bulletin Board System_

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.0.0-green.svg)](https://github.com/mytajimilife-coder/microboard)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

[**Explore Docs**](https://mytajimilife-coder.github.io/microboard/) | [**Report Bug**](https://github.com/mytajimilife-coder/microboard/issues)

</div>

---

## 🚀 Overview

**MicroBoard** is a modern PHP-based bulletin board system designed for performance and simplicity. Unlike heavy CMS platforms, MicroBoard focuses on providing essential community features—**Membership, Posting, Comments, and Points**—without the bloat.

It is built with **vanilla PHP and MySQL**, making it easy to deploy on any standard hosting environment while offering powerful extensibility through a unique **Plugin System**.

## ✨ Key Features

### 🌍 Global & Multilingual

- **4 Languages Supported:** Korean (한국어), English, Japanese (日本語), and Chinese (中文).
- **Auto-Detection:** Automatically detects user browser language.
- **Instant Switching:** Seamless language toggling without page reloads.

### 👥 Community Engagement

- **Point System:** Reward users for posting, commenting, and logging in. Configurable levels and ranks.
- **Member Levels:** 10-tier ranking system with automatic promotion capabilities.
- **Social Login (OAuth):** One-click sign-in with **Google**, **LINE**, and **Apple**.

### 🛠️ Powerful Extensibility

- **Plugin Architecture:** Hook-based system (`before_write`, `after_login`, etc.) to extend functionality without touching core code.
- **Theme/Skin Support:** Easily customizable board skins and layout templates.
- **Responsive Design:** Mobile-first approach ensuring perfect rendering on all devices.

### 🛡️ Enterprise-Grade Security

- **Security First:** Built-in protection against **CSRF**, **XSS**, and **SQL Injection**.
- **Secure File Uploads:** Validates MIME types and randomizes filenames to prevent malicious execution.
- **Policy Management:** Auto-generating "Terms of Service" and "Privacy Policy" in 4 languages.
- **Two-Factor Authentication (2FA):** Email-based 2FA system for enhanced account security. Users can enable 2FA in their profile settings, and administrators can control 2FA availability through email settings.
- **Advanced Permission System:** Granular control over board access with 10-tier level system for list, read, and write permissions.

### 🔍 Advanced Search Capabilities

- **Integrated Search:** Search across all boards simultaneously with keyword highlighting.
- **Board-Specific Search:** Filter by title, content, or author within individual boards.
- **Search Control:** Administrators can include/exclude specific boards from integrated search.
- **Real-time Results:** Fast search with pagination and result count display.

### ⚙️ Flexible Configuration

- **Board-Level Settings:** 
  - Permission control (list/read/write) with level-based access
  - Editor toggle (WYSIWYG or plain text)
  - Comment system enable/disable
  - Search inclusion control
- **SEO Optimization:**
  - Bing Webmaster Tools integration
  - Google Search Console verification
  - Google Analytics (GA4) support
  - Google Tag Manager integration
  - Google AdSense configuration
  - Custom header/footer scripts
- **Theme Customization:** Background images, colors, dark mode, and custom logos/favicons.

### 🔐 Two-Factor Authentication (2FA)

MicroBoard now supports **Two-Factor Authentication** for enhanced account security:

- **Email-Based 2FA:** Users receive verification codes via email for secure login.
- **Authenticator App Support:** Compatible with Google Authenticator, Authy, and other TOTP apps.
- **QR Code Setup:** Easy setup via QR code scanning.
- **Backup Codes:** One-time use backup codes for account recovery.
- **Admin Control:** Administrators can enable/disable 2FA globally via email settings.
- **User Control:** Users can enable/disable 2FA in their profile settings.

## 🏗️ Tech Stack

- **Backend:** PHP 7.4+ (PDO)
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **Frontend:** HTML5, CSS3, Vanilla JavaScript, jQuery
- **Editor:** Summernote WYSIWYG
- **Server:** Apache / Nginx

## 🚀 Quick Start

### Prerequisites

- PHP >= 7.4
- MySQL or MariaDB
- Apache/Nginx Web Server

### Installation Steps

1. **Clone the repository**

   ```bash
   git clone https://github.com/mytajimilife-coder/microboard.git
   ```

2. **Upload & Configure**

   - Upload all files to your web server root.
   - Ensure `config.php` and `data/` directories are writable.

3. **Run Installer**

   - Navigate to `http://your-domain.com/install.php`.
   - Follow the wizard to set up the database and admin account.
   - _(Optional)_ Delete `install.php` after success for security.

4. **Post-Install Setup**
   - Go to `/admin` to configure OAuth keys and Point settings.

## � Plugin System

MicroBoard features a lightweight hook system similar to WordPress. You can create custom plugins in the `plugin/` directory.

**Example Hook:**

```php
// In your plugin file
add_event('after_write', function($post_data) {
    // Send email notification or log data
    error_log("New post by: " . $post_data['author']);
});
```

## 📂 Project Structure

```text
microboard/
├── admin/                  # Admin Panel
│   ├── board.php           # Board Management (with permissions)
│   ├── config.php          # System Configuration
│   ├── index.php           # Dashboard
│   ├── oauth.php           # OAuth Settings
│   ├── policy.php          # Policy Management
│   ├── seo.php             # SEO & Analytics Settings
│   └── users.php           # User Management
├── inc/                    # Core Includes
│   ├── header.php          # Global Header (with SEO tags)
│   ├── footer.php          # Global Footer (with custom scripts)
│   └── oauth.php           # OAuth Helper Functions
├── lang/                   # Localization (en, ja, ko, zh)
├── plugin/                 # Plugin System
├── skin/                   # Board Skins (Themes)
├── user/                   # User Pages
│   ├── mypage.php          # Profile & Activity
│   └── withdraw.php        # Account Deletion
├── config.php              # Global Configuration
├── install.php             # Installation Wizard
├── index.php               # Main Landing Page
├── list.php                # Board List View (with search)
├── view.php                # Post View
├── write.php               # Post Creation/Edit (with editor toggle)
├── search.php              # Integrated Search
├── login.php               # Login Page
├── register.php            # Registration Page
├── policy.php              # Terms & Privacy Policy
├── oauth_callback.php      # OAuth Callback Handler
├── sitemap.php             # Sitemap Generator
├── update_db_permissions.php  # Permission System DB Update
├── update_db_editor.php    # Editor Settings DB Update
├── update_db_search.php    # Search Settings DB Update
└── update_db_seo.php       # SEO Settings DB Update
```

## 📖 Documentation & Guides

- **[Features Guide (Korean)](FEATURES.md):** Detailed breakdown of all features.
- **[Features Guide (English)](FEATURES_EN.md):** Detailed breakdown of all features in English.
- **[Features Guide (Japanese)](FEATURES_JA.md):** Detailed breakdown of all features in Japanese.
- **[Features Guide (Chinese)](FEATURES_ZH.md):** Detailed breakdown of all features in Chinese.
- **[OAuth Setup Guide](OAUTH_SETUP.md):** Step-by-step instructions for Google, LINE, and Apple login.
- **[Security Policy](SECURITY.md):** Information on security practices and vulnerability reporting.

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the project.
2. Create your feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

## 📝 License

Distributed under the MIT License. See `LICENSE` for more information.

---

<div align="center">
  <p>Made with ❤️ by the MicroBoard Team</p>
  <p>
    <a href="https://github.com/mytajimilife-coder/microboard/issues">Report Bug</a> •
    <a href="https://github.com/mytajimilife-coder/microboard/discussions">Request Feature</a>
  </p>
</div>

---

> ⚠️ **Note:** MicroBoard v1.0.0 is currently in **Beta**. While fully functional, some features are still under active development. We welcome your feedback and contributions as we refine the platform.

---

**Available in:**

- 🇰🇷 [한국어](README.md)
- 🇺🇸 [English](README.md)
- 🇯🇵 [日本語](README.md)
- 🇨🇳 [中文](README.md)
