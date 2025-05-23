<?php
/**
 * Header template
 */
?><!DOCTYPE html>
<!--[if IE 6]>
<html id="ie6" class="old-ie" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" class="old-ie" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" class="old-ie" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php
	/*
	 * Always have wp_head() just before the closing </head>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to add elements to <head> such
	 * as styles, scripts, and meta tags.
	 */
	wp_head();
?>

<script type="text/javascript" src="//use.typekit.com/yqt7hkl.js"></script>
<script type="text/javascript">try{Typekit.load();}catch(e){}</script>

</head>

<body <?php body_class(); ?>>

<div id="header" class="group">
	<div id="masthead" class="group">
		<?php /*  Allow screen readers / text browsers to skip the navigation menu and get right to the good stuff */ ?>
		<a href="#<?php echo is_front_page() ? 'wc-hero-panel' : 'content'; ?>" class="skip-link screen-reader-text">
			<?php esc_html_e( 'Skip to content', 'twentyten' ); ?>
		</a>

		<?php $use_heading_tag = is_home() || is_front_page(); ?>
		<?php echo $use_heading_tag ? '<h1 id="site-title">' : '<div id="site-title">'; ?>
			<span>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
			</span>
		<?php echo $use_heading_tag ? '</h1>' : '</div>'; ?>

		<div id="access" role="navigation">
			<?php /* Our navigation menu.  If one isn't filled out, wp_nav_menu falls back to wp_page_menu.  The menu assiged to the primary position is the one used.  If none is assigned, the menu with the lowest ID is used.  */ ?>
			<button class="wc-primary-button menu-toggle">
				<?php esc_html_e( 'Primary Menu', 'twentyten' ); ?>
			</button>
			<?php wp_nav_menu( array( 'container_class' => 'menu-header', 'theme_location' => 'primary' ) ); ?>
		</div><!-- #access -->
	</div><!-- #masthead -->
</div><!-- #header -->


<?php if ( is_front_page() ) : ?>
<div id="wc-hero-panel">
	<div class="wc-hero-wrap group">
		<div class="wc-hero-intro">
			<h2>WordPress Events: connect, learn & shape the future of the open web.</h2>
			<p>WordCamps and WordPress Events are where innovation meets opportunity. Boost your career by connecting with a diverse community of users, professionals, and entrepreneurs. Together, we’re shaping the future of the open web.</p>
			<p class="wc-hero-actions">
				<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="wc-hero-learnmore">Learn More</a> or
				<a href="<?php echo esc_url( home_url( '/schedule/' ) ); ?>" class="wc-primary-button">Find a WordCamp</a>
			</p>
		</div><!-- .wc-hero-intro -->

		<div id="home-hero-slideshow" class="wc-hero-image cycle-me">
			<?php
				// Get image attachments from page Home.
				$attachments = get_posts( array(
					'post_type'      => 'attachment',
					'posts_per_page' => 10,
					'post_parent'    => get_the_ID(),
					'post_mime_type' => 'image',
					'orderby'        => 'date',
					'order'          => 'DESC',
				) );
			?>

			<?php foreach ( $attachments as $image ) : ?>

				<?php
					$image_src = wp_get_attachment_image_src( $image->ID, 'wccentral-thumbnail-hero' );
					if ( ! $image_src ) continue;
					list( $src, $width, $height ) = $image_src;

					$image_alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
					$image_title = get_the_title( $image->ID );
				?>
				<div class="wc-hero-entry" style="position: absolute;">
					<img src="<?php echo esc_url( $src ); ?>" width="<?php echo absint( $width ); ?>" height="<?php echo absint( $height ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" title="<?php echo esc_attr( $image_title ); ?>" />
					<?php if ( ! empty( $image->post_excerpt ) ) : ?>
					<span class="wc-hero-caption"><?php echo esc_html( $image->post_excerpt ); ?></span>
					<?php endif; ?>
				</div>

			<?php endforeach; ?>

		</div><!-- .wc-hero-image -->

		<div class="wc-hero-mailinglist">
			<?php if ( WordCamp_Central_Theme::can_subscribe() ) : ?>
			<div class="wc-hero-mailinglist-container">

				<?php if ( WordCamp_Central_Theme::get_subscription_status() == 'success' ) : ?>

					<p class="wc-hero-mailinglist-subscription-status">Thanks for subscribing! <br /> Please check your inbox to confirm your subscription.</p>
				<?php elseif ( WordCamp_Central_Theme::get_subscription_status() == 'already' ) : ?>
					<p class="wc-hero-mailinglist-subscription-status">Looks like you're already subscribed.</p>
				<?php elseif ( WordCamp_Central_Theme::get_subscription_status() == 'invalid_email' ) : ?>
					<p class="wc-hero-mailinglist-subscription-status">The e-mail you have entered doesn't seem to be valid!</p>
				<?php elseif ( WordCamp_Central_Theme::get_subscription_status() == 'error' ) : ?>
					<p class="wc-hero-mailinglist-subscription-status">Something went wrong; please try again later.</p>
				<?php elseif ( WordCamp_Central_Theme::get_subscription_status() == false ) : ?>
					<h3>Join the <strong>Mailing List</strong></h3>

					<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="POST">
						<input type="hidden" name="wccentral-form-action" value="subscribe" />
						<input type="text" class="wc-hero-mailinglist-email" placeholder="Enter your email address" name="wccentral-subscribe-email" />
						<input type="submit" class="wc-hero-mailinglist-submit" value="Go" />
					</form>
				<?php endif; // get_subscription_status ?>

			</div>
			<?php endif; // can_subscribe ?>
		</div><!-- #wc-hero-mailinglist -->

	</div>
</div><!-- #wc-hero-panel -->
<?php endif; // is_front_page ?>

<div id="wrapper" class="hfeed">

	<div id="main" class="group">
