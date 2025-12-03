# MaicroBoard

A lightweight, high-performance bulletin board system designed for simplicity and ease of use.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.0.0-green.svg)](https://github.com/mytajimilife-coder/maicroboard)

## ✨ Features

- 🚀 **Lightweight & Fast** - Optimized for performance with minimal dependencies
- 🌍 **Multi-language Support** - Korean, English, Japanese, and Chinese
- 🔐 **OAuth Social Login** - Google, LINE, and Apple integration
- 🔒 **Secure** - Built-in CSRF, SQL Injection, and XSS protection
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile
- ⭐ **Point System** - Reward users for posting and engagement
- 🎨 **Multiple Skins** - Choose from different board layouts
- 📝 **Rich Text Editor** - Summernote WYSIWYG with image upload
- 👥 **User Management** - Complete admin panel

## 🔐 OAuth Social Login

MaicroBoard supports seamless integration with popular OAuth providers:

| Provider | Status | Setup Guide |
|----------|--------|-------------|
| 🔵 Google | ✅ Supported | [Google Cloud Console](https://console.cloud.google.com/) |
| 🟢 LINE | ✅ Supported | [LINE Developers](https://developers.line.biz/console/) |
| ⚫ Apple | ✅ Supported | [Apple Developer](https://developer.apple.com/account/) |

### OAuth Features
- ✅ Automatic button visibility based on configuration
- ✅ Visual status indicators in admin panel
- ✅ Secure state parameter for CSRF protection
- ✅ Automatic user account creation
- ✅ Multi-language support for all OAuth flows

See [OAUTH_SETUP.md](OAUTH_SETUP.md) for detailed setup instructions.

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10.2+
- Apache or Nginx web server
- PDO PHP Extension
- cURL PHP Extension (for OAuth)

## 🚀 Quick Start

### Installation

1. Download or clone the repository
```bash
git clone https://github.com/mytajimilife-coder/maicroboard.git
```

2. Upload files to your web server

3. Navigate to `http://your-domain.com/install.php`

4. Follow the installation wizard:
   - Choose your preferred language
   - Configure database settings
   - Create admin account
   - Complete installation

5. Login and start using MaicroBoard!

### OAuth Configuration

After installation, configure OAuth providers:

1. Login as admin
2. Go to **Admin Panel** → **OAuth Settings**
3. For each provider:
   - Enter **Client ID**
   - Enter **Client Secret**
   - Check **Enable** checkbox
4. Social login buttons will automatically appear

**Note:** Buttons only appear when all credentials are configured and enabled.

## 🌍 Supported Languages

- 🇰🇷 Korean (한국어)
- 🇺🇸 English
- 🇯🇵 Japanese (日本語)
- 🇨🇳 Chinese (中文)

Switch languages from the language selector on any page.

## 👨‍💼 Admin Features

Access the admin panel at `/admin/index.php`:

- **User Management** - View, manage, and delete users
- **Board Management** - Create and configure multiple boards
- **OAuth Settings** - Configure social login providers
- **Point System** - Enable/disable points and set rewards
- **Configuration** - Customize board settings

## 📁 Project Structure

```
maicroboard/
├── admin/              # Admin panel
│   ├── oauth.php      # OAuth settings
│   ├── users.php      # User management
│   └── board.php      # Board management
├── inc/               # Include files
│   └── oauth.php      # OAuth helper functions
├── lang/              # Language files
│   ├── ko.php         # Korean
│   ├── en.php         # English
│   ├── ja.php         # Japanese
│   └── zh.php         # Chinese
├── skin/              # Board skins
├── install.php        # Installation wizard
├── oauth_callback.php # OAuth callback handler
└── OAUTH_SETUP.md     # OAuth setup guide
```

## 🔧 Configuration

### Database Migration

For existing installations, run database updates:

```
http://your-domain.com/update_db_oauth.php
```

This adds OAuth tables and configurations.

### Point System

Configure in Admin Panel → Configuration:
- Enable/disable point system
- Set points awarded for posting
- Points are automatically tracked per user

## 🛡️ Security

MaicroBoard includes built-in security features:

- ✅ CSRF token protection
- ✅ Prepared statements (SQL Injection prevention)
- ✅ XSS protection with htmlspecialchars
- ✅ Session timeout (30 minutes)
- ✅ Password hashing with bcrypt
- ✅ OAuth state parameter validation
- ✅ Input validation and sanitization

## 📖 Documentation

- [OAuth Setup Guide](OAUTH_SETUP.md) - Detailed OAuth configuration
- [Security Guide](SECURITY.md) - Security best practices
- [GitHub Pages](https://mytajimilife-coder.github.io/maicroboard/) - Online documentation

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Summernote](https://summernote.org/) - WYSIWYG editor
- [jQuery](https://jquery.com/) - JavaScript library

## 📧 Support

- Create an [Issue](https://github.com/mytajimilife-coder/maicroboard/issues)
- Check the [Documentation](https://mytajimilife-coder.github.io/maicroboard/)

---

Made with ❤️ by MaicroBoard Team

**Version 1.0.0** | [Documentation](https://mytajimilife-coder.github.io/maicroboard/) | [Report Bug](https://github.com/mytajimilife-coder/maicroboard/issues)
