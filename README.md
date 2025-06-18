# Gravity Forms WooCommerce Coupon Generator

A powerful WordPress plugin that automatically generates WooCommerce coupon codes from Gravity Forms submissions. Perfect for creating lead magnets, newsletter signups, and promotional campaigns.

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Shortcode Settings](#shortcode-settings)
- [Generator Configuration](#generator-configuration)
- [Email Templates](#email-templates)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

## Overview

The Gravity Forms WooCommerce Coupon Generator plugin seamlessly integrates Gravity Forms with WooCommerce to automatically create and send coupon codes when users submit forms. This is ideal for:

- Newsletter signup incentives
- Lead generation campaigns
- Customer retention programs
- Promotional giveaways
- Social media campaigns

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Gravity Forms plugin (any license)
- WooCommerce 5.0 or higher
- WooCommerce HPOS compatible

## Installation

1. Upload the plugin files to `/wp-content/plugins/gravity-forms-woocommerce-coupon-generator/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Gravity Forms > Coupon Generators** to configure your first generator

## Quick Start

1. **Create a Generator**: Go to **Gravity Forms > Coupon Generators** and click "Add New"
2. **Configure Settings**: Set up your form, email field, and coupon parameters
3. **Use the Shortcode**: Add the shortcode to any page or post where you want the form to appear

## Shortcode Settings

The plugin extends Gravity Forms shortcodes with additional parameters to enable coupon generation. Here's a comprehensive guide to all available shortcode settings:

### Basic Shortcode Usage

```php
[gravityform id="1" gen="2"]
```

### Shortcode Parameters

#### Required Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `id` | integer | The Gravity Form ID | `id="1"` |
| `gen` | integer | The Coupon Generator ID | `gen="2"` |

#### Optional Parameters

| Parameter | Type | Default | Description | Example |
|-----------|------|---------|-------------|---------|
| `title` | boolean | `true` | Show/hide form title | `title="false"` |
| `description` | boolean | `true` | Show/hide form description | `description="false"` |
| `tabindex` | integer | `1` | Starting tab index | `tabindex="10"` |
| `ajax` | boolean | `false` | Enable AJAX submission | `ajax="true"` |
| `field_values` | string | `null` | Pre-populate field values | `field_values="name=John&email=john@example.com"` |
| `action` | string | `form` | Form action type | `action="form"` |

### Complete Shortcode Examples

#### Basic Form with Coupon Generation
```php
[gravityform id="1" gen="2" title="false" ajax="true"]
```

#### Form with Pre-populated Values
```php
[gravityform id="1" gen="2" field_values="source=website&campaign=summer2024"]
```

#### Minimal Form Display
```php
[gravityform id="1" gen="2" title="false" description="false" tabindex="1"]
```

### How the Shortcode Works

1. **Form Display**: The shortcode displays the specified Gravity Form
2. **Generator Integration**: The `gen` parameter links the form to a specific coupon generator
3. **Hidden Field Injection**: The plugin automatically adds a hidden field containing the generator ID
4. **Form Submission**: When the form is submitted, the plugin processes the submission and generates a coupon
5. **Email Delivery**: If configured, the coupon code is automatically emailed to the user

### Shortcode Processing Flow

```
[gravityform id="1" gen="2"] 
    ↓
Form Displayed with Generator ID Hidden Field
    ↓
User Submits Form
    ↓
Plugin Processes Submission
    ↓
Coupon Code Generated
    ↓
WooCommerce Coupon Created
    ↓
Email Sent (if enabled)
```

### Advanced Shortcode Usage

#### Conditional Display
```php
<?php if (is_user_logged_in()): ?>
    [gravityform id="1" gen="2" title="false"]
<?php else: ?>
    [gravityform id="3" gen="4" title="false"]
<?php endif; ?>
```

#### Multiple Forms on Same Page
```php
<!-- Newsletter Signup -->
[gravityform id="1" gen="2" title="false" description="false"]

<!-- Product Inquiry -->
[gravityform id="3" gen="5" title="false" description="false"]
```

#### Custom Field Values
```php
[gravityform id="1" gen="2" field_values="utm_source=google&utm_medium=cpc&utm_campaign=summer_sale"]
```

### Shortcode Security

- All parameters are sanitized before processing
- Generator ID validation ensures only active generators are used
- Form ID validation prevents unauthorized form access
- XSS protection is built into all field value processing

### Shortcode Performance

- Lightweight processing with minimal overhead
- Efficient database queries for generator lookup
- Cached form data for improved performance
- Optimized for high-traffic sites

### Troubleshooting Shortcodes

#### Common Issues

1. **Form Not Displaying**
   - Verify the form ID exists and is published
   - Check that the generator ID is valid and active
   - Ensure Gravity Forms is properly installed

2. **Coupon Not Generating**
   - Confirm the generator is linked to the correct form
   - Check that the email field ID is correctly configured
   - Verify WooCommerce is active and properly configured

3. **Email Not Sending**
   - Check email settings in the generator configuration
   - Verify the email field contains a valid email address
   - Test email delivery with a simple form submission

#### Debug Mode

Enable debug logging by adding this to your `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Debug information will be logged to `/wp-content/debug.log`.

## Generator Configuration

### Creating a New Generator

1. Navigate to **Gravity Forms > Coupon Generators**
2. Click **"Add New"**
3. Configure the following settings:

#### Basic Settings
- **Title**: A descriptive name for your generator
- **Form**: Select the Gravity Form to use
- **Email Field**: Choose the form field containing the email address
- **Name Field**: (Optional) Choose a field for the customer's name

#### Coupon Settings
- **Coupon Type**: 
  - `random` - Generate random coupon codes
  - `field` - Use a form field value as the coupon code
- **Coupon Field**: (Required if type is 'field') Select the form field
- **Prefix**: Text to add before the coupon code
- **Suffix**: Text to add after the coupon code
- **Separator**: Character to separate prefix/suffix from the code
- **Length**: Number of characters for random codes (default: 8)

#### Discount Settings
- **Discount Type**: 
  - `percentage` - Percentage discount
  - `fixed_cart` - Fixed amount discount
- **Discount Amount**: The discount value
- **Individual Use**: Whether the coupon can be used with other coupons
- **Usage Limits**: Set per-coupon and per-user usage limits
- **Minimum/Maximum Spend**: Set spending requirements

#### Restrictions
- **Products**: Specific products the coupon applies to
- **Categories**: Product categories the coupon applies to
- **Exclude Sale Items**: Whether to exclude items on sale
- **Free Shipping**: Whether the coupon provides free shipping
- **Expiry**: Set coupon expiration in days

#### Email Settings
- **Send Email**: Enable/disable automatic email sending
- **Email Subject**: Custom subject line for the email
- **Email Message**: Custom message content
- **From Name**: Custom sender name
- **From Email**: Custom sender email address

## Email Templates

### Default Email Template

The plugin includes a professional email template that includes:
- Company branding
- Personalized greeting
- Coupon code display
- Usage instructions
- Terms and conditions

### Customizing Email Templates

You can customize the email template by:
1. Editing the template in the generator settings
2. Using HTML formatting
3. Including merge tags for personalization

### Email Merge Tags

| Tag | Description | Example |
|-----|-------------|---------|
| `{coupon_code}` | The generated coupon code | `SUMMER2024` |
| `{customer_name}` | Customer's name (if provided) | `John Doe` |
| `{discount_amount}` | The discount amount | `20%` or `$10` |
| `{expiry_date}` | Coupon expiration date | `December 31, 2024` |
| `{site_name}` | Your website name | `My Store` |

## Advanced Features

### Conditional Logic
- Set different coupon values based on form field values
- Create tiered discount systems
- Implement dynamic pricing strategies

### Integration Options
- WooCommerce Subscriptions compatibility
- Third-party email service integration
- Custom webhook support

### Analytics and Reporting
- Track coupon usage and conversion rates
- Monitor form submission analytics
- Generate detailed reports

## Troubleshooting

### Common Issues

1. **Plugin Not Working**
   - Verify all requirements are met
   - Check for plugin conflicts
   - Review error logs

2. **Coupons Not Creating**
   - Check WooCommerce settings
   - Verify generator configuration
   - Test with a simple form

3. **Emails Not Sending**
   - Check email server configuration
   - Verify email field mapping
   - Test email delivery

### Getting Help

- Check the plugin documentation
- Review the troubleshooting guide
- Contact support for assistance

## Support

For support and documentation:
- **Website**: https://storiabooks.com
- **Documentation**: [Plugin Documentation](https://storiabooks.com/docs)
- **Support**: [Contact Support](https://storiabooks.com/support)

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Compatibility**: WordPress 5.8+, WooCommerce 5.0+, Gravity Forms 