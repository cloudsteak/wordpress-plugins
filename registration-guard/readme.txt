=== Registration Guard – Anti-Spam Signup Protection ===
Contributors: cloudsteak
Tags: registration, spam, anti-spam, honeypot, security
Requires at least: 6.2
Tested up to: 6.7.2
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block bot registrations on the WordPress signup form with honeypot, time-trap, and IP rate limiting — no API keys required.

== Description ==

**Registration Guard** protects the native WordPress registration form (`/wp-login.php?action=register`) and any custom page that uses the `register_form` hook from automated bot signups — without relying on external services, API keys, or third-party accounts.

This plugin is lightweight, privacy-friendly, and designed to stay out of your way. No data is sent to external servers. Everything runs locally on your WordPress site.

= Key Features =

* **Honeypot field** — Adds a hidden input field with a configurable, neutral field name. Bots that fill it in are silently rejected.
* **Time-trap** — Rejects submissions that arrive too quickly after the form loads (default: 3 seconds) or after the form has expired (default: 1 hour).
* **Token verification** — Generates a unique, IP-bound token on every form load to block direct POST requests that skip form rendering.
* **IP rate limiting** — Limits registration attempts per IP address using WordPress transients (default: 3 attempts per hour). Supports Cloudflare (`CF-Connecting-IP` header).
* **Optional logging** — Keeps a rolling log of the last 500 rejected attempts, viewable in the admin settings page.
* **No external dependencies** — No reCAPTCHA, Turnstile, Akismet, or other third-party services required.

= Privacy =

Registration Guard does not phone home, track users, or send any data to third parties. Rejected attempt logs (when enabled) store only a timestamp, IP address, and rejection reason — all stored locally in your WordPress database.

= Performance =

The plugin adds only a tiny hidden CSS file and a few hidden form fields. No JavaScript is required on the frontend. There is no measurable impact on page load times.

= Languages =

* English (default)
* Hungarian (Magyar)

== Installation ==

1. Upload the `registration-guard` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings > Registration Guard** to configure protection options.
4. Ensure **Anyone can register** is enabled under **Settings > General** if you want public signup to be available.

== Frequently Asked Questions ==

= Does this replace CAPTCHA? =

For many sites, yes. Registration Guard uses multiple layered techniques (honeypot, timing analysis, token verification, and rate limiting) that catch most automated bot registrations without requiring users to solve puzzles. For high-traffic sites under heavy attack, you may still want to combine it with additional measures.

= Will this affect SEO or site performance? =

No. The plugin only loads a small CSS file on the registration page and adds hidden form fields. It does not modify your content, inject scripts on public pages, or make external HTTP requests.

= Does it store personal data? =

When logging is enabled, the plugin stores the IP address, timestamp, and rejection reason for blocked signup attempts. No usernames, email addresses, or passwords from rejected submissions are stored. All data remains in your local WordPress database.

= Is it compatible with custom login pages? =

Yes. Any theme or plugin that renders the standard WordPress registration form using the `register_form` action hook is supported. This includes custom login page plugins that call `wp_register_form()` or include the core registration template.

= Does it work with WooCommerce or Elementor registration forms? =

Registration Guard protects the **WordPress core** registration form. WooCommerce and Elementor use their own registration handlers and forms, which are not covered by this plugin. If your site uses WooCommerce customer registration, you will need a WooCommerce-specific anti-spam solution.

= Can I disable protection temporarily? =

Yes. Go to **Settings > Registration Guard** and uncheck **Enable protection**. All checks are bypassed while disabled.

== Screenshots ==

1. Settings page with protection options (time-trap threshold, rate limit, honeypot field name, logging toggle).
2. Rejected registration attempts log table with timestamp, IP address, and rejection reason.

== Changelog ==

= 1.0.0 =
* Initial release.
* Honeypot field with configurable field name and multi-layer CSS hiding.
* Time-trap with signed timestamp and configurable minimum submit delay.
* IP-bound token verification on every form submission.
* IP-based rate limiting with Cloudflare support.
* Optional rejected-attempt logging (up to 500 entries).
* Settings page under Settings > Registration Guard.
* English and Hungarian translations.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Registration Guard – Anti-Spam Signup Protection.
