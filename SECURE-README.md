# 🔒 SecureShare - Protected File Sharing System

A highly secure temporary file sharing system with **screenshot protection**, **download prevention**, and **automatic expiration**. Files open in a protected web viewer instead of downloading.

## ✨ Key Features

### 🛡️ Security Features
- ✅ **Screenshot Protection** - Blocks PrintScreen, screen recording, and capture tools
- ✅ **Download Prevention** - Files display on website, cannot be saved
- ✅ **Right-Click Disabled** - No context menu access
- ✅ **Copy Protection** - Text selection and clipboard disabled
- ✅ **Dynamic Watermarks** - Moving watermarks on content
- ✅ **DevTools Detection** - Blurs content when dev tools open
- ✅ **Tab Switch Protection** - Blurs content when tab loses focus
- ✅ **Keyboard Shortcuts Blocked** - Ctrl+S, Ctrl+C, Ctrl+P disabled

### 📁 File Management
- ✅ Auto-delete after custom time (1 min to 24 hours)
- ✅ Unique shareable links for YOUR domain
- ✅ View counter tracking
- ✅ Automatic cleanup of expired files
- ✅ Support for PDFs, Images, Documents

### 🎨 User Interface
- ✅ Beautiful modern dark theme
- ✅ Drag & drop upload
- ✅ Real-time progress bar
- ✅ Mobile responsive
- ✅ Protection status indicators

## 📋 Requirements

- PHP 7.0 or higher
- Apache web server
- At least 100MB free disk space
- SSL certificate (HTTPS) recommended for security

## 🚀 Installation

### Step 1: Upload Files to Your Domain

Upload these files to your web hosting:
```
index.html          (renamed from secure-index.html)
upload.php          (renamed from secure-upload.php)
view.php            (renamed from secure-view.php)
.htaccess
```

### Step 2: File Placement

```
your-domain.com/
├── index.html       # Main upload page
├── upload.php       # Upload handler
├── view.php         # Secure viewer
├── .htaccess        # Security config
├── uploads/         # Auto-created
└── data/            # Auto-created
```

### Step 3: Set Permissions

```bash
chmod 755 uploads/
chmod 755 data/
chmod 644 index.html
chmod 644 upload.php
chmod 644 view.php
chmod 644 .htaccess
```

### Step 4: Access Your Site

Go to: `https://yourdomain.com/`

## 🎯 How It Works

### For Uploaders:
1. **Upload File** - Drag & drop or browse
2. **Select Expiration** - Choose auto-delete time
3. **Enable Protections** - Screenshot block, watermark, etc.
4. **Get Link** - Share your domain link: `yourdomain.com/view.php?id=abc123`

### For Recipients:
1. **Click Link** - Opens protected viewer
2. **View Content** - File displays in browser
3. **Cannot Download** - Save/screenshot blocked
4. **Auto-Expires** - File deletes after set time

## 🔐 Protection Levels

### Level 1: Basic Protection
- Disable right-click
- Prevent download button
- Show watermark

### Level 2: Advanced Protection (Default)
- All Level 1 features
- Block screenshots (PrintScreen)
- Disable keyboard shortcuts
- Block text selection/copy
- Moving watermarks

### Level 3: Maximum Protection
- All Level 2 features
- DevTools detection with blur
- Tab switch protection
- Clipboard clearing
- Browser focus monitoring

## 📱 Supported File Types

### Fully Protected:
- **PDF** - Opens in secure iframe viewer
- **Images** - JPG, PNG, GIF with right-click disabled
- **Text** - TXT files in protected viewer

### Partially Protected:
- **Documents** - DOC, DOCX (content protection message shown)

## ⚙️ Configuration

### Change Maximum File Size
Edit `upload.php`:
```php
define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 200MB
```

Edit `.htaccess`:
```apache
php_value upload_max_filesize 200M
php_value post_max_size 200M
```

### Add More Expiration Times
Edit `index.html`:
```html
<option value="7200">2 hours</option>
<option value="172800">48 hours</option>
```

### Customize Protection Settings
Edit `view.php` to adjust:
- Watermark frequency
- Blur intensity
- Protection messages
- View counter display

## 🎨 Customization

### Change Color Theme
Edit CSS variables in `index.html`:
```css
:root {
    --bg-primary: #0a0e27;      /* Dark blue background */
    --accent-primary: #00ff88;   /* Green accent */
    --accent-secondary: #00d4ff; /* Cyan accent */
}
```

### Custom Watermark Text
Edit `view.php` line with watermark div:
```php
<div class="watermark">YOUR TEXT</div>
```

## 🔍 How Links Work

### Generated Link Format:
```
https://yourdomain.com/view.php?id=a1b2c3d4e5f6g7h8
```

- **Unique ID** - 32-character random hex
- **One-time use** - Link works until expiration
- **Secure** - Cannot be guessed or enumerated
- **Trackable** - View counter increments

## 🛡️ Security Best Practices

### 1. Use HTTPS
Always use SSL certificate for secure transmission

### 2. Regular Cleanup
Set up cron job for automatic cleanup:
```bash
0 */6 * * * php /path/to/upload.php > /dev/null 2>&1
```

### 3. Monitor Uploads
Check `data/files.json` regularly for activity

### 4. Limit File Types
Edit `upload.php` to restrict certain extensions

### 5. Set Max Views
Add view limit feature to auto-delete after X views

## ⚠️ Protection Limitations

### What This CAN Protect Against:
✅ Casual screenshot attempts
✅ Right-click save
✅ Browser download features
✅ Keyboard shortcuts
✅ Text selection/copy

### What This CANNOT Protect Against:
❌ Phone camera photos of screen
❌ External screen capture hardware
❌ Determined users with advanced tools
❌ Browser extensions that bypass protections

**Note**: This provides STRONG deterrence but not 100% foolproof protection. Use for confidential but not highly sensitive materials.

## 🐛 Troubleshooting

### Files Won't Upload
- Check `uploads/` and `data/` exist and are writable
- Verify PHP max upload size
- Check server disk space

### Screenshot Protection Not Working
- Some browsers may not support all features
- Mobile browsers have limited protection
- Try different browsers for testing

### PDF Not Displaying
- Check PHP base64 encoding is working
- Verify PDF is not corrupted
- Check browser PDF support

### Watermark Not Showing
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify watermark option was checked on upload

## 📊 Monitoring & Analytics

### View File Data
Check `data/files.json`:
```json
{
  "abc123": {
    "filename": "document.pdf",
    "views": 5,
    "uploadTime": 1234567890,
    "expiryTime": 1234567890
  }
}
```

### Track Statistics
- Total uploads
- View counts
- Popular file types
- Average expiration times

## 🔄 Maintenance

### Automatic Cleanup
Expired files are auto-deleted when:
- New file is uploaded
- Expired file is accessed

### Manual Cleanup
Delete old files manually:
```bash
php cleanup.php  # (create this script if needed)
```

## 🚀 Advanced Features (Future)

Ideas for enhancement:
- [ ] Password protection for links
- [ ] Email notifications on view
- [ ] Download limit (delete after X views)
- [ ] QR code generation
- [ ] Admin dashboard
- [ ] API access
- [ ] Bulk upload
- [ ] Custom expiry dates/times
- [ ] File encryption at rest
- [ ] Two-factor authentication

## 📜 License

Free to use and modify for personal and commercial projects.

## 🆘 Support

### Common Issues:

**Q: Can users still screenshot on mobile?**
A: Mobile screenshot protection is limited. Phone cameras can still capture screens.

**Q: Does this work on all browsers?**
A: Best on Chrome/Firefox. Safari and mobile browsers have varying protection levels.

**Q: Can I use this for highly sensitive data?**
A: This adds strong protection but isn't foolproof. Consider encryption for highly sensitive data.

**Q: How do I know if someone screenshotted?**
A: There's no reliable way to detect screenshots, only to deter them.

## ⚖️ Legal Disclaimer

This tool provides technical protection measures but does not guarantee 100% prevention. Users are responsible for complying with applicable laws and regulations regarding data protection and privacy.

---

## 🎯 Quick Start Commands

```bash
# 1. Upload files to your domain
# 2. Set permissions
chmod 755 uploads/ data/
chmod 644 *.html *.php .htaccess

# 3. Test upload
# Visit: https://yourdomain.com/

# 4. Upload a test file
# 5. Click the generated link
# 6. Try to screenshot (should be blocked!)
```

---

Made with 🔒 for secure file sharing

**Your Domain • Your Control • Your Security**
