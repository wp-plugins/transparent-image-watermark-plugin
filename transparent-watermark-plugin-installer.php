<?php


if( ! class_exists('MWA_Plugin_Install_List_Table')){


	require_once(ABSPATH . '/wp-admin/includes/admin.php');
	
	
	// create MyWebsiteAdvisor More Plugins Menu List Table 
	class MWA_Plugin_Install_List_Table extends WP_List_Table {
	
	
		function __construct(){
			
			parent::__construct();
			
			
			$columns = array(
				'name'        => _x( 'Name', 'plugin name' ),
				'version'     => __( 'Version' ),
				'rating'      => __( 'Rating' ),
				'description' => __( 'Description' ),
			);
			$hidden = array();
			$sortable = array();
	
	
			$this->_column_headers = array( $columns, $hidden, $sortable );
			
			

			
		}
	
	
	
		function ajax_user_can() {
			return current_user_can('install_plugins');
		}
	
	
		function prepare_items() {
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	
			global $tabs, $tab, $paged, $type, $term;
	
			wp_reset_vars( array( 'tab' ) );
			
			$tab = "search";
	
			$paged = $this->get_pagenum();
	
			$per_page = 40;
	
			// These are the tabs which are shown on the page
			$tabs = array();
			$tabs['dashboard'] = __( 'Search' );
			if ( 'search' == $tab )
				$tabs['search']	= __( 'MyWebsiteAdvisor.com' );
				
			$tabs['upload']    = __( 'Upload' );
			$tabs['featured']  = _x( 'Featured', 'Plugin Installer' );
			$tabs['popular']   = _x( 'Popular', 'Plugin Installer' );
			$tabs['new']       = _x( 'Newest', 'Plugin Installer' );
			$tabs['favorites'] = _x( 'Favorites', 'Plugin Installer' );
	
			$nonmenu_tabs = array( 'plugin-information' ); //Valid actions to perform which do not have a Menu item.
	
			$tabs = apply_filters( 'install_plugins_tabs', $tabs );
			$nonmenu_tabs = apply_filters( 'install_plugins_nonmenu_tabs', $nonmenu_tabs );
	
			// If a non-valid menu tab has been selected, And its not a non-menu action.
			if ( empty( $tab ) || ( !isset( $tabs[ $tab ] ) && !in_array( $tab, (array) $nonmenu_tabs ) ) )
				$tab = key( $tabs );
	
			$args = array( 'page' => $paged, 'per_page' => $per_page );
	
	
			switch ( $tab ) {
				case 'search':
					$type = isset( $_REQUEST['type'] ) ? stripslashes( $_REQUEST['type'] ) : 'term';
					$term = isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '';
	
					switch ( $type ) {
						case 'tag':
							$args['tag'] = sanitize_title_with_dashes( $term );
							break;
						case 'term':
							$args['search'] = $term;
							break;
						case 'author':
							$args['author'] = $term;
							break;
					}
	
					add_action( 'install_plugins_table_header', 'install_search_form', 10, 0 );
					break;
	
				case 'featured':
				case 'popular':
				case 'new':
					$args['browse'] = $tab;
					break;
	
				case 'favorites':
					$user = isset( $_GET['user'] ) ? stripslashes( $_GET['user'] ) : get_user_option( 'wporg_favorites' );
					update_user_meta( get_current_user_id(), 'wporg_favorites', $user );
					if ( $user )
						$args['user'] = $user;
					else
						$args = false;
	
					add_action( 'install_plugins_favorites', 'install_plugins_favorites_form', 9, 0 );
					break;
	
				default:
					$args = false;
			}
	
			if ( !$args )
				return;
				
				
			$args['author'] = "MyWebsiteAdvisor";	
	
	
	
			$api = plugins_api( 'query_plugins', $args );
	
			if ( is_wp_error( $api ) )
				wp_die( $api->get_error_message() . '</p> <p class="hide-if-no-js"><a href="#" onclick="document.location.reload(); return false;">' . __( 'Try again' ) . '</a>' );
	
			$this->items = $api->plugins;
	
			$this->set_pagination_args( array(
				'total_items' => $api->info['results'],
				'per_page' => $per_page,
			) );
		}
	
		function no_items() {
			_e( 'No plugins match your request.' );
		}
	
		function get_views() {
			global $tabs, $tab;
	
			$display_tabs = array();
			foreach ( (array) $tabs as $action => $text ) {
				$class = ( $action == $tab ) ? ' class="current"' : '';
				$href = self_admin_url('plugin-install.php?tab=' . $action);
				$display_tabs['plugin-install-'.$action] = "<a href='$href'$class>$text</a>";
			}
	
			return $display_tabs;
		}
	
		function display_tablenav( $which ) {
			if ( 'top' ==  $which ) { ?>
				<div class="tablenav top">
					<div class="alignright actions">
                    	
					</div>
					<?php $this->pagination( $which ); ?>
					<br class="clear" />
				</div>
			<?php } else { ?>
				<div class="tablenav bottom">
					<?php $this->pagination( $which ); ?>
					<br class="clear" />
				</div>
			<?php
			}
		}
	
		function get_table_classes() {
			extract( $this->_args );
	
			return array( 'widefat', $plural );
		}
	
		function get_columns() {
			return array(
				'name'        => _x( 'Name', 'plugin name' ),
				'version'     => __( 'Version' ),
				'rating'      => __( 'Rating' ),
				'description' => __( 'Description' ),
			);
		}
	
		function display_rows() {
			$plugins_allowedtags = array(
				'a' => array( 'href' => array(),'title' => array(), 'target' => array() ),
				'abbr' => array( 'title' => array() ),'acronym' => array( 'title' => array() ),
				'code' => array(), 'pre' => array(), 'em' => array(),'strong' => array(),
				'ul' => array(), 'ol' => array(), 'li' => array(), 'p' => array(), 'br' => array()
			);
	
			
			list( $columns, $hidden ) = $this->get_column_info();
		
		
			
	
			$style = array();
			foreach ( $columns as $column_name => $column_display_name ) {
				$style[ $column_name ] = in_array( $column_name, $hidden ) ? 'style="display:none;"' : '';
			}
	
			foreach ( (array) $this->items as $plugin ) {
				if ( is_object( $plugin ) )
					$plugin = (array) $plugin;
	
				$title = wp_kses( $plugin['name'], $plugins_allowedtags );
				//Limit description to 400char, and remove any HTML.
				$description = strip_tags( $plugin['description'] );
				if ( strlen( $description ) > 400 )
					$description = mb_substr( $description, 0, 400 ) . '&#8230;';
				//remove any trailing entities
				$description = preg_replace( '/&[^;\s]{0,6}$/', '', $description );
				//strip leading/trailing & multiple consecutive lines
				$description = trim( $description );
				$description = preg_replace( "|(\r?\n)+|", "\n", $description );
				//\n => <br>
				$description = nl2br( $description );
				$version = wp_kses( $plugin['version'], $plugins_allowedtags );
	
				$name = strip_tags( $title . ' ' . $version );
	
				$author = $plugin['author'];
				if ( ! empty( $plugin['author'] ) )
					$author = ' <cite>' . sprintf( __( 'By %s' ), $author ) . '.</cite>';
	
				$author = wp_kses( $author, $plugins_allowedtags );
	
				$action_links = array();
				$action_links[] = '<a href="' . self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] .
									'&amp;TB_iframe=true&amp;width=600&amp;height=550' ) . '" class="thickbox" title="' .
									esc_attr( sprintf( __( 'More information about %s' ), $name ) ) . '">' . __( 'Details' ) . '</a>';
	
				if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
					$status = install_plugin_install_status( $plugin );
	
					switch ( $status['status'] ) {
						case 'install':
							if ( $status['url'] )
								$action_links[] = '<a class="install-now" href="' . $status['url'] . '" title="' . esc_attr( sprintf( __( 'Install %s' ), $name ) ) . '">' . __( 'Install Now' ) . '</a>';
							break;
						case 'update_available':
							if ( $status['url'] )
								$action_links[] = '<a href="' . $status['url'] . '" title="' . esc_attr( sprintf( __( 'Update to version %s' ), $status['version'] ) ) . '">' . sprintf( __( 'Update Now' ), $status['version'] ) . '</a>';
							break;
						case 'latest_installed':
						case 'newer_installed':
							$action_links[] = '<span title="' . esc_attr__( 'This plugin is already installed and is up to date' ) . ' ">' . _x( 'Installed', 'plugin' ) . '</span>';
							break;
					}
				}
	
				$action_links = apply_filters( 'plugin_install_action_links', $action_links, $plugin );
			?>
			<tr>
				<td class="name column-name"<?php echo $style['name']; ?>><strong><?php echo $title; ?></strong>
					<div class="action-links"><?php if ( !empty( $action_links ) ) echo implode( ' | ', $action_links ); ?></div>
				</td>
				<td class="vers column-version"<?php echo $style['version']; ?>><?php echo $version; ?></td>
				<td class="vers column-rating"<?php echo $style['rating']; ?>>
					<div class="star-holder" title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $plugin['num_ratings'] ), number_format_i18n( $plugin['num_ratings'] ) ) ?>">
						<div class="star star-rating" style="width: <?php echo esc_attr( str_replace( ',', '.', $plugin['rating'] ) ); ?>px"></div>
					</div>
				</td>
				<td class="desc column-description"<?php echo $style['description']; ?>><?php echo $description, $author; ?></td>
			</tr>
			<?php
			}
		}
	}






	//generate MyWebsiteAdvisor More Plugins list
	function add_mwa_plugins_list(){
		
		require_once(ABSPATH . '/wp-admin/includes/admin.php');
		
		global $wp_list_table;
		$wp_list_table = new MWA_Plugin_Install_List_Table;
		$pagenum = $wp_list_table->get_pagenum();
		$wp_list_table->prepare_items();
		
		wp_enqueue_script( 'plugin-install' );
		
		$disable_plugin_installer_nonce = wp_create_nonce("mywebsiteadvisor-plugin-installer-menu-disable");	
		
		$plugin_installer_ajax = " <script>
			function update_mwa_display_plugin_installer_options(){
				  
						jQuery('#display_mwa_plugin_installer_label').text('Updating...');
						
						var menu_enabled = jQuery('#display_mywebsiteadvisor_plugin_installer_menu:checked').length > 0;
					  
						var ajax_data = {
							'checked': menu_enabled,
							'action': 'update_mwa_plugin_installer_menu_option', 
							'security': '$disable_plugin_installer_nonce'
						};
						  
						jQuery.ajax({
							type: 'POST',
							url:  ajaxurl,
							data: ajax_data,
							success: function(data){
								if(data == 'true'){
									jQuery('#display_mwa_plugin_installer_label').text('Enabled!');
								}
								if(data == 'false'){
									jQuery('#display_mwa_plugin_installer_label').text('Disabled!');
								}
								//alert(data);
								//location.reload();
							}
						});  
				  }</script>";
		

		$tab = "search";
		$title = __('MyWebsiteAdvisor.com Plugins');
		$parent_file = 'plugins.php';
		$paged = 1;
		
		if ( 'plugin-information' != $tab )
		add_thickbox();

		$body_id = $tab;

		do_action('install_plugins_pre_' . $tab);
		
		?>
        <div class="wrap">
        
        <?php echo display_mwa_social_media(); ?>     
        
        <?php screen_icon( 'plugins' ); ?>
        <h2><?php echo esc_html( $title ); ?></h2>
        
        <?php echo $plugin_installer_ajax; ?>
       	<p><label id='display_mwa_plugin_installer_label'><input type='checkbox' id='display_mywebsiteadvisor_plugin_installer_menu' name='display_mywebsiteadvisor_plugin_installer_menu' onclick="update_mwa_display_plugin_installer_options()" /> Check here to hide this page from the Plugins menu.</label></p>
        
        <?php $wp_list_table->display(); ?>
        
        </div>
        <?php
	
	}
	
	
	
	
	  
	function display_mwa_social_media(){
	
		$social = '<style>
	
		.fb_edge_widget_with_comment {
			position: absolute;
			top: 0px;
			right: 200px;
		}
		
		</style>
		
		<div  style="height:20px; vertical-align:top; width:45%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">
		
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, "script", "facebook-jssdk"));</script>
			
			<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
			
			
			<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		
		
		</div>';
		
		return $social;

	}	

	

	//add MyWebsiteAdvisor More Plugins Menu to admin menu system
	function build_mwa_plugins_menu(){ 
		$enabled = get_option('mywebsiteadvisor_pluigin_installer_menu_disable');
		if(!isset($enabled) || $enabled == 'true'){
		//if(!isset(get_option('mywebsiteadvisor_pluigin_installer_menu_disable'))){ 
			add_submenu_page( 'plugins.php', 'More Free Plugins from MyWebsiteAdvisor.com', 'MyWebsiteAdvisor', 'manage_options', 'MyWebsiteAdvisor',  'add_mwa_plugins_list' );
		}
	}
	
	
	// call the MyWebsiteAdvisor More Plugins Menu during admin_menu action
	add_action( 'admin_menu', 'build_mwa_plugins_menu' );




	//register ajax handler for disabling plugin installer menu
	add_action('wp_ajax_update_mwa_plugin_installer_menu_option',  'update_mwa_plugin_installer_menu_disable_option');



	function update_mwa_plugin_installer_menu_disable_option(){
		ob_clean();
		check_ajax_referer( 'mywebsiteadvisor-plugin-installer-menu-disable', 'security' );
		
		update_option('mywebsiteadvisor_pluigin_installer_menu_disable', $_POST['checked']);
	
		echo  $_POST['checked'];
		die();
	}
	


	

}




?>