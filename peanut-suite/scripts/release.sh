#!/bin/bash

# Peanut Suite - Full Release Workflow
# Bumps version, commits, packages, creates GitHub release, and deploys

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$ROOT_DIR"

# Check for uncommitted changes
if [[ -n $(git status --porcelain) ]]; then
    echo "❌ Error: You have uncommitted changes. Please commit or stash them first."
    exit 1
fi

# Bump version
BUMP_TYPE="${1:-patch}"
echo ""
echo "📦 Starting release workflow..."
echo ""

bash "$SCRIPT_DIR/bump-version.sh" "$BUMP_TYPE"

# Get new version
VERSION=$(grep -m1 "Version:" "$ROOT_DIR/peanut-suite.php" | sed 's/.*Version: *\([0-9.]*\).*/\1/')

# Commit version bump
echo "📝 Committing version bump..."
git add -A
git commit -m "Bump version to $VERSION

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"

# Push to remote
echo "🚀 Pushing to remote..."
git push origin main

# Create package
echo "📦 Creating package..."
bash "$SCRIPT_DIR/package.sh"

# Create GitHub release
echo "🏷️  Creating GitHub release..."
gh release create "v$VERSION" \
    --title "Peanut Suite v$VERSION" \
    --notes "## Peanut Suite v$VERSION

### Installation
1. Download \`peanut-suite.zip\` below
2. Upload to WordPress via Plugins → Add New → Upload Plugin
3. Activate the plugin" \
    "$ROOT_DIR/dist/peanut-suite-$VERSION.zip#peanut-suite.zip"

# Deploy to peanutgraphic
echo "🌐 Deploying to peanutgraphic..."
rsync -avz --delete --exclude='.git' --exclude='node_modules' --exclude='.env' --exclude='dist' "$ROOT_DIR/" peanutgraphic:~/public_html/wp-content/plugins/peanut-suite/

echo ""
echo "✅ Release v$VERSION complete!"
echo "   - Committed and pushed"
echo "   - GitHub release created"
echo "   - Deployed to peanutgraphic"
echo ""
