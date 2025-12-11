# MicroBoard Features Documentation

## 📋 Table of Contents

- [Core Features](#core-features)
- [Bulletin Board System](#bulletin-board-system)
- [Member Management](#member-management)
- [Social Login](#social-login)
- [Point System](#point-system)
- [Plugin System](#plugin-system)
- [Multilingual Support](#multilingual-support)
- [Admin Features](#admin-features)
- [Security Features](#security-features)

---

## 🌟 Core Features

MicroBoard is a lightweight, high-performance bulletin board system that allows you to build a powerful community platform without the complexity of large CMS.

### Key Features

- ⚡ **Lightweight & Fast**: Optimized performance with minimal dependencies
- 🌍 **Global Support**: 4 language support (Korean, English, Japanese, Chinese)
- 🔐 **Strong Security**: CSRF, SQL Injection, XSS protection
- 📱 **Responsive Design**: Perfect user experience on all devices
- 🔌 **Extensible**: Infinite expansion with plugin system

---

## 📝 Bulletin Board System

### Basic Board Features

MicroBoard's bulletin board provides intuitive and powerful features.

#### ✨ Post Writing

- **WYSIWYG Editor**: Rich text editing with Summernote editor
- **Image Upload**: Easy image attachment with drag and drop
- **File Attachment**: Support for various file formats
- **Real-time Preview**: Immediate preview while writing

#### 🔍 Search Function

- **Integrated Search**: Search by title, content, and author
- **Filtering**: Search by selecting specific fields
- **Sorting**: Sort by latest or views

#### 💬 Comment System

- **Hierarchical Comments**: Support for comments and replies
- **Real-time Registration**: Comment writing without page refresh
- **Author Display**: Display comment author information

#### 📊 View Tracking

- **IP-based Duplicate Prevention**: Prevent duplicate views
- **Real-time Aggregation**: Real-time view count updates

#### 📂 File Management

- **Multiple File Attachment**: Upload multiple files simultaneously
- **File Download**: Download and count attached files
- **Auto Cleanup**: Automatic deletion of related files when post is deleted

#### 📋 Board Directory

- **Card Layout**: Card-style list to view all boards at a glance
- **Responsive Design**: Clean grid system even on mobile
- **Auto Generation**: Automatically reflected in the list when board is added

---

## 👥 Member Management

### Member Registration and Authentication

#### 📋 Member Registration

- **Simple Registration Process**: Register with just ID and password
- **Real-time Duplicate Check**: Immediate check for ID duplication
- **Password Encryption**: Secure password storage using bcrypt
- **Input Validation**: Enhanced security with server-side input validation

#### 🔐 Login System

- **Session Management**: Secure session-based authentication
- **Auto Logout**: Automatic logout after 30 minutes of inactivity
- **Remember Me**: Option to maintain login status
- **Multiple Login Methods**: Normal login + social login

### Member Level System

#### 🏆 Level Management

- **10-level System**: Member level system from 1 to 10
- **Level-based Permissions**: Access control based on level
- **Auto Promotion**: Automatic level adjustment based on activity points (optional)
- **Manual Management**: Admin can directly adjust levels

#### 🚫 Member Sanctions

- **Block Function**: Block problematic members
- **Block Reason Recording**: Record block reason and date
- **Unblock**: Admin can unblock at any time
- **Auto Notification**: Display reason to blocked members

#### 👤 Member Profile

- **Activity History**: View written posts and comments
- **Point History**: Detailed record of earned/deducted points
- **Join Information**: Display join date and last login date

---

## 🔐 Social Login (OAuth)

MicroBoard provides seamless integration with major social platforms.

### Supported Platforms

#### 🔵 Google OAuth

- **Google Account Login**: One-click login with Google account
- **Auto Account Creation**: Automatically create member on first login
- **Profile Integration**: Automatically fetch Google profile information
- **Setup**: Configure in [Google Cloud Console](https://console.cloud.google.com/)

#### 🟢 LINE OAuth

- **LINE Account Login**: Login integrated with LINE app
- **Mobile Optimized**: Login flow optimized for mobile environment
- **Auto Account Creation**: Automatically create member on first login
- **Setup**: Configure in [LINE Developers](https://developers.line.biz/)

#### ⚫ Apple OAuth

- **Login with Apple**: Secure login using Apple ID
- **Privacy Protection**: Apple's strong privacy protection features
- **iOS Optimized**: Optimized experience on iOS/macOS devices
- **Setup**: Configure in [Apple Developer](https://developer.apple.com/)

### OAuth Features

- ✅ **Auto Button Display**: Automatically display login buttons when configured
- ✅ **Status Display**: Check OAuth configuration status in admin page
- ✅ **CSRF Protection**: Prevent CSRF attacks with State parameter
- ✅ **Error Handling**: Detailed error messages and logging
- ✅ **Multilingual Support**: Multilingual support in all OAuth flows

### Setup Method

1. Access admin page (`/admin/oauth.php`)
2. Enter Client ID and Secret for each platform
3. Select enable checkbox
4. Buttons will be automatically displayed after saving

---

## ⭐ Point System

A point system to encourage member activity.

### Point Accumulation

#### 📝 Post Writing

- **Auto Accumulation**: Automatically accumulate points when writing posts
- **Admin Setting**: Admin can set accumulation points
- **Differential Accumulation**: Different point settings per board (optional)

#### 💬 Comment Writing

- **Comment Points**: Accumulate points when writing comments (optional)
- **Encourage Activity**: Encourage active community participation

### Point Deduction

#### 🗑️ Post Deletion

- **Auto Deduction**: Automatically deduct points when post is deleted
- **Prevent Abuse**: Prevent point accumulation through repeated writing/deletion

### Point Management

#### 📊 Point History

- **Detailed Record**: Record all point accumulation/deduction history
- **Date-based View**: View point history by date
- **Reason Display**: Clearly display reason for point changes

#### ⚙️ Admin Settings

- **ON/OFF**: Enable/disable point system
- **Point Settings**: Set points for posts/comments
- **Manual Grant**: Admin can directly grant/deduct points

---

## 🔌 Plugin System

MicroBoard provides a powerful hook-based plugin system.

### Plugin Structure

#### 📁 Plugin Directory

```
/plugin
  /your_plugin_name
    - plugin.php (main plugin file)
    - config.json (plugin settings)
    - README.md (plugin description)
```

### Hook System

#### 🎯 Event Hooks

MicroBoard executes hooks at various times:

- `before_write`: Before writing post
- `after_write`: After writing post
- `before_delete`: Before deleting post
- `after_delete`: After deleting post
- `before_comment`: Before writing comment
- `after_comment`: After writing comment
- `before_login`: Before login
- `after_login`: After login

#### 📌 Plugin Registration

```php
// plugin.php example
add_event('after_write', function($post_data) {
    // Code to execute after writing post
    error_log("New post created: " . $post_data['title']);
}, 10); // Priority 10
```

### Plugin Features

#### ⚙️ Plugin Management

- **Auto Load**: Automatically recognize plugins in `/plugin` folder
- **Enable/Disable**: Turn plugins ON/OFF in admin page
- **Board-specific Application**: Apply plugins to specific boards only
- **Priority**: Control execution order of multiple plugins

#### 🛠️ Plugin Example

```php
<?php
/**
 * Plugin Name: Hello World
 * Description: Simple example plugin
 * Version: 1.0.0
 */

// Log after writing post
add_event('after_write', function($post_data) {
    $log = "[" . date('Y-m-d H:i:s') . "] ";
    $log .= "New post: " . $post_data['title'];
    file_put_contents(__DIR__ . '/log.txt', $log . PHP_EOL, FILE_APPEND);
});

// Notification when comment is written
add_event('after_comment', function($comment_data) {
    // Implement email notification, push notification, etc.
    notify_admin($comment_data);
});
?>
```

---

## 🌍 Multilingual Support

MicroBoard provides perfect multilingual support for global services.

### Supported Languages

#### 🇰🇷 Korean (Korean)

- Perfect Korean translation
- Korean date/time format
- Optimized for Korean services

#### 🇺🇸 English (English)

- Native-level English translation
- International standard date/time format
- Global service support

#### 🇯🇵 Japanese (Japanese)

- Natural Japanese translation
- Japanese honorific expressions
- Optimized for Japanese services

#### 🇨🇳 Chinese (Chinese)

- Accurate Chinese translation (simplified)
- Chinese expression style
- Chinese service support

### Multilingual Features

#### 🔄 Auto Language Detection

- **Browser Language**: Automatically detect user browser settings
- **IP-based**: Determine country by IP address (optional)
- **Cookie Storage**: Automatically save selected language

#### 🎛️ Language Switching

- **Language Selector**: Language selection menu on all pages
- **Instant Switch**: Change language without page refresh
- **URL Parameter**: Specify language in `?lang=ko` format

#### 📝 Translation Management

- **Language Files**: Language-specific PHP files in `/lang/` folder
- **Easy Addition**: Extend language by adding new language file
- **Override**: Customize specific phrases

---

## 👨‍💼 Admin Features

Provides powerful and intuitive admin pages.

### Dashboard

#### 📊 Statistics

- **Member Statistics**: Total members, new signups, active members
- **Post Statistics**: Total posts, daily posts
- **Visitor Statistics**: Daily visitors, page views
- **System Information**: Server status, disk usage

### Member Management

#### 👥 Member List

- **View All Members**: Sort by join date or name
- **Detailed Information**: Activity history and points for each member
- **Bulk Actions**: Manage multiple members simultaneously

#### 🔧 Member Control

- **Level Change**: Bulk or individual level changes
- **Block/Unblock**: Block and unblock problematic member accounts
- **Delete**: Complete deletion of member and related data
- **Point Management**: Manual point grant/deduction

### Board Management

#### 📋 Board Settings

- **Create Board**: Create new board
- **Permission Settings**: Set read/write permission levels
- **Skin Selection**: Apply different skins per board
- **List Count**: Set number of posts to display per page

#### 🎨 Skin Management

- **Skin List**: View list of installed skins
- **Preview**: Preview before applying skin
- **Settings**: Set detailed options per skin

### OAuth Management

#### 🔐 OAuth Settings

- **Google Settings**: Enter Client ID, Secret and enable
- **LINE Settings**: Manage Channel ID, Secret
- **Apple Settings**: Manage Team ID, Key ID, Private Key
- **Status Check**: Real-time check of OAuth integration status

### System Settings

#### ⚙️ Basic Settings

- **Site Title**: Site name and description
- **Admin Email**: Email for system notifications
- **Default Language**: Set site default language
- **Timezone**: Set server timezone

#### 💰 Point Settings

- **Point Usage**: Turn point system ON/OFF
- **Post Points**: Points awarded for writing posts
- **Comment Points**: Points awarded for writing comments
- **Login Points**: Daily login bonus points

### Policy Management

#### 📜 Policy Pages and Auto Installation

- **Auto Installation**: Automatically register terms of service and privacy policy in 4 languages (Korean/English/Japanese/Chinese) during installation
- **Auto Display**: Automatically display policy in user's language setting
- **Terms of Service**: Create/edit terms of service
- **Privacy Policy**: Manage privacy policy
- **WYSIWYG Editor**: Policy content editor
- **Version Management**: Keep policy change history

---

## 🔒 Security Features

MicroBoard protects your site with the latest security technologies.

### Authentication Security

#### 🔐 Password Security

- **bcrypt Encryption**: Industry standard password hashing
- **Salt**: Automatic salt generation to prevent rainbow table attacks
- **Rehashing**: Automatic rehashing when password is changed

#### ⏰ Session Security

- **Session Timeout**: Automatic logout after 30 minutes of inactivity
- **Session Regeneration**: Regenerate session ID on login
- **Secure Cookie**: Use Secure flag in HTTPS environment

### Input Security

#### 🛡️ SQL Injection Prevention

- **PDO Prepared Statements**: Use Prepared Statement in all queries
- **Input Validation**: Server-side validation of all user inputs
- **Type Check**: Strict validation of data types (number/string, etc.)

#### 🚫 XSS Prevention

- **htmlspecialchars**: Escape all HTML on output
- **DOM Purify**: Client-side XSS prevention
- **Content Security Policy**: Block script injection with CSP headers

#### 🔒 CSRF Prevention

- **CSRF Token**: Automatically generate CSRF token in all forms
- **Token Validation**: Validate token on form submission
- **SameSite Cookie**: Set SameSite attribute for cookies

### File Security

#### 📁 Upload Security

- **File Type Validation**: Whitelist-based file extension validation
- **File Size Limit**: Maximum upload size limit
- **Randomize Filename**: Automatically change uploaded filename
- **Execution Prevention**: Block script execution in upload directory

### Access Control

#### 🚪 Permission Management

- **Level-based Access**: Access control based on member level
- **Admin Authentication**: Two-factor authentication for admin pages
- **IP Whitelist**: Allow only specific IPs to access admin (optional)

#### 🔐 Two-Factor Authentication (2FA)

- **Email-based 2FA**: Enhanced account security with email-based two-factor authentication
- **Authenticator App Support**: Compatible with Google Authenticator, Authy, and other TOTP apps
- **QR Code Setup**: Easy setup via QR code scanning
- **Backup Codes**: One-time use backup codes for account recovery
- **Admin Control**: Enable/disable 2FA globally via email settings
- **User Control**: Users can enable/disable 2FA in their profile settings
- **Secure Login**: Additional security layer for user accounts

---

## 📦 Additional Features

### 📮 Email Notifications (Optional)

- Welcome email on member registration
- Comment notification email
- Password reset email
- Admin notification email

### 🔍 SEO Optimization

- Auto generation of meta tags
- Auto generation of sitemap
- Robots.txt management
- URL structure optimization

### 📊 Logging

- Error log recording
- Access log recording
- Admin action log
- Log retention and deletion policy

---

## 🚀 Performance Optimization

### ⚡ Caching

- Database query caching
- Page caching (optional)
- Static file caching

### 📉 Optimization

- Auto image compression
- CSS/JS compression and merging
- Lazy Loading
- CDN support

---

## 📱 Mobile Support

### 📲 Responsive Design

- **Perfect Responsive**: Optimized for all screen sizes
- **Touch Optimized**: Touch interface support
- **Mobile Menu**: Mobile-specific navigation
- **Fast Loading**: Optimized for mobile environment

---

## 🔄 Update and Migration

### 📥 Update

- **Auto Update Check**: Automatically detect new versions
- **One-click Update**: Update with one button
- **Auto Backup**: Automatic backup before update

### 🔄 Migration

- **Database Migration**: Auto update DB schema per version
- **Setting Preservation**: Preserve existing settings and data
- **Rollback Support**: Restore to previous version if issues occur

---

## 📞 Support and Community

### 💬 Support

- **GitHub Issues**: Bug reports and feature requests
- **Documentation**: Detailed online documentation
- **FAQ**: Frequently asked questions

### 🤝 Community

- **GitHub Discussions**: Community discussions
- **Contribution Guide**: Open source contribution methods
- **Plugin Sharing**: Community plugins

---

## 📄 License

MicroBoard is distributed under the [MIT License](LICENSE).

---

**MicroBoard v1.0.0** | [Documentation](https://mytajimilife-coder.github.io/microboard/) | [GitHub](https://github.com/mytajimilife-coder/microboard)
