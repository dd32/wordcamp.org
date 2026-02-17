/**
 * wp-env afterStart lifecycle script.
 *
 * Creates the multi-network structure that mirrors production:
 *   - Network 1: WordCamp (wordcamp.test)
 *   - Network 2: Events (events.wordpress.test)
 *   - Network 3: Campus (campus.wordpress.test)
 *
 * Idempotent — safe to run on every `wp-env start`.
 */

import { execSync } from 'node:child_process';

/**
 * Run a WP-CLI command inside the wp-env development container.
 *
 * @param {string} cmd WP-CLI command (without the `wp` prefix).
 * @return {string} Command output.
 */
function wp( cmd ) {
	return execSync( `npx wp-env run cli wp ${ cmd }`, {
		encoding: 'utf-8',
		stdio: [ 'pipe', 'pipe', 'pipe' ],
	} ).trim();
}

/**
 * Run a raw SQL query via WP-CLI.
 *
 * @param {string} sql SQL query.
 * @return {string} Query output.
 */
function sql( sql ) {
	return wp( `db query "${ sql.replace( /"/g, '\\"' ) }"` );
}

// Check if setup has already run by counting sites.
const siteCount = parseInt( wp( 'site list --format=count' ), 10 );

if ( siteCount > 2 ) {
	console.log( `Setup already complete (${ siteCount } sites exist). Skipping.` );
	process.exit( 0 );
}

console.log( 'Setting up WordCamp.org multi-network environment...' );

// ──────────────────────────────────────────────
// 1. Create additional networks via raw SQL.
//    wp-env creates network 1 automatically.
// ──────────────────────────────────────────────

console.log( 'Creating Events network (ID 2)...' );
sql( "INSERT IGNORE INTO wp_site (id, domain, path) VALUES (2, 'events.wordpress.test', '/')" );
sql( "INSERT IGNORE INTO wp_sitemeta (site_id, meta_key, meta_value) VALUES (2, 'site_name', 'Events (local)')" );
sql( "INSERT IGNORE INTO wp_sitemeta (site_id, meta_key, meta_value) VALUES (2, 'admin_email', 'admin@wordcamp.test')" );

console.log( 'Creating Campus network (ID 3)...' );
sql( "INSERT IGNORE INTO wp_site (id, domain, path) VALUES (3, 'campus.wordpress.test', '/')" );
sql( "INSERT IGNORE INTO wp_sitemeta (site_id, meta_key, meta_value) VALUES (3, 'site_name', 'Campus (local)')" );
sql( "INSERT IGNORE INTO wp_sitemeta (site_id, meta_key, meta_value) VALUES (3, 'admin_email', 'admin@wordcamp.test')" );

// ──────────────────────────────────────────────
// 2. Create sites with specific blog IDs.
//    Using SQL to guarantee exact IDs.
// ──────────────────────────────────────────────

const sites = [
	{
		blogId: 5,
		domain: 'central.wordcamp.test',
		path: '/',
		siteId: 1,
		title: 'WordCamp Central',
	},
	{
		blogId: 47,
		domain: 'events.wordpress.test',
		path: '/',
		siteId: 2,
		title: 'Events',
	},
	{
		blogId: 48,
		domain: 'events.wordpress.test',
		path: '/rome/2024/training/',
		siteId: 2,
		title: 'WordPress Training Day Rome 2024',
	},
	{
		blogId: 49,
		domain: 'narnia.wordcamp.test',
		path: '/2026/',
		siteId: 1,
		title: 'WordCamp Narnia 2026',
	},
];

for ( const site of sites ) {
	console.log( `Creating site ${ site.domain }${ site.path } (blog_id ${ site.blogId })...` );

	// Check if the blog_id already exists.
	const exists = sql(
		`SELECT blog_id FROM wp_blogs WHERE blog_id = ${ site.blogId }`
	);

	if ( exists.includes( String( site.blogId ) ) ) {
		console.log( `  Blog ID ${ site.blogId } already exists, skipping.` );
		continue;
	}

	const now = new Date().toISOString().slice( 0, 19 ).replace( 'T', ' ' );

	sql(
		`INSERT INTO wp_blogs (blog_id, site_id, domain, path, registered, last_updated, public) VALUES (${ site.blogId }, ${ site.siteId }, '${ site.domain }', '${ site.path }', '${ now }', '${ now }', 1)`
	);

	// Create the site's tables by switching to it and running an option insert.
	// WP will auto-create tables when the blog is accessed.
	const prefix = `wp_${ site.blogId }_`;

	// Create minimal options table.
	sql(
		`CREATE TABLE IF NOT EXISTS ${ prefix }options LIKE wp_options`
	);
	sql(
		`INSERT IGNORE INTO ${ prefix }options (option_name, option_value, autoload) VALUES ('siteurl', 'http://${ site.domain }${ site.path }', 'yes')`
	);
	sql(
		`INSERT IGNORE INTO ${ prefix }options (option_name, option_value, autoload) VALUES ('home', 'http://${ site.domain }${ site.path }', 'yes')`
	);
	sql(
		`INSERT IGNORE INTO ${ prefix }options (option_name, option_value, autoload) VALUES ('blogname', '${ site.title }', 'yes')`
	);

	// Create other required tables.
	const coreTables = [
		'posts', 'postmeta', 'comments', 'commentmeta', 'links',
		'terms', 'termmeta', 'term_taxonomy', 'term_relationships',
	];

	for ( const table of coreTables ) {
		sql( `CREATE TABLE IF NOT EXISTS ${ prefix }${ table } LIKE wp_${ table }` );
	}

	console.log( `  Created site: ${ site.title }` );
}

// ──────────────────────────────────────────────
// 3. Set admin password to 'password'.
// ──────────────────────────────────────────────

console.log( 'Setting admin password...' );
try {
	wp( 'user update admin --user_pass=password --skip-email' );
} catch {
	// User may not be named 'admin'; try ID 1.
	wp( 'user update 1 --user_pass=password --skip-email' );
}

// ──────────────────────────────────────────────
// 4. Create placeholder build directories for
//    mu-plugins/blocks to prevent errors.
// ──────────────────────────────────────────────

console.log( 'Creating placeholder build directories...' );
try {
	execSync( 'npx wp-env run cli -- mkdir -p /var/www/html/wp-content/mu-plugins/blocks/build', {
		encoding: 'utf-8',
		stdio: 'pipe',
	} );
} catch {
	// Directory may already exist.
}

console.log( 'Setup complete!' );
console.log( '' );
console.log( 'Sites available:' );
console.log( '  http://central.wordcamp.test/' );
console.log( '  http://narnia.wordcamp.test/2026/' );
console.log( '  http://events.wordpress.test/' );
console.log( '  http://events.wordpress.test/rome/2024/training/' );
console.log( '' );
console.log( 'Login: admin / password' );
