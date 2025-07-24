# Internationalization (i18n) Files

This directory contains translation files for the Gravity Forms WooCommerce Coupon Generator plugin.

## Files

- `gravity-forms-woocommerce-coupon-generator.pot` - Template file containing all translatable strings
- `gravity-forms-woocommerce-coupon-generator-ro_RO.po` - Romanian translation source file
- `gravity-forms-woocommerce-coupon-generator-ro_RO.mo` - Compiled Romanian translation file

## Adding New Translations

1. Copy the `.pot` file and rename it to match your locale (e.g., `gravity-forms-woocommerce-coupon-generator-fr_FR.po`)
2. Translate the strings in the `.po` file
3. Compile the `.po` file to create the `.mo` file using:
   ```
   msgfmt gravity-forms-woocommerce-coupon-generator-fr_FR.po -o gravity-forms-woocommerce-coupon-generator-fr_FR.mo
   ```

## Supported Locales

- `ro_RO` - Romanian (Romania)

## Translation Tools

- Use tools like Poedit, Loco Translate, or WP-CLI to manage translations
- WordPress will automatically load the appropriate translation file based on the site's locale setting
