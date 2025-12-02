# OAuth Social Login Setup Guide

MicroBoard supports social login with Google, LINE, and Apple.

## Setup Instructions

### 1. Database Update

To add OAuth functionality to an existing installation, run the following file:
```
http://your-domain.com/update_db_oauth.php
```

### 2. Configuration in Admin Panel

1. Login as Administrator.
2. Go to **Admin Panel** → **OAuth Settings**.
3. Enter API keys for each provider and enable them.

**Important**: Social login buttons will only appear when ALL of the following conditions are met:
- ✅ Client ID is entered
- ✅ Client Secret is entered
- ✅ "Enable" checkbox is checked

You can check the configuration status of each provider in the admin panel:
- **✓ Configured** (Green): All settings are complete and enabled.
- **⚠ Not Configured** (Red): Settings are incomplete or disabled.

## Google OAuth Setup

### 1. Google Cloud Console Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project or select an existing one.
3. Navigate to "APIs & Services" → "Credentials".
4. Click "Create Credentials" → Select "OAuth client ID".
5. Application type: Select "Web application".
6. Add Authorized redirect URI:
   ```
   http://your-domain.com/oauth_callback.php
   ```
7. Copy the Client ID and Client Secret.

### 2. MicroBoard Setup
- Client ID: Enter the Client ID received from Google.
- Client Secret: Enter the Client Secret received from Google.
- Check "Enable".

## LINE Login Setup

### 1. LINE Developers Console Setup
1. Go to [LINE Developers Console](https://developers.line.biz/console/).
2. Create a new Provider (if you don't have one).
3. Create a new Channel (Channel type: LINE Login).
4. Configure in "LINE Login" tab:
   - Add Callback URL:
     ```
     http://your-domain.com/oauth_callback.php
     ```
5. Check Channel ID and Channel Secret in the "Basic settings" tab.

### 2. MicroBoard Setup
- Client ID: Enter LINE Channel ID.
- Client Secret: Enter LINE Channel Secret.
- Check "Enable".

## Apple Sign In Setup

### 1. Apple Developer Setup
1. Go to [Apple Developer](https://developer.apple.com/account/).
2. Navigate to "Certificates, Identifiers & Profiles".
3. Click "Identifiers" → "+" button.
4. Select "App IDs" and enable "Sign in with Apple".
5. Create "Services IDs":
   - Enter Identifier (e.g., com.yourcompany.microboard).
   - Enable "Sign in with Apple".
   - Configure Return URLs:
     ```
     http://your-domain.com/oauth_callback.php
     ```
6. Create "Keys":
   - Enable "Sign in with Apple".
   - Download Key ID and Private Key.

### 2. MicroBoard Setup
- Client ID: Enter Apple Service ID.
- Client Secret: Enter Apple Team ID.
- **Note**: Apple requires additional configuration (Key ID, Private Key, etc.).

## Callback URL

You must register the following callback URL with all OAuth providers:
```
http://your-domain.com/oauth_callback.php
```

If using HTTPS:
```
https://your-domain.com/oauth_callback.php
```

## Security Considerations

1. **HTTPS Recommended**: You must use HTTPS in a production environment.
2. **Protect Secrets**: Never expose your Client Secrets.
3. **Regular Key Rotation**: Rotate your API keys regularly for security.

## Troubleshooting

### Login buttons are not visible
- Check if the provider is enabled in the admin panel.
- Verify that both Client ID and Client Secret are entered correctly.

### "Invalid redirect URI" error
- Verify that the callback URL is correctly registered in the OAuth provider's console.
- Check if the HTTP/HTTPS protocol matches.

### Unable to retrieve user information
- Verify that the API keys are correct.
- Check the API usage limits of the OAuth provider.

## Technical Details

### Database Tables

**mb1_oauth_config**: Stores OAuth provider settings
- provider: Provider name (google, line, apple)
- client_id: Client ID
- client_secret: Client Secret
- enabled: Enable status

**mb1_oauth_users**: Stores OAuth user linkage information
- mb_id: MicroBoard User ID
- provider: OAuth Provider
- provider_user_id: User ID from the provider
- created_at: Linkage creation time

### File Structure

- `inc/oauth.php`: OAuth helper functions
- `oauth_callback.php`: OAuth callback handler
- `admin/oauth.php`: OAuth settings management page
- `update_db_oauth.php`: Database migration script
