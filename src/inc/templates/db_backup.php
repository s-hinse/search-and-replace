<?php
/**
 * Template for displaying sql export page
 */
// Prevent direct access.
if ( ! defined( 'INSR_DIR' ) ) {
	echo "Hi there!  I'm just a part of plugin, not much I can do when called directly.";
	exit;
}
?>
<div class="wrap">

	<h1 id="title"><?php esc_html_e( 'Search & Replace', 'search-and-replace' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" href="<?php
		echo admin_url() ?>tools.php?page=db_export"><?php
			esc_html_e( 'Backup Database',
			            'search-and-replace' ); ?></a>
		<a class="nav-tab " href="<?php
		echo admin_url() ?>tools.php?page=replace_domain"><?php
			esc_html_e( 'Replace Domain/URL',
			            'search-and-replace' ); ?></a>
		<a class="nav-tab " href="<?php
		echo admin_url() ?>tools.php?page=inpsyde_search_replace"><?php
			esc_html_e( 'Search and Replace',
			            'search-and-replace' ); ?></a>
		<a class="nav-tab" href="<?php
		echo admin_url() ?>tools.php?page=sql_import"><?php
			esc_html_e( 'Import SQL file',
			            'search-and-replace' ); ?></a>
		<a class="nav-tab" href="<?php
		echo admin_url() ?>tools.php?page=credits"><?php
			esc_html_e( 'Credits',
			            'search-and-replace' ); ?></a>
	</h2>

	<p><?php esc_html_e(
			'Create a backup of your database by clicking "Create SQL File".',
			'search-and-replace' ); ?>
	</p>

	<form action="" method="post">
		<?php $this->show_submit_button(); ?>
	</form>