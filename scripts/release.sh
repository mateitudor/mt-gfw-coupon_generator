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

# Update version in main plugin file header comment
echo "📝 Updating version in gravity-forms-woocommerce-coupon-generator.php..."
sed -i '' "s/Version: [0-9.]*/Version: $VERSION/" gravity-forms-woocommerce-coupon-generator.php

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

echo "✅ Release process started!"
echo "📋 GitHub Actions will automatically:"
echo "   - Build the plugin zip"
echo "   - Create a release with the zip file"
echo "   - Generate release notes"
echo ""
echo "🔗 Check progress at: https://github.com/mateitudor/gravity-forms-woocommerce-coupon-generator/actions"
echo "🔗 Release will be at: https://github.com/mateitudor/gravity-forms-woocommerce-coupon-generator/releases"
