#!/usr/bin/env node

/**
 * Peanut Suite - WordPress Plugin Packaging Script
 *
 * This script creates a distributable ZIP file of the plugin,
 * excluding development files and including only production assets.
 */

const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const ROOT_DIR = path.resolve(__dirname, '..');
const OUTPUT_DIR = path.join(ROOT_DIR, 'dist');
const PLUGIN_NAME = 'peanut-suite';
const VERSION = require('../package.json').version;

// Files and directories to include in the package
const INCLUDE_PATTERNS = [
  'peanut-suite.php',
  'uninstall.php',
  'core/**/*',
  'modules/**/*',
  'assets/dist/**/*',
  'languages/**/*',
];

// Files and directories to exclude from the package
const EXCLUDE_PATTERNS = [
  '.git',
  '.gitignore',
  '.DS_Store',
  'node_modules',
  'frontend/node_modules',
  'frontend/src',
  'frontend/package.json',
  'frontend/package-lock.json',
  'frontend/tsconfig.json',
  'frontend/tsconfig.node.json',
  'frontend/vite.config.ts',
  'frontend/index.html',
  'scripts',
  'package.json',
  'package-lock.json',
  '*.md',
  '*.log',
  '.env*',
  'composer.lock',
  'phpunit.xml',
  'tests',
];

async function createPackage() {
  console.log(`\n📦 Packaging ${PLUGIN_NAME} v${VERSION}...\n`);

  // Create output directory if it doesn't exist
  if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
  }

  const outputPath = path.join(OUTPUT_DIR, `${PLUGIN_NAME}-${VERSION}.zip`);
  const output = fs.createWriteStream(outputPath);
  const archive = archiver('zip', { zlib: { level: 9 } });

  // Handle archive events
  output.on('close', () => {
    const size = (archive.pointer() / 1024 / 1024).toFixed(2);
    console.log(`\n✅ Package created successfully!`);
    console.log(`   📁 ${outputPath}`);
    console.log(`   📊 Size: ${size} MB`);
    console.log(`   📋 Total files: ${archive.pointer()} bytes\n`);
  });

  archive.on('warning', (err) => {
    if (err.code === 'ENOENT') {
      console.warn('⚠️  Warning:', err.message);
    } else {
      throw err;
    }
  });

  archive.on('error', (err) => {
    throw err;
  });

  archive.pipe(output);

  // Helper function to check if path should be excluded
  const shouldExclude = (filePath) => {
    const relativePath = path.relative(ROOT_DIR, filePath);
    return EXCLUDE_PATTERNS.some((pattern) => {
      if (pattern.includes('*')) {
        const regex = new RegExp(pattern.replace(/\*/g, '.*'));
        return regex.test(relativePath);
      }
      return relativePath.startsWith(pattern) || relativePath.includes(`/${pattern}`);
    });
  };

  // Add directories and files
  const addDirectory = (dirPath, archivePath) => {
    if (!fs.existsSync(dirPath)) return;

    const items = fs.readdirSync(dirPath);
    for (const item of items) {
      const itemPath = path.join(dirPath, item);
      const itemArchivePath = path.join(archivePath, item);

      if (shouldExclude(itemPath)) continue;

      const stat = fs.statSync(itemPath);
      if (stat.isDirectory()) {
        addDirectory(itemPath, itemArchivePath);
      } else {
        archive.file(itemPath, { name: `${PLUGIN_NAME}/${itemArchivePath}` });
        console.log(`  + ${itemArchivePath}`);
      }
    }
  };

  console.log('Adding files to archive:\n');

  // Add main plugin file
  const mainPluginFile = path.join(ROOT_DIR, 'peanut-suite.php');
  if (fs.existsSync(mainPluginFile)) {
    archive.file(mainPluginFile, { name: `${PLUGIN_NAME}/peanut-suite.php` });
    console.log('  + peanut-suite.php');
  }

  // Add uninstall file
  const uninstallFile = path.join(ROOT_DIR, 'uninstall.php');
  if (fs.existsSync(uninstallFile)) {
    archive.file(uninstallFile, { name: `${PLUGIN_NAME}/uninstall.php` });
    console.log('  + uninstall.php');
  }

  // Add core directory
  addDirectory(path.join(ROOT_DIR, 'core'), 'core');

  // Add modules directory
  addDirectory(path.join(ROOT_DIR, 'modules'), 'modules');

  // Add built assets
  addDirectory(path.join(ROOT_DIR, 'assets'), 'assets');

  // Add languages directory if exists
  const langDir = path.join(ROOT_DIR, 'languages');
  if (fs.existsSync(langDir)) {
    addDirectory(langDir, 'languages');
  }

  // Finalize the archive
  await archive.finalize();
}

// Run the packaging
createPackage().catch((err) => {
  console.error('❌ Error creating package:', err);
  process.exit(1);
});
