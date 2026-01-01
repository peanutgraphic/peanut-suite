#!/bin/bash

# Peanut Suite - Version Bump Script
# Updates version across all files that contain version numbers

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

# Get current version from main plugin file
CURRENT_VERSION=$(grep -m1 "Version:" "$ROOT_DIR/peanut-suite.php" | sed 's/.*Version: *\([0-9.]*\).*/\1/')

# Parse version components
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

# Determine new version based on argument
case "${1:-patch}" in
    major)
        NEW_VERSION="$((MAJOR + 1)).0.0"
        ;;
    minor)
        NEW_VERSION="$MAJOR.$((MINOR + 1)).0"
        ;;
    patch|"")
        NEW_VERSION="$MAJOR.$MINOR.$((PATCH + 1))"
        ;;
    *)
        # Assume it's a specific version number
        if [[ $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            NEW_VERSION="$1"
        else
            echo "❌ Invalid version format: $1"
            echo ""
            echo "Usage: $0 [major|minor|patch|x.y.z]"
            echo ""
            echo "  major  - Bump major version (1.0.0 -> 2.0.0)"
            echo "  minor  - Bump minor version (1.0.0 -> 1.1.0)"
            echo "  patch  - Bump patch version (1.0.0 -> 1.0.1) [default]"
            echo "  x.y.z  - Set specific version"
            echo ""
            exit 1
        fi
        ;;
esac

echo ""
echo "🔄 Bumping version: $CURRENT_VERSION → $NEW_VERSION"
echo ""

# Files to update
FILES_UPDATED=0

# 1. Update peanut-suite.php (header comment)
if grep -q "Version: $CURRENT_VERSION" "$ROOT_DIR/peanut-suite.php"; then
    sed -i '' "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" "$ROOT_DIR/peanut-suite.php"
    echo "   ✓ peanut-suite.php (header)"
    FILES_UPDATED=$((FILES_UPDATED + 1))
fi

# 2. Update peanut-suite.php (PEANUT_VERSION constant)
if grep -q "PEANUT_VERSION', '$CURRENT_VERSION'" "$ROOT_DIR/peanut-suite.php"; then
    sed -i '' "s/PEANUT_VERSION', '$CURRENT_VERSION'/PEANUT_VERSION', '$NEW_VERSION'/" "$ROOT_DIR/peanut-suite.php"
    echo "   ✓ peanut-suite.php (PEANUT_VERSION)"
    FILES_UPDATED=$((FILES_UPDATED + 1))
fi

# 3. Update peanut-suite.php (PEANUT_SUITE_VERSION constant)
if grep -q "PEANUT_SUITE_VERSION', '$CURRENT_VERSION'" "$ROOT_DIR/peanut-suite.php"; then
    sed -i '' "s/PEANUT_SUITE_VERSION', '$CURRENT_VERSION'/PEANUT_SUITE_VERSION', '$NEW_VERSION'/" "$ROOT_DIR/peanut-suite.php"
    echo "   ✓ peanut-suite.php (PEANUT_SUITE_VERSION)"
    FILES_UPDATED=$((FILES_UPDATED + 1))
fi

# 4. Update package.json
if [ -f "$ROOT_DIR/package.json" ]; then
    sed -i '' "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$ROOT_DIR/package.json"
    echo "   ✓ package.json"
    FILES_UPDATED=$((FILES_UPDATED + 1))
fi

# 5. Update frontend/package.json
if [ -f "$ROOT_DIR/frontend/package.json" ]; then
    sed -i '' "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$ROOT_DIR/frontend/package.json"
    echo "   ✓ frontend/package.json"
    FILES_UPDATED=$((FILES_UPDATED + 1))
fi

# 6. Update docs/openapi.yaml
if [ -f "$ROOT_DIR/docs/openapi.yaml" ]; then
    sed -i '' "s/version: $CURRENT_VERSION/version: $NEW_VERSION/" "$ROOT_DIR/docs/openapi.yaml"
    echo "   ✓ docs/openapi.yaml"
    FILES_UPDATED=$((FILES_UPDATED + 1))
fi

echo ""
echo "✅ Version bumped to $NEW_VERSION ($FILES_UPDATED files updated)"
echo ""
