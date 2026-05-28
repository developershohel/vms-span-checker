# VMS Span Checker

**Ultimate Spam Protection & Email Validation for WordPress**

Protect your WordPress forms from spam submissions, fake emails, and malicious domains with advanced validation techniques and AI-powered detection.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration Guide](#configuration-guide)
  - [Dashboard](#dashboard)
  - [Form Guard](#form-guard)
  - [Whitelist Domains](#whitelist-domains)
  - [Disposable Domains](#disposable-domains)
  - [API Settings](#api-settings)
  - [AI Span Settings](#ai-span-settings)
  - [Guard Types](#guard-types)
- [Supported Forms](#supported-forms)
- [API Integrations](#api-integrations)
- [Hooks & Filters](#hooks--filters)
- [FAQ](#faq)
- [Changelog](#changelog)
- [Support](#support)

---

## Features

### Core Protection
- **Disposable Email Detection** - Block 10,000+ temporary/disposable email domains
- **Email Domain Validation** - Verify email domains have valid MX records and HTTPS
- **Real-time Validation** - Instant feedback before form submission
- **Custom Whitelist** - Always allow trusted domains
- **Custom Blocklist** - Add your own blocked domains

### Security APIs
- **Google Web Risk API** - Enterprise-grade malware and phishing detection
- **VirusTotal Integration** - Multi-engine domain scanning (supports multiple API keys)
- **Google reCAPTCHA** - v2 and v3 support for bot protection

### AI-Powered Features
- **AI Spam Detection** - Intelligent comment analysis using LLM providers
- **AI Post Summaries** - Auto-generate summaries for posts
- **AI Product Summaries** - Auto-generate WooCommerce product summaries
- **Multiple AI Providers** - OpenAI, Anthropic (Claude), Google Gemini, DeepSeek

### Form Protection Guards
- **Contact Guard** - Protect contact forms
- **Subscribe Guard** - Protect newsletter subscription forms
- **Registration Guard** - Protect WordPress registration
- **Login Guard** - Protect login forms
- **Comment Guard** - Protect comment forms with spam rules
- **Product Review Guard** - Protect WooCommerce product reviews

### Additional Features
- **Activity Dashboard** - Monitor all validation events
- **Detailed Logging** - Track blocked attempts with reasons
- **Blocked Users Management** - View and manage blocked users
- **Custom Error Messages** - Customize validation error messages
- **Email Templates** - Customizable notification emails
- **Translation Ready** - Full i18n support

---

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

---

## Installation

### From WordPress Admin

1. Go to **Plugins > Add New**
2. Search for "VMS Span Checker"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

### From Source

```bash
cd wp-content/plugins/
git clone https://github.com/yourusername/vms-span-checker.git
```

Then activate from WordPress admin.

---

## Quick Start

1. **Activate the plugin** - Go to Plugins and activate VMS Span Checker
2. **Navigate to VMS Span Checker** - Find it in your admin menu (shield icon)
3. **Add a Form Guard mapping** - Go to Form Guard and click "Add form guard mapping"
4. **Configure your form** - Select form type, target pages, and validation options
5. **Test your form** - Submit a test with a disposable email to verify protection

---

## Configuration Guide

### Dashboard

The dashboard provides an overview of your spam protection:

- **Total Validations** - Number of email validations performed
- **Blocked Attempts** - Number of blocked spam submissions
- **Pass Rate** - Percentage of legitimate submissions
- **AI Blocked** - Comments blocked by AI analysis
- **Quick Links** - Fast access to all settings
- **Top Failed Domains** - Most frequently blocked domains
- **Recent Activity** - Latest validation events

### Form Guard

Form Guard is the main feature for protecting your forms.

#### Adding a Form Mapping

1. Go to **VMS Span Checker > Form Guard**
2. Click **Add form guard mapping**
3. Configure the following:

| Field | Description |
|-------|-------------|
| **Form Type** | Login, Register, Contact, Comment, Newsletter, or Custom |
| **Where to Run** | Select pages/posts where this mapping applies |
| **Form Selector** | CSS selector to identify the form (e.g., `.wpcf7-form`, `#contact-form`) |
| **Submit Selector** | (Optional) CSS selector for submit button |
| **Auto Validation** | Automatically detect email/URL fields |

#### Form Selector Examples

```css
/* By ID */
#my-contact-form

/* By class */
.wpcf7-form

/* Descendant selector */
.tnp-subscription form

/* Complex selector */
#content .entry-content form.contact
```

#### Target Locations

- **Entire site** - Runs on all pages (requires Form Selector)
- **All pages** - All WordPress pages
- **All posts** - All WordPress posts
- **All singular** - All single post/page views
- **Specific pages** - Select individual pages
- **Specific posts** - Select individual posts

#### Validation Options

| Option | Description |
|--------|-------------|
| **Check Disposable** | Block disposable/temporary email domains |
| **Require HTTPS** | Only allow emails from domains with HTTPS |
| **Google Web Risk** | Scan domains for malware/phishing |
| **VirusTotal** | Multi-engine domain scanning |
| **reCAPTCHA** | Add Google reCAPTCHA protection |

### Whitelist Domains

Always allow submissions from trusted domains.

1. Go to **VMS Span Checker > Whitelist Domains**
2. Add domains one per line:

```
gmail.com
yahoo.com
outlook.com
yourcompany.com
```

**Note:** Whitelisted domains bypass all checks including disposable detection and API scans.

### Disposable Domains

Block known disposable/temporary email providers.

1. Go to **VMS Span Checker > Disposable Domains**
2. The plugin includes 10,000+ pre-loaded disposable domains
3. Add custom domains to block:

```
tempmail.com
throwaway.email
fakeinbox.com
```

### API Settings

Configure third-party security APIs for enhanced protection.

#### Google Web Risk API

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable the **Web Risk API**
4. Create an API key
5. Enter the key in **VMS Span Checker > API Settings > Google Web Risk**

#### VirusTotal API

1. Create a free account at [VirusTotal](https://www.virustotal.com/)
2. Go to your profile and copy your API key
3. Add the key in **VMS Span Checker > API Settings > VirusTotal**
4. Configure thresholds:
   - **Max Malicious** - Block if malicious detections exceed this (default: 0)
   - **Max Suspicious** - Block if suspicious detections exceed this (optional)

**Tip:** Add multiple VirusTotal API keys for higher rate limits.

#### Google reCAPTCHA

1. Register at [Google reCAPTCHA](https://www.google.com/recaptcha/admin)
2. Choose v2 ("I'm not a robot") or v3 (invisible)
3. Add your domain
4. Copy Site Key and Secret Key
5. Enter in **VMS Span Checker > API Settings > reCAPTCHA**

### AI Span Settings

Configure AI-powered spam detection and content generation.

#### Supported Providers

| Provider | Models |
|----------|--------|
| **OpenAI** | GPT-4o, GPT-4o-mini, GPT-4-turbo, GPT-3.5-turbo |
| **Anthropic** | Claude 3.5 Sonnet, Claude 3 Opus, Claude 3 Haiku |
| **Google Gemini** | Gemini 1.5 Pro, Gemini 1.5 Flash |
| **DeepSeek** | DeepSeek Chat, DeepSeek Coder |

#### Setup

1. Go to **VMS Span Checker > AI Span Settings**
2. Enable AI VMS Span Checker
3. Select your preferred provider
4. Enter your API key
5. Choose the model
6. Select post types for AI summaries

#### AI Features

- **Comment Moderation** - AI analyzes comments against post content to detect spam
- **Post Summaries** - Generate summaries displayed before comments
- **Product Summaries** - Generate WooCommerce product summaries

### Guard Types

#### Registration Guard

Protect WordPress user registration.

1. Go to **VMS Span Checker > Registration Guard**
2. Enable the guard
3. Configure validation options
4. Customize error messages

#### Login Guard

Add validation to WordPress login.

1. Go to **VMS Span Checker > Login Guard**
2. Enable protection
3. Configure rate limiting (optional)

#### Contact Guard

Protect contact forms with frontend validation.

1. Go to **VMS Span Checker > Contact Guard**
2. Enable the guard
3. Configure which forms to protect
4. Set validation rules

#### Subscribe Guard

Protect newsletter subscription forms.

1. Go to **VMS Span Checker > Subscribe Guard**
2. Enable protection
3. Map to your subscription forms
4. Compatible with Newsletter Plugin, Mailchimp, etc.

#### Comment Guard

Advanced comment spam protection.

1. Go to **VMS Span Checker > Comment Guard**
2. Enable spam rules
3. Configure:
   - Minimum comment length
   - Maximum links allowed
   - Blocked phrases
   - Blocked patterns (regex)

#### Product Review Guard

Protect WooCommerce product reviews.

1. Go to **VMS Span Checker > Product Review Guard**
2. Enable protection
3. Configure validation for review forms

---

## Supported Forms

VMS Span Checker works with any HTML form. Pre-tested compatibility:

| Plugin/Form | Compatibility |
|-------------|---------------|
| Contact Form 7 | ✅ Full |
| WPForms | ✅ Full |
| Gravity Forms | ✅ Full |
| Ninja Forms | ✅ Full |
| Formidable Forms | ✅ Full |
| Newsletter Plugin | ✅ Full |
| Mailchimp Forms | ✅ Full |
| WooCommerce | ✅ Full |
| WordPress Registration | ✅ Built-in |
| WordPress Comments | ✅ Built-in |
| Custom HTML Forms | ✅ Full |

---

## API Integrations

### Rate Limits

| Service | Free Tier | Notes |
|---------|-----------|-------|
| Google Web Risk | 100,000/month | Requires billing enabled |
| VirusTotal | 500/day per key | Add multiple keys |
| OpenAI | Pay-per-use | ~$0.01 per validation |
| Anthropic | Pay-per-use | ~$0.01 per validation |
| Google Gemini | 60/minute free | Higher limits available |
| DeepSeek | Pay-per-use | Very affordable |

### API Response Caching

Responses are cached to reduce API calls:
- Domain validation: 24 hours
- VirusTotal results: 24 hours
- AI summaries: Until post is updated

---

## Hooks & Filters

### Filters

```php
// Modify disposable domain list
add_filter('wsc_disposable_domains', function($domains) {
    $domains[] = 'custom-spam-domain.com';
    return $domains;
});

// Modify whitelist domains
add_filter('wsc_whitelist_domains', function($domains) {
    $domains[] = 'trusted-partner.com';
    return $domains;
});

// Custom validation logic
add_filter('wsc_validate_email', function($is_valid, $email, $domain) {
    // Your custom logic
    return $is_valid;
}, 10, 3);

// Modify error messages
add_filter('wsc_error_messages', function($messages) {
    $messages['disposable'] = 'Please use a permanent email address.';
    return $messages;
});
```

### Actions

```php
// After successful validation
add_action('wsc_validation_passed', function($email, $form_data) {
    // Log or process successful validation
}, 10, 2);

// After blocked validation
add_action('wsc_validation_blocked', function($email, $reason, $form_data) {
    // Log or alert on blocked attempt
}, 10, 3);

// After AI spam detection
add_action('wsc_ai_spam_detected', function($comment_id, $score, $reason) {
    // Handle AI-detected spam
}, 10, 3);
```

---

## FAQ

### Does this work without API keys?

Yes! Basic disposable email detection and domain validation work without any API keys. API integrations (Google Web Risk, VirusTotal, AI) are optional enhancements.

### Will it slow down my forms?

Validation is performed via AJAX before form submission. Most checks complete in under 500ms. API calls are cached to minimize latency.

### Is it GDPR compliant?

Yes. The plugin only processes email domains, not full email addresses. No personal data is stored externally. AI providers process data per their privacy policies.

### Can I use multiple AI providers?

Only one provider is active at a time, but you can save credentials for multiple providers and switch between them.

### How do I protect a custom form?

1. Go to Form Guard
2. Add a new mapping
3. Set Form Type to "Custom"
4. Enter your form's CSS selector
5. Configure validation options

### Why is a legitimate email being blocked?

Check these in order:
1. Is the domain on the disposable list? → Add to whitelist
2. Is VirusTotal flagging it? → Adjust thresholds
3. Is HTTPS check failing? → Disable HTTPS requirement

### How do I add a domain to the blocklist?

Go to **Disposable Domains** and add the domain to the custom list.

---

## Changelog

### 1.0.0
- Initial release
- Email domain validation
- Disposable email detection (10,000+ domains)
- Google Web Risk API integration
- VirusTotal API integration
- Google reCAPTCHA support (v2 & v3)
- AI spam detection (OpenAI, Anthropic, Gemini, DeepSeek)
- AI post and product summaries
- Form Guard with flexible mapping
- Registration, Login, Contact, Subscribe, Comment Guards
- Product Review Guard for WooCommerce
- Activity dashboard with analytics
- Whitelist and blocklist management
- Custom error messages
- Email templates
- Full translation support

---

## Support

- **Documentation:** [https://vmselements.com](https://vmselements.com)
- **Support Forum:** [WordPress.org Support](https://wordpress.org/support/plugin/vms-span-checker/)
- **Email:** support@vmselements.com
- **Pro:** [VMS Span Checker Pro](https://vmselements.com/product/vms-span-checker-pro)

---

## License

VMS Span Checker is licensed under the GPL-2.0-or-later license. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

## Credits

Developed by [VMS Elements](https://vmselements.com)

**Third-party Services:**
- [Google Web Risk API](https://cloud.google.com/web-risk)
- [VirusTotal API](https://www.virustotal.com/)
- [OpenAI API](https://openai.com/)
- [Anthropic API](https://www.anthropic.com/)
- [Google Gemini API](https://ai.google.dev/)
- [DeepSeek API](https://www.deepseek.com/)
