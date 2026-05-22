=== VMS Span Checker ===
Contributors: developershohel
Donate link: https://vmsuniverse.com/donate
Tags: spam, email, disposable, anti-spam, validation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect your WordPress forms from spam, fake emails, and malicious domains with advanced validation and AI-powered detection.

== Description ==

**VMS Span Checker** is a comprehensive spam protection plugin that validates email domains, blocks disposable emails, and protects your forms from malicious submissions using multiple security layers.

= Key Features =

**Core Protection**

* **Disposable Email Detection** - Block 10,000+ temporary/disposable email domains automatically
* **Email Domain Validation** - Verify email domains have valid MX records and HTTPS
* **Real-time Validation** - Instant feedback before form submission
* **Custom Whitelist** - Always allow trusted domains
* **Custom Blocklist** - Add your own blocked domains

**Security API Integrations**

* **Google Web Risk API** - Enterprise-grade malware and phishing detection
* **VirusTotal Integration** - Multi-engine domain scanning with support for multiple API keys
* **Google reCAPTCHA** - Both v2 and v3 support for bot protection

**AI-Powered Features**

* **AI Spam Detection** - Intelligent comment analysis using leading AI providers
* **AI Post Summaries** - Auto-generate summaries for blog posts
* **AI Product Summaries** - Auto-generate WooCommerce product summaries
* **Multiple AI Providers** - OpenAI (GPT-4), Anthropic (Claude), Google Gemini, DeepSeek

**Form Protection Guards**

* **Contact Guard** - Protect contact forms from spam
* **Subscribe Guard** - Protect newsletter subscription forms
* **Registration Guard** - Protect WordPress user registration
* **Login Guard** - Protect login forms with validation
* **Comment Guard** - Advanced comment spam protection with custom rules
* **Product Review Guard** - Protect WooCommerce product reviews

**Additional Features**

* **Activity Dashboard** - Monitor all validation events with analytics
* **Detailed Logging** - Track every blocked attempt with reasons
* **Blocked Users Management** - View and manage blocked users
* **Custom Error Messages** - Customize all validation messages
* **Email Templates** - Customizable notification emails
* **Translation Ready** - Full internationalization support

= Supported Forms =

VMS Span Checker works with any HTML form including:

* Contact Form 7
* WPForms
* Gravity Forms
* Ninja Forms
* Formidable Forms
* Newsletter Plugin
* Mailchimp Forms
* WooCommerce Forms
* WordPress Registration
* WordPress Comments
* Any Custom HTML Form

= How It Works =

1. **Install and Activate** - Simple one-click installation
2. **Add Form Mapping** - Tell the plugin which forms to protect
3. **Configure Validation** - Choose your protection level
4. **Automatic Protection** - Forms are protected in real-time

= API Keys (Optional) =

Basic protection works without any API keys. For enhanced security:

* **Google Web Risk** - Detect malware and phishing domains
* **VirusTotal** - Multi-engine scanning (free tier: 500 requests/day)
* **AI Providers** - Enable intelligent spam detection

= Privacy & GDPR =

* Only email domains are processed, not full email addresses
* No personal data is stored externally
* All API calls use domain-level data only
* Full GDPR compliance

== Installation ==

= Automatic Installation =

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "VMS Span Checker"
3. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

= After Activation =

1. Navigate to **VMS Span Checker** in your admin menu
2. Go to **Form Guard** and add your first form mapping
3. Configure validation options as needed
4. (Optional) Add API keys for enhanced protection

== Frequently Asked Questions ==

= Does this work without API keys? =

Yes! Basic disposable email detection and domain validation work without any API keys. Google Web Risk, VirusTotal, and AI features are optional enhancements.

= Will it slow down my forms? =

No. Validation is performed via AJAX before form submission. Most checks complete in under 500ms. API responses are cached to minimize latency.

= Is it compatible with my form plugin? =

VMS Span Checker works with any HTML form. It's tested with Contact Form 7, WPForms, Gravity Forms, Ninja Forms, Newsletter Plugin, and many more.

= Is it GDPR compliant? =

Yes. The plugin only processes email domains, not full email addresses or personal data. No personal information is stored externally.

= How do I protect a custom form? =

1. Go to Form Guard
2. Click "Add form guard mapping"
3. Set Form Type to "Custom"
4. Enter your form's CSS selector (e.g., `#my-form` or `.contact-form`)
5. Configure validation options and save

= Can I whitelist specific domains? =

Yes. Go to **VMS Span Checker > Whitelist Domains** and add trusted domains. Whitelisted domains bypass all validation checks.

= Why is a legitimate email being blocked? =

Check these in order:
1. Is the domain on the disposable list? Add it to the whitelist
2. Is VirusTotal flagging it? Adjust the detection thresholds
3. Is the HTTPS check failing? Disable the HTTPS requirement for that form

= Can I use multiple AI providers? =

You can save API keys for multiple providers, but only one is active at a time. You can switch between providers anytime without losing your keys.

= How many disposable domains are blocked? =

The plugin includes over 10,000 known disposable email domains, and you can add custom domains to the blocklist.

= Does it work with WooCommerce? =

Yes! VMS Span Checker includes a dedicated Product Review Guard for WooCommerce and can protect checkout/registration forms.

== Screenshots ==

1. **Dashboard** - Overview of spam protection statistics and quick links
2. **Form Guard** - Configure form mappings with flexible targeting options
3. **Form Guard Settings** - Detailed validation options for each form
4. **Whitelist Domains** - Manage trusted domains that always pass validation
5. **Disposable Domains** - View and add blocked disposable email domains
6. **API Settings** - Configure Google Web Risk, VirusTotal, and reCAPTCHA
7. **AI Settings** - Configure AI providers for spam detection and summaries
8. **Comment Guard** - Advanced comment spam rules and patterns
9. **Activity Log** - Detailed logging of all validation events
10. **Frontend Validation** - Real-time validation feedback on forms

== Changelog ==

= 1.0.0 =
* Initial release
* Email domain validation with MX record checking
* Disposable email detection (10,000+ domains)
* Custom whitelist and blocklist management
* Google Web Risk API integration
* VirusTotal API integration with multiple key support
* Google reCAPTCHA v2 and v3 support
* AI spam detection with OpenAI, Anthropic, Gemini, and DeepSeek
* AI-powered post and product summaries
* Form Guard with flexible page/post targeting
* Registration Guard for WordPress registration
* Login Guard for login form protection
* Contact Guard for contact form protection
* Subscribe Guard for newsletter forms
* Comment Guard with custom spam rules
* Product Review Guard for WooCommerce
* Activity dashboard with analytics
* Detailed validation logging
* Blocked users management
* Custom error messages
* Email templates
* Full translation/i18n support

== Upgrade Notice ==

= 1.0.0 =
Initial release of VMS Span Checker. Install to protect your forms from spam and malicious submissions.

== Additional Information ==

= Minimum Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

= API Rate Limits =

* **Google Web Risk**: 100,000 requests/month (free with billing enabled)
* **VirusTotal**: 500 requests/day per API key (add multiple keys for higher limits)
* **OpenAI/Anthropic/Gemini/DeepSeek**: Pay-per-use, approximately $0.01 per AI check

= Support =

* Documentation: [https://vmsuniverse.com/docs/vms-span-checker](https://vmsuniverse.com/docs/vms-span-checker)
* Support Forum: [WordPress.org Support](https://wordpress.org/support/plugin/vms-span-checker/)
* Email: support@vmsuniverse.com

= Credits =

Developed by [VMS Universe](https://vmsuniverse.com)

This plugin integrates with third-party services:
* [Google Web Risk API](https://cloud.google.com/web-risk)
* [VirusTotal API](https://www.virustotal.com/)
* [OpenAI API](https://openai.com/)
* [Anthropic API](https://www.anthropic.com/)
* [Google Gemini API](https://ai.google.dev/)
* [DeepSeek API](https://www.deepseek.com/)
