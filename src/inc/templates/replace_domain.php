<?php
/**
 * Template for displaying replace domain page
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
		<a class="nav-tab " href="<?php echo admin_url() ?>tools.php?page=db_backup"><?php esc_html_e( 'Backup Database', 'search-and-replace' ); ?></a>
		<a class="nav-tab nav-tab-active" href="<?php echo admin_url() ?>tools.php?page=replace_domain"><?php esc_html_e( 'Replace Domain/URL', 'search-and-replace' ); ?></a>
		<a class="nav-tab " href="<?php echo admin_url() ?>tools.php?page=inpsyde_search_replace"><?php esc_html_e( 'Search and Replace', 'search-and-replace' ); ?></a>
		<a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=sql_import"><?php esc_html_e( 'Import SQL file', 'search-and-replace' ); ?></a>
		<a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=credits"><?php esc_html_e( 'Credits', 'search-and-replace' ); ?></a>
	</h2>

	<p><?php esc_html_e( 'If you want to migrate your site to another domain, enter the new URL in the field "Replace with" and create a backup of your database by clicking "Do Replace Domain/Url".',
	             'search-and-replace' ); ?> </p>

	<form action="" method="post">

		<table class="form-table">
			<tbody>

			<tr>
				<th><label for="search"><strong><?php esc_html_e( 'Search for: ', 'search-and-replace' ); ?></strong></label></th>
				<td><input id="search" type="text" name="search" value="<?php echo get_site_url(); ?>" /></td>
			</tr>
			<tr>
				<th><label for="replace"><strong><?php esc_html_e( 'Replace with: ', 'search-and-replace' ); ?></strong></label></th>
				<td><input id="replace" type="url" name="replace" placeholder="<?php esc_attr_e( 'New URL' ) ?>" /></td>
			</tr>
			<tr>
				<th><label for="change_db_prefix"><strong><?php esc_html_e( 'Change database prefix', 'search-and-replace' ); ?></strong></label></th>
				<td><input id ="change_db_prefix" type="checkbox" name="change_db_prefix"  /></td>
			</tr>
			<tr class="disabled">
				<th><label for="current_db_prefix"><strong><?php esc_html_e( 'Current prefix: ', 'search-and-replace' ); ?></strong></label></th>
				<td><?php echo $this->dbm->get_base_prefix(); ?></td>
			</tr>
			<tr class="maybe_disabled disabled">
				<th><label for="new_db_prefix"><strong><?php esc_html_e( 'New prefix: ', 'search-and-replace' ); ?></strong></label></th>
				<td><input id="new_db_prefix" type="text" name="new_db_prefix" disabled placeholder="<?php esc_attr_e( 'New database prefix', 'search-and-replace' ) ?>" /></td>
			</tr>
			</tbody>
		</table>
		<?php $this->show_submit_button(); ?>
	</form>