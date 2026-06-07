=== VMS Elements Form Guard ===
Contributors: vmsuniverse
Donate link: https://vmselements.com/product/vms-elements-form-guard-pro
Tags: spam, email, disposable, anti-spam, validation
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect your WordPress forms from spam, fake emails, and malicious domains with advanced validation and AI-powered detection.

== Description ==

**VMS Elements Form Guard** is a comprehensive spam protection plugin that validates email domains, blocks disposable emails, and protects your forms from malicious submissions using multiple security layers.

**[VMS Elements Form Guard Pro](https://vmselements.com/product/vms-elements-form-guard-pro)** adds Form Guard, Contact Guard, Subscribe Guard, AI summaries, email templates, and more.

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

VMS Elements Form Guard works with any HTML form including:

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

* Only email domains are processed for validation, not full email addresses (unless you enable features that require them).
* Data is sent to third-party services only when you enter API keys and enable those features.
* No personal data is stored on VMS Elements servers by default.
* Full GDPR compliance depends on your configuration and privacy policy.

== Privacy Policy ==

VMS Elements Form Guard does not collect, store, or send any data from your website on its own. Every outbound network call is opt-in and only happens after you explicitly configure the corresponding API key or feature in the plugin settings.

= Third-party services used by this plugin =

The plugin can connect to the following third-party services. Each one is only contacted when you actively enable the matching feature and provide the required API key:

* **Google Web Risk** — Sends the email domain or URL hostname being validated to `https://webrisk.googleapis.com/v1/uris:search`. Requires your own Google API key. [Terms of Service](https://cloud.google.com/terms) · [Privacy Policy](https://policies.google.com/privacy).
* **VirusTotal** — Sends the email domain or URL hostname being validated to `https://www.virustotal.com/api/v3/domains/`. Requires your own VirusTotal API key. [Terms of Service](https://docs.virustotal.com/docs/historic-terms-of-service) · [Privacy Policy](https://docs.virustotal.com/docs/historic-privacy-policy).
* **Google reCAPTCHA** — Loaded only when you enable reCAPTCHA on a form. The visitor's browser exchanges a verification token with `https://www.google.com/recaptcha/`. [Terms](https://policies.google.com/terms) · [Privacy Policy](https://policies.google.com/privacy).
* **OpenAI** — Sends the message body being moderated to `https://api.openai.com/v1/chat/completions`. Requires your own OpenAI API key. [Terms](https://openai.com/policies/terms-of-use) · [Privacy Policy](https://openai.com/policies/privacy-policy).
* **Anthropic** — Sends the message body being moderated to `https://api.anthropic.com/v1/messages`. Requires your own Anthropic API key. [Terms](https://www.anthropic.com/legal/consumer-terms) · [Privacy Policy](https://www.anthropic.com/legal/privacy).
* **Google Gemini** — Sends the message body being moderated to `https://generativelanguage.googleapis.com/`. Requires your own Gemini API key. [Terms](https://ai.google.dev/gemini-api/terms) · [Privacy Policy](https://policies.google.com/privacy).
* **DeepSeek** — Sends the message body being moderated to `https://api.deepseek.com/chat/completions`. Requires your own DeepSeek API key. [Terms](https://chat.deepseek.com/downloads/DeepSeek%20Terms%20of%20Use.html) · [Privacy Policy](https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html).
* **Amazon Bedrock** — Sends the message body being moderated to the AWS Bedrock Runtime endpoint `https://bedrock-runtime.{region}.amazonaws.com/model/{model}/invoke` (the region and model are the ones you configure). Requires your own AWS access key, secret key, and model ID. [Terms](https://aws.amazon.com/service-terms/) · [Privacy Policy](https://aws.amazon.com/privacy/).

= What data is sent =

For each enabled service the plugin sends ONLY:

* The **domain part** of the submitted email or the URL hostname, for reputation checks (Web Risk, VirusTotal).
* The **message text** the visitor typed in a guarded textarea, for AI spam moderation (OpenAI, Anthropic, Gemini, DeepSeek, Amazon Bedrock). The chosen AI provider receives the body of the field plus a fixed system prompt.
* The **reCAPTCHA token** generated by the visitor's browser, for bot-protection verification (Google reCAPTCHA).

The plugin does NOT send the full submitted form, IP addresses, user accounts, or any other personal data to these third parties.

= Data stored locally =

The plugin writes only to the WordPress site database. The following tables and options may be created:

* `*_vms_elements_form_guard_whitelist_domains`, `*_vms_elements_form_guard_disposable_domains` — Email-domain reputation lists you manage.
* `*_vms_elements_form_guard_logs` — Validation events (domain, decision, timestamp). No personal data.
* `*_vms_elements_form_guard_api_keys` — Encrypted copies of the API keys you entered.
* `*_vms_elements_form_guard_comment_enforcement` — IP/user-ID strike counters for the optional Block User feature.
* `vefg-google-config`, `vefg-virustotal-config`, `vefg-ai-span-config`, `vefg-recaptcha-config`, `vefg-error-messages`, `vefg-registration-guard` — Plugin settings.

All tables and options are removed when you uninstall the plugin.

= Cookies =

The plugin itself does not set any cookies. Google reCAPTCHA sets its own cookies when you enable it.

= GDPR =

Because every outbound request requires an administrator to first enable the matching feature and provide an API key, your site only becomes a "data processor" toward these third parties after you opt in. We recommend listing each enabled service in your site's own privacy policy.

== External services ==

This plugin relies on the following third-party / external services. Each service is contacted ONLY after a site administrator enables the matching feature and provides the required API key or credentials. Nothing is sent by default.

1. **Google Web Risk** — Used to detect malware and phishing domains. The email domain or URL hostname being validated is sent to `https://webrisk.googleapis.com/v1/uris:search` each time a guarded field is validated and this feature is enabled. Provided by Google. [Terms of Service](https://cloud.google.com/terms) — [Privacy Policy](https://policies.google.com/privacy).

2. **VirusTotal** — Used for multi-engine domain reputation scanning. The email domain or URL hostname being validated is sent to `https://www.virustotal.com/api/v3/domains/` each time a guarded field is validated and this feature is enabled. Provided by VirusTotal (Google). [Terms of Service](https://docs.virustotal.com/docs/historic-terms-of-service) — [Privacy Policy](https://docs.virustotal.com/docs/historic-privacy-policy).

3. **Google reCAPTCHA** — Used for bot protection on guarded forms. When enabled, the visitor's browser exchanges a verification token with `https://www.google.com/recaptcha/`. Provided by Google. [Terms of Service](https://policies.google.com/terms) — [Privacy Policy](https://policies.google.com/privacy).

4. **OpenAI** — Used for AI spam moderation. The message text typed in a guarded field is sent to `https://api.openai.com/v1/chat/completions` when AI moderation is enabled with this provider. Provided by OpenAI. [Terms of Use](https://openai.com/policies/terms-of-use) — [Privacy Policy](https://openai.com/policies/privacy-policy).

5. **Anthropic** — Used for AI spam moderation. The message text typed in a guarded field is sent to `https://api.anthropic.com/v1/messages` when AI moderation is enabled with this provider. Provided by Anthropic. [Terms](https://www.anthropic.com/legal/consumer-terms) — [Privacy Policy](https://www.anthropic.com/legal/privacy).

6. **Google Gemini** — Used for AI spam moderation. The message text typed in a guarded field is sent to `https://generativelanguage.googleapis.com/` when AI moderation is enabled with this provider. Provided by Google. [Terms](https://ai.google.dev/gemini-api/terms) — [Privacy Policy](https://policies.google.com/privacy).

7. **DeepSeek** — Used for AI spam moderation. The message text typed in a guarded field is sent to `https://api.deepseek.com/chat/completions` when AI moderation is enabled with this provider. Provided by DeepSeek. [Terms](https://chat.deepseek.com/downloads/DeepSeek%20Terms%20of%20Use.html) — [Privacy Policy](https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html).

8. **Amazon Bedrock** — Used for AI spam moderation. The message text typed in a guarded field is sent to the AWS Bedrock Runtime endpoint `https://bedrock-runtime.{region}.amazonaws.com/model/{model}/invoke` (region and model are the ones you configure) when AI moderation is enabled with this provider. Provided by Amazon Web Services. [Service Terms](https://aws.amazon.com/service-terms/) — [Privacy Policy](https://aws.amazon.com/privacy/).

== Installation ==

= Automatic Installation =

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "VMS Elements Form Guard"
3. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

= After Activation =

1. Navigate to **VMS Elements Form Guard** in your admin menu
2. Go to **Form Guard** and add your first form mapping
3. Configure validation options as needed
4. (Optional) Add API keys for enhanced protection

== Frequently Asked Questions ==

= Does this work without API keys? =

Yes! Basic disposable email detection and domain validation work without any API keys. Google Web Risk, VirusTotal, and AI features are optional enhancements.

= Will it slow down my forms? =

No. Validation is performed via AJAX before form submission. Most checks complete in under 500ms. API responses are cached to minimize latency.

= Is it compatible with my form plugin? =

VMS Elements Form Guard works with any HTML form. It's tested with Contact Form 7, WPForms, Gravity Forms, Ninja Forms, Newsletter Plugin, and many more.

= Is it GDPR compliant? =

The plugin only processes email domains for core validation, not full email addresses. Optional features may process more data depending on your settings. No personal information is stored on VMS servers by default. You are responsible for your site's privacy policy when using third-party APIs.

= What data is sent to external services? =

When configured, the plugin may send domain names (and related validation metadata) to Google Web Risk, VirusTotal, Google reCAPTCHA, and AI providers (OpenAI, Anthropic, Google Gemini, DeepSeek). See the **Privacy Policy** section for the full per-service breakdown.

= How do I protect a custom form? =

1. Go to Form Guard
2. Click "Add form guard mapping"
3. Set Form Type to "Custom"
4. Enter your form's CSS selector (e.g., `#my-form` or `.contact-form`)
5. Configure validation options and save

= Can I whitelist specific domains? =

Yes. Go to **VMS Elements Form Guard > Whitelist Domains** and add trusted domains. Whitelisted domains bypass all validation checks.

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

Yes! VMS Elements Form Guard includes a dedicated Product Review Guard for WooCommerce and can protect checkout/registration forms.

= What are the minimum requirements? =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

= What are the API rate limits? =

* **Google Web Risk**: 100,000 requests/month (free with billing enabled)
* **VirusTotal**: 500 requests/day per API key (add multiple keys for higher limits)
* **OpenAI/Anthropic/Gemini/DeepSeek**: Pay-per-use, approximately $0.01 per AI check

= Where can I get support? =

* Documentation: https://vmselements.com
* Support forum: https://wordpress.org/support/plugin/vms-elements-form-guard/
* Email: support@vmselements.com

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
Initial release of VMS Elements Form Guard. Install to protect your forms from spam and malicious submissions.
