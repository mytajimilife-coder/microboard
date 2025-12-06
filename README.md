# MicroBoard

<div align="center">

![MicroBoard](https://via.placeholder.com/150x150.png?text=MicroBoard)

### A Lightweight, High-Performance Community Platform
*Simple, Secure, and Extensible Bulletin Board System*

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.0.0-green.svg)](https://github.com/mytajimilife-coder/microboard)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

[**Explore Docs**](https://mytajimilife-coder.github.io/microboard/) | [**View Demo**](#) | [**Report Bug**](https://github.com/mytajimilife-coder/microboard/issues)

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
   - *(Optional)* Delete `install.php` after success for security.

4. **Post-Install Setup**
   - Go to `/admin` to configure OAuth keys and Point settings.

## 🔌 Plugin System

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
├── admin/                  # Admin Dashboard & Settings
├── docs/                   # Documentation (GitHub Pages)
├── lang/                   # Localization Files (ko, en, ja, zh)
├── plugin/                 # Plugin System Directory
├── skin/                   # Board Skins
├── install.php             # Installation Wizard
├── config.php              # Global Configuration
├── index.php               # Main Entry Point
└── OAUTH_SETUP.md          # OAuth Configuration Guide
```

## 📖 Documentation & Guides

- **[Features Guide (Korean)](FEATURES.md):** Detailed breakdown of all features.
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
