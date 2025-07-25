#!/bin/bash

# Release script for Gravity Forms WooCommerce Coupon Generator Plugin
# Usage: ./scripts/release.sh 1.0.1

if [ -z "$1" ]; then
    echo "Usage: ./scripts/release.sh <version>"
    echo "Example: ./scripts/release.sh 1.0.1"
    exit 1
fi

VERSION=$1
TAG="v$VERSION"

echo "🚀 Creating release for version $VERSION..."

# Update version in main plugin file header comment and constant
echo "📝 Updating version in gravity-forms-woocommerce-coupon-generator.php..."
sed -i '' "s/Version: [0-9.]*/Version: $VERSION/" gravity-forms-woocommerce-coupon-generator.php
sed -i '' "s/define('GFWCG_VERSION', '[0-9.]*');/define('GFWCG_VERSION', '$VERSION');/" gravity-forms-woocommerce-coupon-generator.php

# Update version in README.md if it exists
if [ -f "README.md" ]; then
    echo "📝 Updating version in README.md..."
    sed -i '' "s/Version: [0-9.]*/Version: $VERSION/" README.md
fi

# Commit changes
echo "💾 Committing version changes..."
git add .
git commit -m "Version $VERSION"

# Push to main
echo "📤 Pushing to main..."
git push origin main

# Create and push tag
echo "🏷️  Creating tag $TAG..."
git tag $TAG
git push origin $TAG

# Create GitHub release
echo "🚀 Creating GitHub release..."
gh release create "$TAG" \
    --title "Version $VERSION" \
    --notes "## What's New in Version $VERSION

- Enhanced select component with category search functionality
- Improved admin interface styling and accessibility
- Added product category search via AJAX endpoint
- Optimized form processing and email delivery
- Better error handling and user experience

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Gravity Forms
- WooCommerce 5.0+"

echo "✅ Release created successfully!"
echo "🔗 View release at: https://github.com/mateitudor/gravity-forms-woocommerce-coupon-generator/releases"
