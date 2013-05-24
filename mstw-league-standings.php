<?php
/*
Plugin Name: League Standings
Plugin URI: http://shoalsummitsolutions.com
Description: The League Standings Plugin defines a custom type - Scheduled Games - for use in the MySportTeamWebite framework. Generations a game schedule (html table) using a shortcode.
Version: 1.0
Author: Mark O'Donnell
Author URI: http://shoalsummitsolutions.com
*/

/*
League Standings (Wordpress Plugin)
Copyright (C) 2013 Mark O'Donnell
Contact me at http://shoalsummitsolutions.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/


	/*---------------------------------------------------------------
	 * Load the mstw-utility-functions if necessary
	 *---------------------------------------------------------------*/
	
	// ----------------------------------------------------------------
	// If an admin, load the admin functions (once)
		if ( is_admin( ) ) {
			// we're in wp-admin
			require_once ( dirname( __FILE__ ) . '/includes/mstw-league-standings-admin.php' );
		}
		
	// ----------------------------------------------------------------
	// Set up localization
		add_action( 'init', 'mstw_ls_load_localization' );
		
		function mstw_ls_load_localization( ) {
			load_plugin_textdomain( 'mstw-loc-domain', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		} // end custom_theme_setup
		
	// ----------------------------------------------------------------
	// Deactivate, request upgrade, and exit if WP version is not right
	add_action( 'admin_init', 'mstw_ls_requires_wp_ver' );

	// ----------------------------------------------------------------
	function mstw_ls_requires_wp_ver() {
		global $wp_version;
		$plugin = plugin_basename( __FILE__ );
		$plugin_data = get_plugin_data( __FILE__, false );

		if ( version_compare($wp_version, "3.5", "<" ) ) {
			if( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				wp_die( "'" . $plugin_data['Name'] . "' requires WordPress 3.5 or higher, and has been deactivated! 
					Please upgrade WordPress and try again.<br /><br />Back to <a href='" . admin_url() . "'>WordPress admin</a>." );
			}
		}
	}

	// --------------------------------------------------------------------------------------
	// Set-up Action and Filter Hooks for the Settings on the admin side
	// --------------------------------------------------------------------------------------
	register_activation_hook( __FILE__, 'mstw_ls_set_defaults' );
	register_uninstall_hook( __FILE__, 'mstw_ls_delete_plugin_options' );
	//add_action('admin_init', 'mstw_ls_register_settings' );
	// add_action('admin_menu', 'mstw_ls_add_options_page'); Code is still in place
	//add_filter( 'plugin_action_links', 'mstw_plugin_action_links', 10, 2 );

	// --------------------------------------------------------------------------------------
	// Callback for: register_uninstall_hook(__FILE__, 'mstw_ls_delete_plugin_options')
	// --------------------------------------------------------------------------------------
	// It runs when the user deactivates AND DELETES the plugin. 
	// It deletes the plugin options DB entry, which is an array storing all the plugin options
	// --------------------------------------------------------------------------------------
	function mstw_ls_delete_plugin_options( ) {
		delete_option( 'mstw_ls_options' );
	}

	/* Queue up the necessary CSS */
	/* add_action( 'wp_head', 'mstw_ls_enqueue_styles' ); */
	add_action( 'wp_enqueue_scripts', 'mstw_ls_enqueue_styles' );

	// ---------------------------------------------------------------------
	// Callback for: add_action( 'wp_enqueue_scripts', 'mstw_ls_enqueue_styles' );
	// ---------------------------------------------------------------------
	// Loads the Cascading Style Sheet for the [mstw-ls-table] shortcode
	// ---------------------------------------------------------------------
	function mstw_ls_enqueue_styles ( ) {
		/* Find the full path to the css file */
		$mstw_ls_style_url = plugins_url( '/css/mstw-ls-styles.css', __FILE__ );
		$mstw_ls_style_file = WP_PLUGIN_DIR . '/league-standings/css/mstw-ls-styles.css';
		
		wp_register_style( 'mstw_ls_style', plugins_url( '/css/mstw-ls-styles.css', __FILE__ ) );
		
		//echo 'file url: ' . $mstw_ls_style_url . "\n";
		//echo 'file name: ' . $mstw_ls_style_file . "\n";
		
		/* If stylesheet exists, enqueue the style */
		if ( file_exists( $mstw_ls_style_file ) ) {	
			wp_enqueue_style( 'mstw_ls_style' );			
			
		} 
	}

	// --------------------------------------------------------------------------------------
	// LEAGUE STANDINGS CUSTOM POST TYPE STUFF
	// --------------------------------------------------------------------------------------
	// Set-up Action Hooks & Filters
	// ACTIONS
	// 		'init'											mstw_ls_register_post_type
	//		'add_metaboxes'									mstw_ls_add_meta
	//		'save_posts'									mstw_ls_save_meta
	//		'manage_game_schedule_posts_custom_column'		mstw_ls_manage_columns

	// FILTERS
	// 		'manage_edit-game_schedule_columns'				mstw_ls_edit_columns
	//		'post_row_actions'								mstw_ls_remove_the_view
	//		
	// --------------------------------------------------------------------------------------

	// Register the Teams post type
	add_action( 'init', 'mstw_ls_register_post_type' );
	
	// Add the custom Leagues taxonomy ... will act like tags	
	add_action( 'init', 'mstw_ls_register_taxonomy' );

	function mstw_ls_register_taxonomy() {
		
		$labels = array( 
					'name' 				   		   => __( 'Leagues', 'mstw-loc-domain' ),
					'singular_name' 			   =>  __( 'League', 'mstw-loc-domain' ),
					'search_items' 				   => __( 'Search Leagues', 'mstw-loc-domain' ),
					'popular_items' 			   => __( 'Popular Leagues', 'mstw-loc-domain' ),
					'all_items' 				   => __( 'All Leagues', 'mstw-loc-domain' ),
					'parent_item' 				   => null,
					'parent_item_colon' 		   => null,
					'edit_item' 				   => __( 'Edit League', 'mstw-loc-domain' ), 
					'update_item'                  => __( 'Update League', 'mstw-loc-domain' ),
					'add_new_item'                 => __( 'Add New League', 'mstw-loc-domain' ),
					'new_item_name'                => __( 'New League Name', 'mstw-loc-domain' ),
					'separate_items_with_commas'   => __( 'Separate Leagues with commas', 'mstw-loc-domain' ),
					'add_or_remove_items'          => __( 'Add or Remove Leagues', 'mstw-loc-domain' ),
					'choose_from_most_used'        => __( 'Choose from the most used Leagues', 'mstw-loc-domain' ),
					'not_found'                    => __( 'No Leagues found', 'mstw-loc-domain' ),
					'menu_name'                    => __( 'Leagues', 'mstw-loc-domain' ),
				  ); 
				  
		$args = array(
					'hierarchical'        => false,
					'labels'              => $labels,
					'show_ui'             => true,
					'show_admin_column'   => true,
					'query_var'           => true,
					'rewrite'             => array( 'slug' => 'league' )
				  );
		
		register_taxonomy( 'leagues', 'league_team', $args );
		
		/*if ( register_taxonomy_for_object_type( 'leagues', 'league_team' ) === false ) {
			echo "<p> Oops, can't register taxonomy leagues for type league_team. </p>";
			die;
		}*/
		
	}
	// --------------------------------------------------------------------------------------
	function mstw_ls_register_post_type( ) {
		/* Set up the arguments for the Game Schedules post type */
		$args = array(
			'public' => true,
			'query_var' => 'league_team',
			'rewrite' => array(
				'slug' => 'league-team',
				'with_front' => false,
			),
			'supports' => array(
				'title'
			),
			'labels' => array(
				'name' => __( 'MSTW Teams', 'mstw-loc-domain' ),
				'singular_name' => __( 'Team', 'mstw-loc-domain' ),
				'all_items' => __( 'All Teams', 'mstw-loc-domain' ),
				'add_new' => __( 'Add New Team', 'mstw-loc-domain' ),
				'add_new_item' => __( 'Add Team', 'mstw-loc-domain' ),
				'edit_item' => __( 'Edit Team', 'mstw-loc-domain' ),
				'new_item' => __( 'New Team', 'mstw-loc-domain' ),
				//'View Game Schedule' needs a custom page template that is of no value.
				'view_item' => null, 
				'search_items' => __( 'Search Teams', 'mstw-loc-domain' ),
				'not_found' => __( 'No Teams Found', 'mstw-loc-domain' ),
				'not_found_in_trash' => __( 'No Teams Found In Trash', 'mstw-loc-domain' ),
				),
			    'taxonomies' => array( 'leagues' ),
			);
			
		register_post_type( 'league_team', $args);
	}

	// --------------------------------------------------------------------------------------
	add_shortcode( 'mstw_ls_table', 'mstw_ls_shortcode_handler' );
	// --------------------------------------------------------------------------------------
	// Add the shortcode handler, which will create the League Standings table on the user side.
	// Handles the shortcode parameters, if there were any, 
	// then calls mstw_ls_build_standings() to create the output
	// --------------------------------------------------------------------------------------
	function mstw_ls_shortcode_handler( $atts ){
		// NOTE: Want to set these based on plugin settings/options
		$options = get_option( 'mstw_ls_options' );

		$order_by = mstw_set_atts_default( $options, 'ls_order_by', 'percent' ); // percent|points|rank
		$show_rank = mstw_set_atts_default( $options, 'ls_show_rank', 0 );
		$rank_label = mstw_set_atts_default( $options, 'ls_rank_label', __( 'Rank', 'mstw-loc-domain' ) );
		$team_format = mstw_set_atts_default( $options, 'ls_team_format', 'team' ); //team|mascot|both
		$team_label = mstw_set_atts_default( $options, 'ls_team_label', __( 'Team', 'mstw-loc-domain' ) );
		$show_team_links = mstw_set_atts_default( $options, 'ls_show_team_links', 0 );
		$show_games_played = mstw_set_atts_default( $options, 'ls_show_games_played', 0 );
		$games_played_label =  mstw_set_atts_default( $options, 'ls_games_played_label', __( 'GP', 'mstw-loc-domain' ) );
		$show_wins = mstw_set_atts_default( $options, 'ls_show_wins', 0 );
		$wins_label =  mstw_set_atts_default( $options, 'ls_wins_label', __( 'W', 'mstw-loc-domain' ) );
		$show_losses = mstw_set_atts_default( $options, 'ls_show_losses', 0 );
		$losses_label =  mstw_set_atts_default( $options, 'ls_losses_label', __( 'L', 'mstw-loc-domain' ) );
		$show_ties = mstw_set_atts_default( $options, 'ls_show_ties', 0 );
		$ties_label =  mstw_set_atts_default( $options, 'ls_ties_label', __( 'T', 'mstw-loc-domain' ) );
		$show_other = mstw_set_atts_default( $options, 'ls_show_other', 0 );
		$other_label =  mstw_set_atts_default( $options, 'ls_other_label', __( 'OTW', 'mstw-loc-domain' ) );
		$show_percent = mstw_set_atts_default( $options, 'ls_show_percent', 0 );
		$percent_label =  mstw_set_atts_default( $options, 'ls_percent_label', __( 'Percent', 'mstw-loc-domain' ) );
		$show_points = mstw_set_atts_default( $options, 'ls_show_points', 0 );
		$points_label =  mstw_set_atts_default( $options, 'ls_points_label', __( 'PTS', 'mstw-loc-domain' ) );
		$show_games_behind = mstw_set_atts_default( $options, 'ls_show_games_behind', 0 );
		$games_behind_label =  mstw_set_atts_default( $options, 'ls_games_behind_label', __( 'GB', 'mstw-loc-domain' ) );
		$show_goals_for = mstw_set_atts_default( $options, 'ls_show_goals_for', 0 );
		$goals_for_label =  mstw_set_atts_default( $options, 'ls_goals_for_label', __( 'GF', 'mstw-loc-domain' ) );
		$show_goals_against = mstw_set_atts_default( $options, 'ls_show_goals_against', 0 );
		$goals_against_label =  mstw_set_atts_default( $options, 'ls_goals_against_label', __( 'GA', 'mstw-loc-domain' ) );
		$show_goals_diff = mstw_set_atts_default( $options, 'ls_show_goals_diff', 0 );
		$goals_diff_label =  mstw_set_atts_default( $options, 'ls_goals_diff_label', __( 'GD', 'mstw-loc-domain' ) );
		$show_last_10 = mstw_set_atts_default( $options, 'ls_show_last_10', 0 );
		$last_10_label =  mstw_set_atts_default( $options, 'ls_last_10_label', __( 'L10', 'mstw-loc-domain' ) );
		$show_last_5 = mstw_set_atts_default( $options, 'ls_show_last_5', 0 );
		$last_5_label =  mstw_set_atts_default( $options, 'ls_last_5_label', __( 'L5', 'mstw-loc-domain' ) );
		$show_streak = mstw_set_atts_default( $options, 'ls_show_streak', 0 );
		$streak_label =  mstw_set_atts_default( $options, 'ls_streak_label', __( 'Streak', 'mstw-loc-domain' ) );
		$show_home = mstw_set_atts_default( $options, 'ls_show_home', 0 );
		$home_label =  mstw_set_atts_default( $options, 'ls_home_label', __( 'Home', 'mstw-loc-domain' ) );
		$show_away = mstw_set_atts_default( $options, 'ls_show_away', 0 );
		$away_label =  mstw_set_atts_default( $options, 'ls_away_label', __( 'Away', 'mstw-loc-domain' ) );
		$show_division = mstw_set_atts_default( $options, 'ls_show_division', 0 );
		$division_label =  mstw_set_atts_default( $options, 'ls_division_label', __( 'Div', 'mstw-loc-domain' ) );
		$show_conference = mstw_set_atts_default( $options, 'ls_show_conference', 0 );
		$conference_label =  mstw_set_atts_default( $options, 'ls_conference_label', __( 'Conf', 'mstw-loc-domain' ) );

		//echo '<p>Games Played Label: ' . $games_played_label . '</p>';
		
		$attribs = shortcode_atts( array(
					'league_id' => 'default', // You gotta provide a league ID
					//'order_by' => 'percent', //$options['ls_order_by'], 
					'order_by' => $order_by,
					
					'show_rank' => $show_rank,
					'rank_label' => $rank_label,
					
					'team_format' => $team_format, 
					'team_label' => $team_label,
					'show_team_links' => $show_team_links, 
					// Add team logo later ... maybe
					
					'show_games_played' => $show_games_played,
					'games_played_label' => $games_played_label,
					'show_wins' => $show_wins,
					'wins_label' => $wins_label,
					'show_losses' => $show_losses,
					'losses_label' => $losses_label,
					'show_ties' => $show_ties,
					'ties_label' => $ties_label,
					'show_other' => $show_other,
					'other_label' => $other_label,
					
					'show_percent' => $show_percent,
					'percent_label' => $percent_label,
					'show_points' => $show_points,
					'points_label' => $points_label,
					'show_games_behind' => $show_games_behind,
					'games_behind_label' => $games_behind_label,
					'show_goals_for' => $show_goals_for,
					'goals_for_label' => $goals_for_label,
					'show_goals_against' => $show_goals_against,
					'goals_against_label' => $goals_against_label,
					'show_goals_diff' => $show_goals_diff,
					'goals_diff_label' => $goals_diff_label,
					'show_last_10' => $show_last_10,
					'last_10_label' => $last_10_label,
					'show_last_5' => $show_last_5,
					'last_5_label' => $last_5_label,
					'show_streak' => $show_streak,
					'streak_label' => $streak_label,
					'show_home' => $show_home,
					'home_label' => $home_label,
					'show_away' => $show_away,
					'away_label' => $away_label,
					'show_division' => $show_division, 
					'division_label' => $division_label,
					'show_conference' => $show_conference,
					'conference_label' => $conference_label,
					),
					$atts );
					
		//wp_parse_args( $atts, get_option( 'mstw_ls_settings' );
		
		$mstw_ls_standings_table = mstw_ls_build_standings( $attribs );
		
		/*$show_rank, $rank_label, $team_format, $show_games_played, $games_played_label, $show_wins, $wins_label, $show_losses, $losses_label, $show_ties, $ties_label, $show_other, $other_label, $show_percent, $percent_label, $show_points, $points_label, $show_games_behind, $games_behind_label, $show_goals_for, $goals_for_label, $show_goals_against, $goals_against_label, $show_last_10, $last_10_label, $show_last_5, $last_5_label, $show_streak, $streak_label, $show_home, $home_label, $show_away, $away_label, $show_division, $division_label, $show_conference, $conference_label */
		
		return $mstw_ls_standings_table;
	}

	function mstw_set_atts_default ( $options, $setting, $default ) {
	
		return ( isset( $options[$setting] ) and !empty( $options[$setting] ) ) ?  $options[$setting] : $default;
	
	}
	
	/*--------------------------------------------------------------------------------------
	 * Called by:	mstw_ls_shortcode_handler
	 * Builds the League Standings table as a string (to replace the [shortcode] in a page or post.
	 * Loops through the League Standings custom posts and formats them into a pretty table based on 
	 * the attributes provided (see list in mstw_ls_shortcode_handler)
	 *--------------------------------------------------------------------------------------*/
	function mstw_ls_build_standings( $attribs ) {
		// Extract the attributes into corresponding variables
		extract( $attribs );
		
		// Should no longer need this, move to mstw_ls_shortcode_handler
		// $options = get_option( 'mstw_ls_options' );
		
		// Need to figure out order by!
		switch ( $order_by ) {
			case 'points':
				$sort_key = 'mstw_ls_points';
				$sort_order = "DESC";
				break;
			case 'rank':
				$sort_key = 'mstw_ls_rank';
				$sort_order = "ASC";
				break;
			default:
				$sort_key = 'mstw_ls_percent';
				$sort_order = "DESC";
				break;
		}
		
		// Get the league's teams
		
		/* First get all the games for the specified schedule id.
		$game_posts = get_posts( array( 'numberposts' => -1,
							  'post_type' => 'scheduled_games',
							  'meta_query' => array(
												array(
													'key' => '_mstw_ls_sched_id',
													'value' => $sched,
													'compare' => '='
												)
											),
							  
							  'orderby' => 'meta_value', 
							  'meta_key' => '_mstw_ls_unix_dtg',
							  'order' => 'ASC' 
							));*/
	
		$teams = get_posts( array( 'numberposts' => -1,
								  'post_type' => 'league_team',
								  'leagues' => $league_id, 		//only posts from custom taxonomy == $league_id
								  'orderby' => 'meta_value', 
								  'meta_key' => $sort_key,
								  'order' => $sort_order, 
								) );
								
		// Build the standings table
		if ( $teams ) {
			// Start with the table header
			//$output = '<p>$sort_key= ' . $sort_key . '</p>';
			$output = '<table class="mstw-ls-table">'; 
			$output .= '<thead class="mstw-ls-table-head"><tr>';
			
			if ( $show_rank )
				$output .= '<th>'. $rank_label . '</th>';
			// Team column is always there
			$output .= '<th>'. $team_label . '</th>';
			if ( $show_games_played )
				$output .= '<th>'. $games_played_label . '</th>';
			if ( $show_wins )
				$output .= '<th>'. $wins_label . '</th>';
			if ( $show_losses )
				$output .= '<th>'. $losses_label . '</th>';
			if ( $show_ties )
				$output .= '<th>'. $ties_label . '</th>';
			if ( $show_other )
				$output .= '<th>'. $other_label . '</th>';
			if ( $show_percent )
				$output .= '<th>'. $percent_label . '</th>';
			if ( $show_points )
				$output .= '<th>'. $points_label . '</th>';
			if ( $show_games_behind )
				$output .= '<th>'. $games_behind_label . '</th>';
			if ( $show_goals_for )
				$output .= '<th>'. $goals_for_label . '</th>';
			if ( $show_goals_against )
				$output .= '<th>'. $goals_against_label . '</th>';
			if ( $show_goals_diff )
				$output .= '<th>'. $goals_diff_label . '</th>';
			if ( $show_last_10 )
				$output .= '<th>'. $last_10_label . '</th>';
			if ( $show_last_5 )
				$output .= '<th>'. $last_5_label . '</th>';				
			if ( $show_streak )
				$output .= '<th>'. $streak_label . '</th>';
			if ( $show_home )
				$output .= '<th>'. $home_label . '</th>';
			if ( $show_away )
				$output .= '<th>'. $away_label . '</th>';
			if ( $show_division )
				$output .= '<th>'. $division_label . '</th>';
			if ( $show_conference )
				$output .= '<th>'. $conference_label . '</th>';	
				
			// done with the table header
			$output .= '</tr></thead>';
			   
			// Keeps track of even and odd rows. Start with row 1 = odd.
			$even_and_odd = array('even', 'odd');
			$row_cnt = 1; 
		
			// Loop through the team (posts) and build the table rows
			foreach( $teams as $team ){
				// set up some housekeeping to make styling in the loop easier
				$even_or_odd_row = $even_and_odd[$row_cnt]; 
				$row_class = 'mstw-ls-' . $even_or_odd_row;
				
				$row_tr = '<tr class="' . $row_class . '">';
				$row_td = '<td>'; 
				
				// create the row
				$row_string = $row_tr;			
				
				if ( $show_rank )
					$row_string .= $row_td. get_post_meta( $team->ID, 'mstw_ls_rank', true ) . '</td>';
					
				// Team column is always there. Just need to format it correctly.
				// Default to name 
				if ( $team_format == 'mascot' )
					$name_string = get_post_meta( $team->ID, 'mstw_ls_mascot', true );
				else if ( $team_format == 'both' ) 
					$name_string = get_post_meta( $team->ID, 'mstw_ls_name', true ) . ' ' . get_post_meta( $team->ID, 'mstw_ls_mascot', true );
				else // default to name
					$name_string = get_post_meta( $team->ID, 'mstw_ls_name', true );
				
				if ( $show_team_links )
					$name_string = '<a href="' . get_post_meta( $team->ID, 'mstw_ls_team_link', true ) . '" target="_blank" >' . $name_string . '</a>';
				$row_string .= $row_td . $name_string . '</td>';
				
				if ( $show_games_played )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_games_played', true ) . '</td>';
				if ( $show_wins )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_wins', true ) . '</td>';
				if ( $show_losses )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_losses', true ) . '</td>';
				if ( $show_ties )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_ties', true ) . '</td>';
				if ( $show_other )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_other', true ) . '</td>';
				if ( $show_percent )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_percent', true ) . '</td>';
				if ( $show_points )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_points', true ) . '</td>';
				if ( $show_games_behind )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_games_behind', true ) . '</td>';
				if ( $show_goals_for )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_goals_for', true ) . '</td>';
				if ( $show_goals_against )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_goals_against', true ) . '</td>';
				if ($show_goals_diff ) {
					$diff = get_post_meta( $team->ID, 'mstw_ls_goals_for', true ) - get_post_meta( $team->ID, 'mstw_ls_goals_against', true );
					$row_string .= $row_td . $diff . '</td>';
				}
				if ( $show_last_10 )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_last_10', true ) . '</td>';
				if ( $show_last_5 )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_last_5', true ) . '</td>';				
				if ( $show_streak )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_streak', true ) . '</td>';
				if ( $show_home )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_home', true ) . '</td>';
				if ( $show_away )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_away', true ) . '</td>';
				if ( $show_division )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_division', true ) . '</td>';
				if ( $show_conference )
					$row_string .= $row_td . get_post_meta( $team->ID, 'mstw_ls_conference', true ) . '</td>';	
			
				//Finish the row
				$output .= $row_string . '</tr>';
				
				$row_cnt = 1- $row_cnt;  // Switch styles for even-odd rows
				
			} // end of foreach teams
			
			// We're done. Close the table
			$output .= '</table>';
		}
		else { 		// No posts(teams) were found
			$output =  '<h3>' . __( 'Sorry, no teams were found in league ', 'mstw-loc-domain' ) . $league_id . '.</h3>';
		}
		return $output;

	} /*End function mstw_ls_build_standings*/
							
	
/* ------------------------------------------------------------------------
 * League Standings Widget (want to move to separate include file)
 *	mstw_ls_standings_widget - displays the league standings as a simple table
 *-----------------------------------------------------------------------*/

// ----------------------------------------------------------------
// First, use 'widgets_init' hook to register the widgets
// ----------------------------------------------------------------

// add_action( 'widgets_init', 'mstw_ls_register_widgets' );

 //register our widgets
function mstw_ls_register_widgets() {
    register_widget( 'mstw_ls_standings_widget' );
}

/*--------------------------------------------------------------------
 * mstw_ls_standings_widget
 *	- displays a simple standings table
 *------------------------------------------------------------------*/
class mstw_ls_standings_widget extends WP_Widget {

    //process the new widget
    function mstw_ls_standings_widget( ) {
        $widget_ops = array( 
			'classname' => 'mstw_ls_standings_widget_class', 
			'description' => 'Display a team schedule.' 
			); 
        $this->WP_Widget( 'mstw_ls_standings_widget', 'Game Schedule', $widget_ops );
    }
 
     //build the widget settings form
    function form($instance) {
        $defaults = array(	'sched_title' => 'Schedule', 
							'sched_id' => '1', 
							'sched_yr' => date('Y'),
							'sched_start_date' => 0, 
							'sched_end_date' => strtotime( '2999-12-31'), 
							'sched_max_to_show' => -1, 
							); 
							
        $instance = wp_parse_args( (array) $instance, $defaults );
		$sched_title = $instance['sched_title'];
		$sched_id = $instance['sched_id'];
		$sched_start_date = $instance['sched_start_date'];
		$sched_end_date = $instance['sched_end_date'];
		$sched_max_to_show = $instance['sched_max_to_show'];
		
        ?>
        <p>Schedule Title: <input class="widefat" name="<?php echo $this->get_field_name( 'sched_title' ); ?>"  
            					type="text" value="<?php echo esc_attr( $sched_title ); ?>" /></p>
        <p>Schedule ID: <input class="widefat" name="<?php echo $this->get_field_name( 'sched_id' ); ?>"  
        						type="text" value="<?php echo esc_attr( $sched_id ); ?>" /></p>
		<p>The dates below MUST be in the format yyyy-mm-dd hh:mm. (You can omit the hh:mm for 00:00.) Otherwise, you can expect unexpected results.</p>
		<p>Display Start Date: <input class="widefat" name="<?php echo $this->get_field_name( 'sched_start_date' ); ?>"	type="text" value="<?php echo date('Y-m-d H:i', (int)esc_attr( $sched_start_date ) ); ?>" />
		</p>
        <p>Display End Date: <input class="widefat" name="<?php echo $this->get_field_name( 'sched_end_date' ); ?>"  type="text" value="<?php echo date('Y-m-d H:i', (int)esc_attr( $sched_end_date ) ); ?>" />
		</p>
		<p>Maximum # of games to show (-1 to show all games): <input class="widefat" name="<?php echo $this->get_field_name( 'sched_max_to_show' ); ?>" type="text" value="<?php echo esc_attr( $sched_max_to_show ); ?>" />
		</p>
        <?php
    }
 
    //save the widget settings
    function update($new_instance, $old_instance) {
		
        $instance = $old_instance;
		
		$instance['sched_title'] = strip_tags( $new_instance['sched_title'] );

		$instance['sched_id'] = strip_tags( $new_instance['sched_id'] );
		
		$instance['sched_start_date'] = strtotime( strip_tags( $new_instance['sched_start_date'] ) );
		
		$instance['sched_end_date'] = strtotime( strip_tags( $new_instance['sched_end_date'] ) );
		
		$instance['sched_max_to_show'] = strip_tags( $new_instance['sched_max_to_show'] );
 
        return $instance;
		
    }
 
 /*--------------------------------------------------------------------
 * displays the widget
 *------------------------------------------------------------------*/	
 
	function widget( $args, $instance ) {

		//Date column of the widget's table
		$options = get_option('mstw_ls_options');
		$tab_widget_dtg_format = $options['ls_tab_widget_dtg_format'];
		if ( $tab_widget_dtg_format == '' ) {
			$tab_widget_dtg_format =  'Y-m-d';
		}
		
		// $args holds the global theme variables, such as $before_widget
		extract( $args );
		
		echo $before_widget;
		
		$title = apply_filters( 'widget_title', $instance['sched_title'] );
		
		// Get the parameters for get_posts() below
		$sched_id = $instance['sched_id'];
		$first_dtg = $instance['sched_start_date'];
		$last_dtg = $instance['sched_end_date'];
		$max_to_show = $instance['sched_max_to_show']; 
		
		// show the widget title, if there is one
		if( !empty( $title ) ) {
			echo  $before_title . $title . $after_title;
		}
		
		// Get the game posts for $sched_id 
		$posts = get_posts(array( 'numberposts' => $max_to_show,
								  'relation' => 'AND',
							  	  'post_type' => 'scheduled_games',
							  	  'meta_query' => array(
												array(
													'key' => '_mstw_ls_standings_id', //**
													'value' => $sched_id,
													'compare' => '='
												),
												array(
													'key' => '_mstw_ls_unix_dtg',
													'value' => array( $first_dtg, $last_dtg),
													'type' => 'NUMERIC',
													'compare' => 'BETWEEN'
												)
											),						  
							  	  'orderby' => 'meta_value', 
							  	  'meta_key' => '_mstw_ls_unix_dtg',
							      'order' => 'ASC' 
							));						
	
   	 	// Make table of posts
		if($posts) {
					
			// Start with the table header
        	$output = ''; ?>
        
        	<table class="mstw-ls-sw-tab">
        	<thead class="mstw-ls-sw-tab-head"><tr>
            	<th><?php _e( 'DATE', 'mstw-loc-domain' ); ?></th>
            	<th><?php _e( 'OPPONENT', 'mstw-loc-domain' ); ?></th>	
			</tr></thead>
        
			<?php
			// Loop through the posts and make the rows
			$even_and_odd = array('even', 'odd');
			$row_cnt = 1; // Keeps track of even and odd rows. Start with row 1 = odd.
		
			foreach( $posts as $post ) {
				// set up some housekeeping to make styling in the loop easier
				$is_home_game = get_post_meta($post->ID, '_mstw_ls_home_game', true );
				$even_or_odd_row = $even_and_odd[$row_cnt]; 
				$row_class = 'mstw-ls-sw-' . $even_or_odd_row;
				if ( $is_home_game == 'home' ) 
					$row_class = $row_class . ' mstw-ls-sw-home';
			
				$row_tr = '<tr class="' . $row_class . '">';
				//$row_tr = '<tr>';
				$row_td = '<td>'; 
				//$row_td = '<td class="' . $row_class . '">';
			
				// create the row
				$row_string = $row_tr;		
			
				// column 1: Build the game date in a specified format			
				$date_string = mstw_date_loc( $tab_widget_dtg_format, (int)get_post_meta( $post->ID, '_mstw_ls_unix_dtg', true ) );
			
				$row_string = $row_string. $row_td . $date_string . '</td>';
			
				// column 2: create the opponent entry
				$opponent = get_post_meta( $post->ID, '_mstw_ls_opponent', true);
				
				if ( $is_home_game != 'home' ) {
					$opponent = '@' . $opponent;
				}
				
				$row_string =  $row_string . $row_td . $opponent . '</td>';
			
				/*
				// Might want to add this at some point
				// column 4: create the time/results entry
				if ( get_post_meta( $post->ID, '_mstw_ls_game_result', true) != '' ) {
					$row_string =  $row_string . $row_td . get_post_meta( $post->ID, '_mstw_ls_game_result', true) . '</td>';
				}	
				else {	
					$row_string =  $row_string . $row_td . get_post_meta( $post->ID, '_mstw_ls_game_time', true) . '</td>';
				}
				*/
		
				echo $row_string . '</tr>';
			
				$row_cnt = 1- $row_cnt;  // Get the styles right
			
			} // end of foreach post

			echo '</table>';
		}
		else { // No posts were found

			_e( 'No Scheduled Games Found', 'mstw-loc-domain' );

		} // End of if ($posts)
		
		echo $after_widget;
	
	} // end of function widget( )
} // End of class mstw_ls_standings_widget
?>