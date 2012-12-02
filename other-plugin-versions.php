<?php
/*
Plugin Name: Other Plugin Versions
Plugin URI: 
Description: Allows you to install older versions of any plugin hosted in the repository with a single click
Version: 0.1
Author: Pippin Williamson and Japh Thompson
Author URI: 
License: 
License URI: 
*/

function pw_plugin_action_links( $action_links, $plugin ) {

	$status = install_plugin_install_status( $plugin );

	switch ( $status['status'] ) {
		case 'install':
			$action_links[] = '<a href="' . self_admin_url( 'plugin-install.php?tab=plugin-versions&amp;plugin=' . $plugin['slug'] .
								'&amp;TB_iframe=true&amp;width=600&amp;height=550' ) . '" class="thickbox" title="' .
								esc_attr( sprintf( __( 'Other Versions of %s' ), $plugin['name'] ) ) . '">' . __( 'Other Versions' ) . '</a>';
			break;
	}
	return $action_links;

}
add_filter( 'plugin_install_action_links', 'pw_plugin_action_links', 10, 2);


/**
 * Display plugin information in dialog box form.
 *
 */
function pw_install_plugin_other_versions_information() {
	global $tab;

	$api = plugins_api('plugin_information', array('slug' => stripslashes( $_REQUEST['plugin'] ) ));

	if ( is_wp_error($api) )
		wp_die($api);

	$plugins_allowedtags = array(
		'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ),
		'abbr' => array( 'title' => array() ), 'acronym' => array( 'title' => array() ),
		'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
		'div' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
		'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
		'img' => array( 'src' => array(), 'class' => array(), 'alt' => array() )
	);

	$plugins_section_titles = array(
		'description'  => _x('Description',  'Plugin installer section title'),
		'installation' => _x('Installation', 'Plugin installer section title'),
		'faq'          => _x('FAQ',          'Plugin installer section title'),
		'screenshots'  => _x('Screenshots',  'Plugin installer section title'),
		'changelog'    => _x('Changelog',    'Plugin installer section title'),
		'other_notes'  => _x('Other Notes',  'Plugin installer section title')
	);

	//Sanitize HTML
	foreach ( (array)$api->sections as $section_name => $content )
		$api->sections[$section_name] = wp_kses($content, $plugins_allowedtags);
	foreach ( array( 'version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
		if ( isset( $api->$key ) )
			$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
	}

	$section = isset($_REQUEST['section']) ? stripslashes( $_REQUEST['section'] ) : 'description'; //Default to the Description tab, Do not translate, API returns English.
	if ( empty($section) || ! isset($api->sections[ $section ]) )
		$section = array_shift( $section_titles = array_keys((array)$api->sections) );

	iframe_header( __('Plugin Install') );
	echo "<div id='$tab-header'>\n";
	echo "<ul id='sidemenu'>\n";
	foreach ( (array)$api->sections as $section_name => $content ) {

		if ( isset( $plugins_section_titles[ $section_name ] ) )
			$title = $plugins_section_titles[ $section_name ];
		else
			$title = ucwords( str_replace( '_', ' ', $section_name ) );

		$class = ( $section_name == $section ) ? ' class="current"' : '';
		$href = add_query_arg( array('tab' => $tab, 'section' => $section_name) );
		$href = esc_url($href);
		$san_section = esc_attr( $section_name );
		echo "\t<li><a name='$san_section' href='$href' $class>$title</a></li>\n";
	}
	echo "</ul>\n";
	echo "</div>\n";
	?>
	<div class="alignright fyi">
		<?php if ( ! empty($api->download_link) && ( current_user_can('install_plugins') || current_user_can('update_plugins') ) ) : ?>
		<p class="action-button">
		<?php
		$status = install_plugin_install_status($api);
		switch ( $status['status'] ) {
			case 'install':
				if ( $status['url'] )
					echo '<a href="' . $status['url'] . '" target="_parent">' . __('Install Now') . '</a>';
				break;
			case 'update_available':
				if ( $status['url'] )
					echo '<a href="' . $status['url'] . '" target="_parent">' . __('Install Update Now') .'</a>';
				break;
			case 'newer_installed':
				echo '<a>' . sprintf(__('Newer Version (%s) Installed'), $status['version']) . '</a>';
				break;
			case 'latest_installed':
				echo '<a>' . __('Latest Version Installed') . '</a>';
				break;
		}
		?>
		</p>
		<?php endif; ?>
		<h2 class="mainheader"><?php /* translators: For Your Information */ _e('FYI') ?></h2>
		<ul>
<?php if ( ! empty($api->version) ) : ?>
			<li><strong><?php _e('Version:') ?></strong> <?php echo $api->version ?></li>
<?php endif; if ( ! empty($api->author) ) : ?>
			<li><strong><?php _e('Author:') ?></strong> <?php echo links_add_target($api->author, '_blank') ?></li>
<?php endif; if ( ! empty($api->last_updated) ) : ?>
			<li><strong><?php _e('Last Updated:') ?></strong> <span title="<?php echo $api->last_updated ?>"><?php
							printf( __('%s ago'), human_time_diff(strtotime($api->last_updated)) ) ?></span></li>
<?php endif; if ( ! empty($api->requires) ) : ?>
			<li><strong><?php _e('Requires WordPress Version:') ?></strong> <?php printf(__('%s or higher'), $api->requires) ?></li>
<?php endif; if ( ! empty($api->tested) ) : ?>
			<li><strong><?php _e('Compatible up to:') ?></strong> <?php echo $api->tested ?></li>
<?php endif; if ( ! empty($api->downloaded) ) : ?>
			<li><strong><?php _e('Downloaded:') ?></strong> <?php printf(_n('%s time', '%s times', $api->downloaded), number_format_i18n($api->downloaded)) ?></li>
<?php endif; if ( ! empty($api->slug) && empty($api->external) ) : ?>
			<li><a target="_blank" href="http://wordpress.org/extend/plugins/<?php echo $api->slug ?>/"><?php _e('WordPress.org Plugin Page &#187;') ?></a></li>
<?php endif; if ( ! empty($api->homepage) ) : ?>
			<li><a target="_blank" href="<?php echo $api->homepage ?>"><?php _e('Plugin Homepage &#187;') ?></a></li>
<?php endif; ?>
		</ul>
		<?php if ( ! empty($api->rating) ) : ?>
		<h2><?php _e('Average Rating') ?></h2>
		<div class="star-holder" title="<?php printf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings)); ?>">
			<div class="star star-rating" style="width: <?php echo esc_attr( str_replace( ',', '.', $api->rating ) ); ?>px"></div>
		</div>
		<small><?php printf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings)); ?></small>
		<?php endif; ?>
	</div>
	<div id="section-holder" class="wrap">
	<?php
		if ( !empty($api->tested) && version_compare( substr($GLOBALS['wp_version'], 0, strlen($api->tested)), $api->tested, '>') )
			echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been tested</strong> with your current version of WordPress.') . '</p></div>';

		else if ( !empty($api->requires) && version_compare( substr($GLOBALS['wp_version'], 0, strlen($api->requires)), $api->requires, '<') )
			echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been marked as compatible</strong> with your version of WordPress.') . '</p></div>';

		foreach ( (array)$api->sections as $section_name => $content ) {

			if ( isset( $plugins_section_titles[ $section_name ] ) )
				$title = $plugins_section_titles[ $section_name ];
			else
				$title = ucwords( str_replace( '_', ' ', $section_name ) );

			$content = links_add_base_url($content, 'http://wordpress.org/extend/plugins/' . $api->slug . '/');
			$content = links_add_target($content, '_blank');

			$san_section = esc_attr( $section_name );

			$display = ( $section_name == $section ) ? 'block' : 'none';

			echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
			echo "\t\t<h2 class='long-header'>$title</h2>";
			echo $content;
			echo "\t</div>\n";
		}
	echo "</div>\n";

	iframe_footer();
	exit;
}
add_action('install_plugins_pre_plugin-versions', 'pw_install_plugin_other_versions_information');



function pw_add_other_versions_tab( $tabs ) {
	$tabs['plugin-versions'] = __( 'Other Versions', 'wp_ov' );
	return $tabs; 
}
add_filter( 'install_plugins_tabs', 'pw_add_other_versions_tab' );