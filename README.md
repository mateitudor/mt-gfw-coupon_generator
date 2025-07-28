# Gravity Forms WooCommerce Coupon Generator

A powerful WordPress plugin that automatically generates WooCommerce coupon codes from Gravity Forms submissions. Perfect for creating lead magnets, newsletter signups, and promotional campaigns.

**Version: 1.0.1**

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

The Gravity Forms WooCommerce Coupon Generator plugin seamlessly integrates Gravity Forms with WooCommerce to automatically create and send coduri de reducere when users submit forms. This is ideal for:

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
3. **Use the Shortcode**: Add the shortcode to any page or post

## Shortcodes

#### Display Generator Restrictions
Use this shortcode to display the restrictions and details of a generator de cod de reducere on the frontend.

**Basic Usage:**
```
[gfwcg_restrictions id="1"]
```

**With Slug:**
```
[gfwcg_restrictions slug="my-generator"]
```

**Parameters:**

**Basic Parameters:**
- `id` - Generator ID (optional if slug is provided)
- `slug` - Generator slug (optional if id is provided)
- `show_title` - Show generator title (true/false, default: true)
- `show_description` - Show generator description (true/false, default: true)
- `show_discount` - Show discount details (true/false, default: true)
- `show_usage` - Show usage limits (true/false, default: true)
- `show_restrictions` - Show product/category restrictions (true/false, default: true)
- `show_expiry` - Show expiry information (true/false, default: true)
- `css_class` - Custom CSS class (default: gfwcg-restrictions)
- `display_section_titles` - Show section titles (true/false, default: true)

**Discount Section Controls:**
- `display_discount_labels` - Show discount labels (true/false, default: true)
- `discount_type_value` - Show discount type value (true/false, default: true)
- `discount_type_label` - Show discount type label (true/false, default: true)
- `discount_amount_value` - Show discount amount value (true/false, default: true)
- `discount_amount_label` - Show discount amount label (true/false, default: true)
- `discount_free_shipping_value` - Show free shipping value (true/false, default: true)
- `discount_free_shipping_label` - Show free shipping label (true/false, default: true)

**Usage Section Controls:**
- `display_usage_labels` - Show usage labels (true/false, default: true)
- `usage_per_coupon_value` - Show per coupon usage value (true/false, default: true)
- `usage_per_coupon_label` - Show per coupon usage label (true/false, default: true)
- `usage_per_user_value` - Show per user usage value (true/false, default: true)
- `usage_per_user_label` - Show per user usage label (true/false, default: true)
- `usage_individual_value` - Show individual use value (true/false, default: true)
- `usage_individual_label` - Show individual use label (true/false, default: true)

**Restrictions Section Controls:**
- `display_restrictions_labels` - Show restrictions labels (true/false, default: true)
- `restrictions_minimum_value` - Show minimum spend value (true/false, default: true)
- `restrictions_minimum_label` - Show minimum spend label (true/false, default: true)
- `restrictions_maximum_value` - Show maximum spend value (true/false, default: true)
- `restrictions_maximum_label` - Show maximum spend label (true/false, default: true)
- `restrictions_exclude_sale_value` - Show exclude sale items value (true/false, default: true)
- `restrictions_exclude_sale_label` - Show exclude sale items label (true/false, default: true)
- `restrictions_products_value` - Show included products value (true/false, default: true)
- `restrictions_products_label` - Show included products label (true/false, default: true)
- `restrictions_exclude_products_value` - Show excluded products value (true/false, default: true)
- `restrictions_exclude_products_label` - Show excluded products label (true/false, default: true)
- `restrictions_categories_value` - Show included categories value (true/false, default: true)
- `restrictions_categories_label` - Show included categories label (true/false, default: true)
- `restrictions_exclude_categories_value` - Show excluded categories value (true/false, default: true)
- `restrictions_exclude_categories_label` - Show excluded categories label (true/false, default: true)

**Expiry Section Controls:**
- `display_expiry_labels` - Show expiry labels (true/false, default: true)
- `expiry_days_value` - Show expiry days value (true/false, default: true)
- `expiry_days_label` - Show expiry days label (true/false, default: true)

**Examples:**

**Basic Usage:**
```
[gfwcg_restrictions id="1"]
[gfwcg_restrictions slug="summer-sale"]
```

**Hide Specific Sections:**
```
[gfwcg_restrictions id="1" show_title="false" show_description="false"]
[gfwcg_restrictions slug="winter-promo" show_restrictions="false" show_expiry="false"]
```

**Granular Control - Hide Labels Only:**
```
[gfwcg_restrictions id="1" display_discount_labels="false" display_usage_labels="false"]
[gfwcg_restrictions slug="spring-sale" display_restrictions_labels="false"]
```

**Custom Styling:**
```
[gfwcg_restrictions id="1" css_class="my-custom-restrictions"]
[gfwcg_restrictions slug="holiday-promo" css_class="promo-box restrictions"]
```

**Minimal Display:**
```
[gfwcg_restrictions id="1" show_title="false" show_description="false" display_section_titles="false" display_discount_labels="false" display_usage_labels="false" display_restrictions_labels="false" display_expiry_labels="false"]
```

#### Display Generator Form with Restrictions
Use this shortcode to display both the restrictions and the Gravity Form for generarea codului de reducere. This combines the restrictions display with the actual form submission.

**Basic Usage:**
```
[gfwcg_form id="1"]
[gfwcg_form slug="summer-sale"]
```

**Parameters:**
- `id` - Generator ID (optional if slug is provided)
- `slug` - Generator slug (optional if id is provided)
- `show_restrictions` - Show restrictions above form (true/false, default: true)
- `css_class` - Custom CSS class (default: gfwcg-form)

**Examples:**

**Basic Form with Restrictions:**
```
[gfwcg_form id="1"]
[gfwcg_form slug="winter-promo"]
```

**Form Without Restrictions Display:**
```
[gfwcg_form id="1" show_restrictions="false"]
[gfwcg_form slug="spring-sale" show_restrictions="false"]
```

**Custom Styled Form:**
```
[gfwcg_form id="1" css_class="my-custom-form-container"]
[gfwcg_form slug="holiday-promo" css_class="promo-form-wrapper"]
```

**How it Works:**
1. Displays generator restrictions (if enabled)
2. Shows the associated Gravity Form
3. Automatically injects generator ID as hidden field
4. Processes form submission for generarea codului de reducere

## Shortcode Settings

The plugin extends Gravity Forms shortcodes with additional parameters to enable generarea codului de reducere. Here's a comprehensive guide to all available shortcode settings:

### Basic Shortcode Usage

```php
[gravityform id="1" gen="2"]
```

### Shortcode Parameters

#### Required Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `id` | integer | The Gravity Form ID | `id="1"` |
| `gen` | integer | The Cod de Reducere Generator ID | `gen="2"` |

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

#### Basic Form with Generarea Codului de Reducere
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
2. **Generator Integration**: The `gen` parameter links the form to a specific generator de cod de reducere
3. **Hidden Field Injection**: The plugin automatically adds a hidden field containing the generator ID
4. **Form Submission**: When the form is submitted, the plugin processes the submission and generates a cod de reducere
5. **Email Delivery**: If configured, the cod de reducere is automatically emailed to the user

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
Cod de Reducere Generat
    ↓
WooCommerce Cod de Reducere Creat
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

2. **Cod de Reducere Nu Se Generează**
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

#### Cod de Reducere Settings
- **Tip Cod de Reducere**: 
  - `random` - Generează coduri de reducere aleatorii
  - `field` - Folosește o valoare din câmpul formularului ca cod de reducere
- **Câmp Cod de Reducere**: (Necesar dacă tipul este 'field') Selectează câmpul formularului
- **Prefix**: Text de adăugat înaintea codului de reducere
- **Sufix**: Text de adăugat după codul de reducere
- **Separator**: Caracter pentru separarea prefixului/sufixului de cod
- **Lungime**: Numărul de caractere pentru codurile aleatorii (implicit: 8)

#### Discount Settings
- **Discount Type**: 
  - `percentage` - Percentage discount
  - `fixed_cart` - Fixed amount discount
- **Discount Amount**: The discount value
- **Individual Use**: Whether the cod de reducere can be used with other coduri de reducere
- **Usage Limits**: Set per-cod de reducere and per-user usage limits
- **Minimum/Maximum Spend**: Set spending requirements

#### Restrictions
- **Products**: Specific products the coupon applies to
- **Categories**: Product categories the coupon applies to
- **Exclude Sale Items**: Whether to exclude items on sale
- **Free Shipping**: Whether the cod de reducere provides free shipping
- **Expiry**: Set cod de reducere expiration in days

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
- Cod de reducere display
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
| `{coupon_code}` | The generated cod de reducere | `SUMMER2024` |
| `{customer_name}` | Customer's name (if provided) | `John Doe` |
| `{discount_amount}` | The discount amount | `20%` or `$10` |
| `{expiry_date}` | Cod de reducere expiration date | `December 31, 2024` |
| `{site_name}` | Your website name | `My Store` |

## Advanced Features

### Conditional Logic
- Set different cod de reducere values based on form field values
- Create tiered discount systems
- Implement dynamic pricing strategies

### Integration Options
- WooCommerce Subscriptions compatibility
- Third-party email service integration
- Custom webhook support

### Analytics and Reporting
- Track cod de reducere usage and conversion rates
- Monitor form submission analytics
- Generate detailed reports

## Troubleshooting

### Common Issues

1. **Plugin Not Working**
   - Verify all requirements are met
   - Check for plugin conflicts
   - Review error logs

2. **Coduri de Reducere Nu Se Creează**
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