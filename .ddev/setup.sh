#!/bin/bash
#
# Idempotent DDEV post-start setup script for WordCamp.org.
# Runs inside the web container after `ddev start`.
#

set -e

PROJECT_ROOT="/var/www/html"
WP_DIR="$PROJECT_ROOT/public_html/mu"
WP_CONFIG="$PROJECT_ROOT/public_html/wp-config.php"
SEED_SQL="$PROJECT_ROOT/.docker/data/wordcamp_dev.sql"

# 1. Download WordPress core if not present.
if [ ! -f "$WP_DIR/wp-includes/version.php" ]; then
    echo "Downloading WordPress core..."
    mkdir -p "$WP_DIR"
    wp core download --path="$WP_DIR" --skip-content
fi

# 2. Copy wp-config.php if not present.
if [ ! -f "$WP_CONFIG" ]; then
    echo "Copying wp-config.php..."
    cp "$PROJECT_ROOT/.ddev/wp-config-ddev.php" "$WP_CONFIG"
fi

# 3. Import seed database if tables don't exist.
if ! wp db check --path="$WP_DIR" 2>/dev/null | grep -q "wc_options"; then
    if [ -f "$SEED_SQL" ]; then
        echo "Importing seed database..."
        wp db import "$SEED_SQL" --path="$WP_DIR"
    else
        echo "Warning: Seed database not found at $SEED_SQL"
    fi
fi

# 4. Create placeholder build files for mu-plugins/blocks.
BLOCKS_DIR="$PROJECT_ROOT/public_html/wp-content/mu-plugins/blocks/source"
if [ -d "$BLOCKS_DIR" ]; then
    for dir in "$BLOCKS_DIR"/*/; do
        block_name=$(basename "$dir")
        build_dir="$PROJECT_ROOT/public_html/wp-content/mu-plugins/blocks/build/$block_name"
        if [ ! -d "$build_dir" ]; then
            mkdir -p "$build_dir"
            echo '(()=>{"use strict";})();' > "$build_dir/index.js"
            echo '{"apiVersion":2}' > "$build_dir/block.json"
        fi
    done
fi

# 5. Install WP test suite for PHPUnit.
WP_TESTS_DIR="/tmp/wp/wordpress-tests-lib"
if [ ! -d "$WP_TESTS_DIR/includes" ]; then
    echo "Installing WordPress test suite..."
    bash "$PROJECT_ROOT/.docker/bin/install-wp-tests.sh" wcorg_test db db db latest
fi

echo "DDEV setup complete!"
