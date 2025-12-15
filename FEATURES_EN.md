# MicroBoard Features Guide

> **Version:** 1.0.0  
> **Last Updated:** 2025-12-15

## 📋 Table of Contents

1. [Core Features](#core-features)
2. [Board Management](#board-management)
3. [Permission System](#permission-system)
4. [Search Features](#search-features)
5. [SEO & Analytics Tools](#seo--analytics-tools)
6. [Member Management](#member-management)
7. [Security Features](#security-features)
8. [Multilingual Support](#multilingual-support)

---

## Core Features

### 🎨 Theme & Design

- **Dark Mode Support**: Automatic light/dark theme switching
- **Responsive Design**: Perfect support for mobile, tablet, and desktop
- **Custom Background**: Color, gradient, and image background settings
- **Logo & Favicon**: Site branding customization

### 📝 Board System

- **Unlimited Boards**: Create as many boards as you want
- **Skin System**: Independent skin application per board
- **Editor Settings**: Choose WYSIWYG editor or plain text per board
- **Comment System**: Enable/disable comments per board
- **File Attachments**: Multiple file upload support

---

## Board Management

### Board Creation and Settings

In Admin Page → Board Management, you can configure the following:

#### Basic Settings
- **Board Name**: Set board title
- **Table Name**: Use only English, numbers, and underscores
- **Administrator**: Assign board administrator
- **List Count**: Number of posts per page
- **Skin**: Choose default or modern

#### Feature Settings
- ✅ **Use Comments**: Enable comment functionality
- ✅ **Use Editor**: Summernote WYSIWYG editor
- ✅ **Include in Search**: Include in integrated search results

#### Permission Settings
- **List Permission**: Level 0~10 (0: Including guests, 1: Members only, 10: Admin only)
- **Read Permission**: Level 1~10
- **Write Permission**: Level 1~10

#### Plugin Settings
- Select plugins to activate per board

---

## Permission System

### Member Level System

MicroBoard uses a 10-tier level system:

| Level | Description | Default Permissions |
|-------|-------------|---------------------|
| 0 | Guest | List view only (when set) |
| 1 | Regular Member | Basic read/write |
| 2-9 | Tiered Members | Admin-defined permissions |
| 10 | Administrator | All permissions |

### Board-Level Permission Settings

Each board can have independent permission settings:

#### 1. List Permission
- Minimum level to view board list
- Level 0: Everyone including guests
- Level 1: Logged-in members only
- Level 10: Admin only

#### 2. Read Permission
- Minimum level to read post content
- Level 1~10 configurable

#### 3. Write Permission
- Minimum level to create posts
- Level 1~10 configurable

### Permission Examples

**Public Board (Everyone can view)**
- List: Level 0 (Including guests)
- Read: Level 1 (Members only)
- Write: Level 1 (Members only)

**Members-Only Board**
- List: Level 1
- Read: Level 1
- Write: Level 1

**VIP Board**
- List: Level 5
- Read: Level 5
- Write: Level 5

**Admin-Only Board**
- List: Level 10
- Read: Level 10
- Write: Level 10

---

## Search Features

### Integrated Search

Search across all boards simultaneously.

#### Features
- 🔍 Search all boards at once
- 📌 Search both titles and content
- 🎨 Keyword highlighting
- 📄 Pagination support (20 per page)
- 🏷️ Board tags display

#### How to Use
1. Click "🔍 Integrated Search" in header menu
2. Enter search term
3. Click search button or press Enter

#### Exclude from Search
Administrators can exclude specific boards from integrated search:
- Admin Page → Board Management → Edit Board
- Uncheck "Include in Search" checkbox

### Board Search

Search within individual boards.

#### Search Options
- **Title**: Search in post titles only
- **Content**: Search in post content only
- **Author**: Search by author name
- **All** (default): Title + Content combined search

#### How to Use
1. Use search form at top of board list page
2. Select search field (Title/Content/Author)
3. Enter search term and click 🔍 button

---

## SEO & Analytics Tools

In Admin Page → SEO Settings, you can easily configure various SEO and analytics tools.

### Bing Webmaster Tools
- Register and verify with Bing search engine
- Enter only meta tag content value

### Google Search Console
- Register and verify with Google search engine
- Enter only meta tag content value

### Google Analytics (GA4)
- Visitor statistics and analysis
- Enter Measurement ID (G-XXXXXXXXXX)
- Automatically inserts tracking script

### Google Tag Manager
- Tag management system
- Enter Container ID (GTM-XXXXXXX)
- Automatically inserts in both Head and Body

### Google AdSense
- Ad monetization
- Enter Client ID (ca-pub-XXXXXXXXXXXXXXXX)
- Automatically inserts AdSense script

### Custom Scripts

#### Header Scripts
- Scripts/meta tags to add in `<head>` tag
- Additional SEO tags, fonts, CSS, etc.

#### Footer Scripts
- Scripts to add just before `</body>` tag
- Chat widgets, analytics tools, etc.

---

## Member Management

### Member Level System

#### Level Management
- Change levels in Admin Page → Member Management
- Levels 1~10 configurable
- Admin automatically gets level 10

#### Member Status Management
- **Active**: Normal activity allowed
- **Blocked**: Login disabled, block reason displayed
- **Withdrawn**: Member withdrawal processed

### Point System

#### Point Settings
- Admin Page → Configuration → Point Settings
- Enable/disable point system
- Set points awarded for writing posts (negative values allowed)

#### Point Distribution
- Writing posts: Award configured points
- Deleting posts: Deduct awarded points

### OAuth Social Login

#### Supported Platforms
- Google
- LINE
- Apple

#### Configuration
- Admin Page → OAuth Settings
- Enter Client ID and Secret for each platform
- Select enable checkbox

---

## Security Features

### CSRF Protection
- Automatic CSRF token generation in all forms
- Request rejection on token validation failure

### XSS Prevention
- Automatic user input escaping
- Dangerous tag removal when HTML allowed
- Event handler removal

### SQL Injection Prevention
- PDO Prepared Statements usage
- All query parameters bound

### File Upload Security
- MIME type validation
- Filename randomization
- Only allowed extensions uploadable

### Two-Factor Authentication (2FA)
- Email-based 2FA
- Authenticator app support (Google Authenticator, etc.)
- QR code setup
- Backup codes provided

---

## Multilingual Support

### Supported Languages
- 🇰🇷 Korean
- 🇺🇸 English
- 🇯🇵 Japanese
- 🇨🇳 Chinese

### Auto Language Detection
- Browser language auto-detection
- User-selected language saved in session

### Language Switching
- Click flag icon in header
- Instant switch without page reload

### Language File Location
```
lang/
├── ko.php  # Korean
├── en.php  # English
├── ja.php  # Japanese
└── zh.php  # Chinese
```

---

## Plugin System

### Plugin Structure

```
plugin/
└── example_plugin/
    ├── index.php       # Plugin main file
    └── config.json     # Plugin configuration (optional)
```

### Available Hooks

- `before_write`: Before writing post
- `after_write`: After writing post
- `before_login`: Before login
- `after_login`: After login
- `board_head`: Board header
- `before_logo_display`: Before logo display

### Plugin Example

```php
<?php
// plugin/example_plugin/index.php

// Email notification after writing post
add_event('after_write', function($post_data) {
    $to = 'admin@example.com';
    $subject = 'New post: ' . $post_data['title'];
    $message = $post_data['writer'] . ' wrote a post.';
    mail($to, $subject, $message);
});

// Award points after login
add_event('after_login', function($user_data) {
    insert_point($user_data['mb_id'], 10, 'Login bonus');
});
?>
```

---

## Database Updates

To use new features, run these scripts:

### Permission System
```
http://your-domain/update_db_permissions.php
```

### Editor Settings
```
http://your-domain/update_db_editor.php
```

### Integrated Search
```
http://your-domain/update_db_search.php
```

### SEO Settings
```
http://your-domain/update_db_seo.php
```

---

## Troubleshooting

### Permission Errors
- Schema automatically updates when accessing board management page
- Or run update script directly

### No Search Results
- Check "Include in Search" in board settings
- Verify board table was created properly

### SEO Tags Not Displaying
- Verify values entered in SEO settings page
- Check tag insertion in page source

---

## Additional Resources

- [GitHub Repository](https://github.com/mytajimilife-coder/microboard)
- [GitHub Pages Documentation](https://mytajimilife-coder.github.io/microboard/)
- [Issue Reports](https://github.com/mytajimilife-coder/microboard/issues)

---

**MicroBoard v1.0.0** - Made with ❤️
