<?php
/*
 *	This is the admin portion of the MSTW League Standings Plugin
 *	It is loaded conditioned on is_admin() in mstw-league-standings.php 
 */

/*  Copyright 2013  Mark O'Donnell  (email : mark@shoalsummitsolutions.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// ----------------------------------------------------------------
/* Load the mstw-utility-functions if necessary
	if ( !function_exists( 'mstw_sanitize_hex_color' ) ) {
		require_once 'mstw-utility-functions.php';
	}

	*/
	
// ----------------------------------------------------------------
// enqueue the color picker scripts and styles 
/*$just_playing = 'Just playing';
add_action( 'admin_enqueue_scripts', 'mstw_ls_add_styles' );	
function mstw_ls_add_styles( ) {
    //Access the global $wp_version variable to see which version of WordPress is installed.
    global $wp_version;
	global $just_playing;
    
    //If the WordPress version is greater than or equal to 3.5, then load the new WordPress color picker.
    if ( 3.5 <= $wp_version ){
      //Both the necessary css and javascript have been registered already by WordPress, so all we have to do is load them with their handle.
      wp_enqueue_style( 'wp-color-picker' );
      wp_enqueue_script( 'wp-color-picker' );
    }
    //If the WordPress version is less than 3.5 load the older farbtasic color picker.
    else {
      //As with wp-color-picker the necessary css and javascript have been registered already by WordPress, so all we have to do is load them with their handle.
      wp_enqueue_style( 'farbtastic' );
      wp_enqueue_script( 'farbtastic' );
    }
    
    //Load our custom javascript file
    wp_enqueue_script( 'wp-color-picker-settings', plugin_dir_url( ) . 'game-schedules/js/color-settings.js' );
	$just_playing = 'Enqueueing: ' . plugin_dir_url(  ) . 'game-schedules/js/color-settings.js';
  }
*/	
	
	// ----------------------------------------------------------------
	// Remove Quick Edit Menu	
		add_filter( 'post_row_actions', 'mstw_ls_remove_quick_edit', 10, 2 );

		function mstw_ls_remove_quick_edit( $actions, $post ) {
			if( $post->post_type == 'league_team' ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			return $actions;
		}
	
	// Remove the Bulk Actions pull-down
	add_filter( 'bulk_actions-' . 'edit-league_team', '__return_empty_array' );	
	
	// Add a filter the All Teams screen based on the Leagues Taxonomy
	add_action( 'restrict_manage_posts', 'mstw_leagues_filter' );
	
	function mstw_leagues_filter( ) {
	
		// only display these taxonomy filters on desired custom post_type listings
		global $typenow;
		if ( $typenow == 'league_team' ) {

			// create an array of taxonomy slugs you want to filter by - if you want to retrieve all taxonomies, could use get_taxonomies() to build the list
			$filters = array('leagues');

			foreach ($filters as $tax_slug) {
				// retrieve the taxonomy object
				$tax_obj = get_taxonomy($tax_slug);
				$tax_name = $tax_obj->labels->name;
				// retrieve array of term objects per taxonomy
				$terms = get_terms($tax_slug);

				// output html for taxonomy dropdown filter
				echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
				echo "<option value=''>Show All $tax_name</option>";
				foreach ($terms as $term) {
					// output each select option line, check against the last $_GET to show the current option selected
					echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
				}
				echo "</select>";
			}
		}
		
		/*global $typenow;
		global $wp_query;
		if ( $typenow == 'league_team' ) {
			$taxonomy = 'leagues';
			$leagues_taxonomy = get_taxonomy( $taxonomy );
			wp_dropdown_categories( array(
				'show_option_all' =>  __( "Show All {$leagues_taxonomy->label}" ),
				'taxonomy'        =>  $taxonomy,
				'name'            =>  'leagues',
				'orderby'         =>  'name',
				'selected'        =>  $wp_query->query['term'],
				'hierarchical'    =>  false,
				//'depth'           =>  3,
				'show_count'      =>  true, // Show # teams in parens
				'hide_empty'      =>  false, // Show leagues w/o teams
			));
		} */
	}
	
	// Now query (and display) Teams based on the Leagues filter
	//add_filter( 'parse_query', 'mstw_league_id_to_term' );
	
	function mstw_league_id_to_term( $query ) {
		global $pagenow;
		$qv = &$query->query_vars;
		if ($pagenow=='edit.php' &&
				isset( $qv['taxonomy'] ) && $qv['taxonomy']=='leagues' &&
				isset( $qv['term'] ) && is_numeric( $qv['term']) ) {
			$term = get_term_by( 'id', $qv['term'], 'leagues' );
			$qv['term'] = $term->slug;
		}
		echo "<p>qv['term']: " . $qv['term'] . '</p>';
		echo '<p>term->slug: ' . $qv['term'] . '</p>';
		echo '<p>$pagenow: ' . $pagenow . '</p>';
		echo "<p>qv['taxonomy']: " . $qv['taxonomy'] . '</p>';
		print_r( $qv );
		
	}
		
	// ----------------------------------------------------------------
	// Create the meta box for the League Standing custom post type (league_team)
		add_action( 'add_meta_boxes', 'mstw_ls_add_meta_box' );

		function mstw_ls_add_meta_box( ) {
			add_meta_box('mstw-ls-meta', 'Team', 'mstw_ls_create_ui', 
							'league_team', 'normal', 'high' );
		}

	// ----------------------------------------------------------------
	// Creates the UI form for entering a Team in the Admin page
	// Callback for: add_meta_box('mstw-ls-meta', 'Team', ... )

	function mstw_ls_create_ui( $team ) {
									  				  
		// Retrieve the metadata values if they exist
		$rank = get_post_meta( $team->ID, 'mstw_ls_rank', true );
		$name = get_post_meta( $team->ID, 'mstw_ls_name', true );
		$team_link = get_post_meta( $team->ID, 'mstw_ls_team_link', true );
		$mascot = get_post_meta( $team->ID, 'mstw_ls_mascot', true );
		$games_played = get_post_meta( $team->ID, 'mstw_ls_games_played', true );
		$wins = get_post_meta( $team->ID, 'mstw_ls_wins', true );
		$losses = get_post_meta( $team->ID, 'mstw_ls_losses', true );
		$ties = get_post_meta( $team->ID, 'mstw_ls_ties', true );
		$other = get_post_meta( $team->ID, 'mstw_ls_other', true );
		$percent = get_post_meta( $team->ID, 'mstw_ls_percent', true );
		$points = get_post_meta( $team->ID, 'mstw_ls_points', true );
		$games_behind = get_post_meta( $team->ID, 'mstw_ls_games_behind', true );
		$goals_for = get_post_meta( $team->ID, 'mstw_ls_goals_for', true );
		$goals_against = get_post_meta( $team->ID, 'mstw_ls_goals_against', true );
		$last_10 = get_post_meta( $team->ID, 'mstw_ls_last_10', true );
		$last_5 = get_post_meta( $team->ID, 'mstw_ls_last_5', true );
		$streak = get_post_meta( $team->ID, 'mstw_ls_streak', true );
		$home = get_post_meta( $team->ID, 'mstw_ls_home', true );
		$away = get_post_meta( $team->ID, 'mstw_ls_away', true );
		$division = get_post_meta( $team->ID, 'mstw_ls_division', true );
		$conference = get_post_meta( $team->ID, 'mstw_ls_conference', true );
		?>	
		
	   <table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_rank" ><?php _e( 'Rank (or Place):', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_rank"
				value="<?php echo esc_attr( $rank ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_name" ><?php _e( 'Team Name:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_name"
				value="<?php echo esc_attr( $name ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_team_link" ><?php _e( 'Team Link:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="128" size="20" name="mstw_ls_team_link"
				value="<?php echo esc_url( $team_link ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_mascot" ><?php _e( 'Team Mascot:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_mascot"
				value="<?php echo esc_attr( $mascot ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_games_played" ><?php _e( 'Games Played:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_games_played"
				value="<?php echo esc_attr( $games_played ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_wins" ><?php _e( 'Wins:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_wins"
				value="<?php echo esc_attr( $wins ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_losses" ><?php _e( 'Losses:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_losses"
				value="<?php echo esc_attr( $losses ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_ties" ><?php _e( 'Ties:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_ties"
				value="<?php echo esc_attr( $ties ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_other" ><?php _e( 'Other:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_other"
				value="<?php echo esc_attr( $other ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_percent" ><?php _e( 'Win Percentage (INFO ONLY):', 'mstw-loc-domain' ); ?></label></th>
			<td><?php echo esc_attr( $percent )?></td>
			<td><?php _e( 'Win percentage is calculated from Wins, Losses and Ties. See documentation for formula and how to modify it.', 'mstw-loc-domain' ); ?></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_points" ><?php _e( 'Points:', 'mstw-loc-domain' ); ?> </label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_points"
				value="<?php echo esc_attr( $points ); ?>"/></td>
			<td><?php _e( 'Points are calculated differently in different leagues, so right now you have to enter them. [This could change in a future release.]', 'mstw-loc-domain' ); ?></td>
		</tr>
		
	  
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_games_behind" ><?php _e( 'Games Behind:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_games_behind"
				value="<?php echo esc_attr( $games_behind ); ?>"/></td>
				<td><?php _e( 'Right now you have to enter Games Behind if you want it. It will be calculated for you in a future release.', 'mstw-loc-domain' ); ?></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_goals_for" ><?php _e( 'Goals For:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_goals_for"
				value="<?php echo esc_attr( $goals_for ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_goals_against" ><?php _e( 'Goals Against:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_goals_against"
				value="<?php echo esc_attr( $goals_against ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_last_5" ><?php _e( 'Record in Last 5 Games:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_last_5"
				value="<?php echo esc_attr( $last_5 ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_last_5" ><?php _e( 'Record in Last 10 Games:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_last_10"
				value="<?php echo esc_attr( $last_10 ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_streak" ><?php _e( 'Current Streak:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_streak"
				value="<?php echo esc_attr( $streak ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_home" ><?php _e( 'Home Record:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_home"
				value="<?php echo esc_attr( $home ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_away" ><?php _e( 'Road Record:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_away"
				value="<?php echo esc_attr( $away ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_division" ><?php _e( 'Record in Division:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_division"
				value="<?php echo esc_attr( $division ); ?>"/></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="mstw_ls_conference" ><?php _e( 'Record in Conference:', 'mstw-loc-domain' ); ?></label></th>
			<td><input maxlength="32" size="20" name="mstw_ls_conference"
				value="<?php echo esc_attr( $conference ); ?>"/></td>
		</tr>
		 
		<?php /* MAY WANT THIS sOMEDAY FOR TAXONOMY
		$plugin_active = 'Inactive';
		if( is_plugin_active('game-locations/mstw-game-locations.php') ) { 
			$plugin_active = 'Active';
			$locations = get_posts(array( 'numberposts' => -1,
							  'post_type' => 'game_locations',
							  'orderby' => 'title',
							  'order' => 'ASC' 
							));						
	
			if( $locations ) {
				echo '<tr valign="top">';
				echo '<th>Select Location from Game Locations:</th>';
				echo "<td><select id='mstw_ls_gl_location' name='mstw_ls_gl_location'>";
				foreach( $locations as $loc ) {
					$selected = ( $mstw_ls_gl_location == $loc->ID ) ? 'selected="selected"' : '';
					echo "<option value='" . $loc->ID . "'" . $selected . ">" . get_the_title( $loc->ID ) . "</option>";
				}
				echo "</select></td>";
				echo "<td>Note: this setting requires that the Game Locations plugin is activated. It is preferred to using the custom location and link settings below.</td>";
				echo "</tr>";
				
			}
		} //End: if (is_plugin_active) 
		else {
			echo '<tr valign="top">';
			echo '<th scope="row">Game Locations Plugin:</th>';
			echo "<td>Please activate the <a href='http://wordpress.org/extend/plugins/game-locations/' title='Game Locations Plugin'>Game Locations Plugin</a> to use this feature. It makes life a lot simpler for 'normal' Game Schedules use.</td>";
			echo '</tr>';
		} */ ?>
		
		</table>
		
	<?php        	
	}

	// ----------------------------------------------------------------
	// Save the Team Meta Data
	// ----------------------------------------------------------------
	add_action( 'save_post', 'mstw_ls_save_meta_data' );

	function mstw_ls_save_meta_data( $post_id ) {
		
		update_post_meta( $post_id, 'mstw_ls_rank', 
				strip_tags( $_POST['mstw_ls_rank'] ) );
		
		update_post_meta( $post_id, 'mstw_ls_name', 
				strip_tags( $_POST['mstw_ls_name'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_team_link', 
				esc_url_raw( $_POST['mstw_ls_team_link'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_mascot', 
				strip_tags( $_POST['mstw_ls_mascot'] ) );
		
		update_post_meta( $post_id, 'mstw_ls_games_played', 
				strip_tags( $_POST['mstw_ls_games_played'] ) );
		
		update_post_meta( $post_id, 'mstw_ls_wins', 
				strip_tags( $_POST['mstw_ls_wins'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_losses', 
				strip_tags( $_POST['mstw_ls_losses'] ) );
		
		update_post_meta( $post_id, 'mstw_ls_ties', 
				strip_tags( $_POST['mstw_ls_ties'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_other', 
				strip_tags( $_POST['mstw_ls_other'] ) );
		// calculate win percentage - note that this doesn't work for the NHL
		$games = intval( $_POST['mstw_ls_wins'] ) + intval( $_POST['mstw_ls_losses'] ) + intval( $_POST['mstw_ls_ties'] );
		if ( $games >0 )
			$percent = round( (intval( $_POST['mstw_ls_wins'] ) + .5*intval( $_POST['mstw_ls_ties'] ))/$games, 3 );
		else
			$percent = ''; //No games played
			
		update_post_meta( $post_id, 'mstw_ls_percent', 
				$percent ); 
				
		update_post_meta( $post_id, 'mstw_ls_points', 
				strip_tags( $_POST['mstw_ls_points'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_games_behind', 
				strip_tags( $_POST['mstw_ls_games_behind'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_goals_for', 
				strip_tags( $_POST['mstw_ls_goals_for'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_goals_against', 
				strip_tags( $_POST['mstw_ls_goals_against'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_last_5', 
				strip_tags( $_POST['mstw_ls_last_5'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_last_10', 
				strip_tags( $_POST['mstw_ls_last_10'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_streak', 
				strip_tags( $_POST['mstw_ls_streak'] ) );

		update_post_meta( $post_id, 'mstw_ls_home', 
				strip_tags( $_POST['mstw_ls_home'] ) );

		update_post_meta( $post_id, 'mstw_ls_away', 
				strip_tags( $_POST['mstw_ls_away'] ) );	

		update_post_meta( $post_id, 'mstw_ls_division', 
				strip_tags( $_POST['mstw_ls_division'] ) );
				
		update_post_meta( $post_id, 'mstw_ls_conference', 
				strip_tags( $_POST['mstw_ls_conference'] ) );
		
	}

	// ----------------------------------------------------------------
	// Set up the League Standings (Teams) 'view all' columns
	// ----------------------------------------------------------------
	add_filter( 'manage_edit-league_team_columns', 'mstw_ls_edit_columns' ) ;

	function mstw_ls_edit_columns( $columns ) {	
		
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title', 'mstw-loc-domain' ),
			'league' => __( 'League', 'mstw-loc-domain' ),
			'name' => __( 'Name', 'mstw-loc-domain' ),
			'mascot' => __( 'Mascot', 'mstw-loc-domain' ),
			'rank' => __( 'Rank', 'mstw-loc-domain' ),
			'games_played' => __( 'GP', 'mstw-loc-domain' ),
			'wins' => __( 'W', 'mstw-loc-domain' ),
			'losses' => __( 'L', 'mstw-loc-domain' ),
			'ties' => __( 'T', 'mstw-loc-domain' ),
			//'other' => __( 'Other', 'mstw-loc-domain' ),
			//'goals_for' => __( 'Goals For', 'mstw-loc-domain' ),
			//'goals_against' => __( 'Goals Against', 'mstw-loc-domain' ),
			//'last_5' => __( 'Last 5', 'mstw-loc-domain' ),
			//'last_10' => __( 'Last 10', 'mstw-loc-domain' ),
			// 'debug' => __('Debug-Remove')
		);

		return $columns;
	}

	// ----------------------------------------------------------------
	// Display the League Standings (Teams) 'view all' columns
	// ----------------------------------------------------------------
	add_action( 'manage_league_team_posts_custom_column', 'mstw_ls_manage_columns', 10, 2 );

	function mstw_ls_manage_columns( $column, $post_id ) {
		//global $post; Not used??

		switch( $column ) {
			case 'name' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_name', true ) );
				printf( '%s', $temp );
				break;

			case 'league' :
				//$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_name', true ) );
				//global $typenow;
				//if ($typenow=='listing') {
					$taxonomy = 'leagues';
					
					$leagues = get_the_terms( $post_id, $taxonomy );
					if ( is_array( $leagues) ) {
						foreach($leagues as $key => $league ) {
							//$edit_link = get_term_link( $league, $taxonomy );
							$leagues[$key] =  $league->name;
						}
							//echo implode("<br/>",$businesses);
							echo implode( ' | ', $leagues );
					}
				/*$terms = wp_get_post_terms( $post_id, 'leagues' );
				$temp = $terms;
				print_r( '%s', $temp );*/
				break;
				
			case 'mascot' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_mascot', true ) );
				printf( '%s', $temp );
				break;
			
			case 'rank' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_rank', true ) );
				printf( '%s', $temp );
				break;
				
			case 'games_played' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_games_played', true ) );
				printf( '%s', $temp );
				break;
			
			case 'wins' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_wins', true ) );
				printf( '%s', $temp );
				break;

			case 'losses' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_losses', true ) );
				printf( '%s', $temp );
				break;
				
			case 'ties' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_ties', true ) );
				printf( '%s', $temp );
				break;	
				
			case 'other' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_other', true ) );
				printf( '%s', $temp );
				break;	
				
			case 'goals_for' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_goals_for', true ) );
				printf( '%s', $temp );
				break;	
				
			case 'goals_against' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_goals_against', true ) );
				printf( '%s', $temp );
				break;	

			case 'last_5' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_last_5', true ) );
				printf( '%s', $temp );
				break;
				
			case 'last_10' :
				$temp = sanitize_text_field( get_post_meta( $post_id, 'mstw_ls_last_10', true ) );
				printf( '%s', $temp );
				break;
			
			/* Just break out of the switch statement for anything else. */
			default :
				break;
		}
	}
	
	// ----------------------------------------------------------------
	// Remove the "View Post" option
	// ----------------------------------------------------------------
	if ( is_admin( ) ) {
		add_filter( 'post_row_actions', 'mstw_ls_remove_the_view', 10, 2 );
	}			

	function mstw_ls_remove_the_view( $actions ) {
		global $post;
		if( $post->post_type == 'league_team' ) {
			unset( $actions['view'] );
		}
		return $actions;
	}

// ----------------------------------------------------------------
//	CODE FOR LEAGUE STANDINGS SETTINGS PAGES
// ----------------------------------------------------------------

// ----------------------------------------------------------------	
// Add a menus for the settings pages
// ----------------------------------------------------------------
	add_action( 'admin_menu', 'mstw_ls_add_page' );

	function mstw_ls_add_page( ) {
		
		// Decided to add the settings page to the Teams menu rather than
		// the settings menu
		$page = add_submenu_page( 	'edit.php?post_type=league_team', 
							'League Standings Settings', 	//page title
							'Display Settings', 			//menu title
							'manage_options', 				// Capability required to see this option.
							'mstw_ls_settings', 			// Slug name to refer to this menu
							'mstw_ls_option_page' );		// Callback to output content
							
		// Does the importing work ... maybe someday?
		//$plugin = new MSTW_LS_ImporterPlugin;
		
		/*add_submenu_page(	'edit.php?post_type=league_team',
							'Import Schedule from CSV File',		//page title
							'CSV Schedule Import',					//menu title
							'manage_options',						//capability to access
							'mstw_ls_csv_import',					//slug name for menu
							array( $plugin, 'form' )				//callback to display menu
						);*/
							
		// Now also add action to load java scripts ONLY when you're on this page
		// add_action( 'admin_print_styles-' . $page, mstw_ls_load_scripts );
	}

// ----------------------------------------------------------------	
// 	Render the option page
// ----------------------------------------------------------------
	function mstw_ls_option_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>League Standings Plugin Settings</h2>
			<?php //settings_errors(); ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'mstw_ls_options_group' ); ?>
				<?php do_settings_sections( 'mstw_ls_settings' ); ?>
				<p>
				<input name="Submit" type="submit" class="button-primary" value=<?php _e( "Save Changes", "mstw-loc-domain" ); ?>  />
				<!-- <input type="submit" name="mstw_ls_options[submit]" value=<?php _e( "Submit 2", "mstw-loc-domain" ); ?> /> -->
				<input type="submit" name="mstw_ls_options[reset]" value="Reset Default Values" />
				<strong><?php _e( "WARNING! Reset Default Values will do so without further warning!", "mstw-loc-domain" ); ?></strong>
				</p>
			</form>
		</div>
		<?php
	}
	
// ----------------------------------------------------------------	
// 	Register and define the settings
// ----------------------------------------------------------------
	add_action('admin_init', 'mstw_ls_admin_init');
	
	function mstw_ls_admin_init( ) {
		// Used throughout
		
		$options = get_option( 'mstw_ls_options');
		$options = wp_parse_args( $options, mstw_ls_get_defaults( ) );
		
		//print_r( $options ); // '<p>$options: ' . $options . '</p>';
		
		register_setting(
			'mstw_ls_options_group',  	// settings group name
			'mstw_ls_options',  		// options (array) to validate
			'mstw_ls_validate_options'  // validation function
			);
		
		// Main Section
		add_settings_section(
			'mstw_ls_main_settings',		// String for use in the 'id' attribute of tags
			'Standings Table Settings',		// Title of the section.
			'mstw_ls_main_inst',			// Callback to display section content
			'mstw_ls_settings'				// Menu page on which to display this section
			);
			
		// ORDER STANDINGS table by column
		$args = array(	'options' => array(	'Win Percentage' => 'percent', 
											'Points' => 'points', 
											'Rank' => 'rank' 
											),
						'id' => 'ls_order_by',
						'name' => 'mstw_ls_options[ls_order_by]',
						'value' => $options['ls_order_by'],
						'label' => 'Select column to short standings tables. (default: Win Percentage)'
						);
		add_settings_field( 
			'mstw_ls_order_by',
			'Order Standings by Column:',
			'mstw_ls_select_option_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// DISPLAY FORMAT for Teams
		$args = array(	'options' => array(	'Team Only' => 'team', 
											'Mascot Only' => 'mascot', 
											'Both' => 'both' 
											),
						'id' => 'ls_team_format',
						'name' => 'mstw_ls_options[ls_team_format]',
						'value' => $options['ls_team_format'],
						'label' => 'Select display format for Team Name. (default: Team)'
						);
		add_settings_field( 
			'mstw_ls_team_format',
			'Display Team by:',
			'mstw_ls_select_option_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide RANK column
		$args = array( 	'id' => 'ls_show_rank',
						'name'	=> 'mstw_ls_options[ls_show_rank]',
						'value'	=> $options['ls_show_rank'],
						'label'	=> 'Check to show the Rank column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_rank',
			'Show Rank Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// RANK column label
		$args = array( 	'id' => 'ls_rank_label',
						'name'	=> 'mstw_ls_options[ls_rank_label]',
						'value'	=> $options['ls_rank_label'],
						'label'	=> 'Set Heading for Rank column. Defaults to "Rank".'
						);
						
		add_settings_field(
			'mstw_ls_rank_label',
			'Rank Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide GAMES PLAYED column
		$args = array( 	'id' => 'ls_show_games_played',
						'name'	=> 'mstw_ls_options[ls_show_games_played]',
						'value'	=> $options['ls_show_games_played'],
						'label'	=> 'Check to show the Games Played column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_games_played',
			'Show Games Played Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GAMES PLAYED column label
		$args = array( 	'id' => 'ls_games_played_label',
						'name'	=> 'mstw_ls_options[ls_games_played_label]',
						'value'	=> $options['ls_games_played_label'],
						'label'	=> 'Set Heading for Games Played column. Defaults to "GP".'
						);
						
		add_settings_field(
			'mstw_ls_games_played_label',
			'Games Played Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);

		// Show/hide WINS column
		$args = array( 'id' => 'ls_show_wins',
						'name' => 'mstw_ls_options[ls_show_wins]',
						'value' => $options['ls_show_wins'],
						'label' => 'Check to show the Wins column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_wins',
			'Show Wins Column:',
			//'mstw_ls_show_wins_ctrl',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// WINS column label
		$args = array( 	'id' => 'ls_wins_label',
						'name'	=> 'mstw_ls_options[ls_wins_label]',
						'value'	=> $options['ls_wins_label'],
						'label'	=> 'Set Heading for Wins column. Defaults to "W".'
						);
		
		add_settings_field(
			'mstw_ls_wins_label',
			'Wins Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide LOSSES column
		$args = array( 'id' => 'ls_show_losses',
						'name' => 'mstw_ls_options[ls_show_losses]',
						'value' => $options['ls_show_losses'],
						'label' => 'Check to show the Losses column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_losses',
			'Show Losses Column:',
			//'mstw_ls_show_losses_ctrl',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// LOSSES column label
		$args = array( 	'id' => 'ls_losses_label',
						'name'	=> 'mstw_ls_options[ls_losses_label]',
						'value'	=> $options['ls_losses_label'],
						'label'	=> 'Set Heading for Losses column. Defaults to "L".'
						);
		add_settings_field(
			'mstw_ls_losses_label',
			'Losses Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide TIES column
		$args = array( 'id' => 'ls_show_ties',
						'name' => 'mstw_ls_options[ls_show_ties]',
						'value' => $options['ls_show_ties'],
						'label' => 'Check to show the Ties column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_ties',
			'Show Ties Column:',
			//'mstw_ls_show_ties_ctrl',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// TIES column label
		$args = array( 	'id' => 'ls_ties_label',
						'name'	=> 'mstw_ls_options[ls_ties_label]',
						'value'	=> $options['ls_ties_label'],
						'label'	=> 'Set Heading for Ties column. Defaults to "T".'
						);
		add_settings_field(
			'mstw_ls_ties_label',
			'Ties Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide OTHER column
		$args = array( 'id' => 'ls_show_other',
						'name' => 'mstw_ls_options[ls_show_other]',
						'value' => $options['ls_show_other'],
						'label' => 'Check to show the Other column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_other',
			'Show Other Column:',
			//'mstw_ls_show_other_ctrl',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// OTHER column label
		$args = array( 	'id' => 'ls_other_label',
						'name'	=> 'mstw_ls_options[ls_other_label]',
						'value'	=> $options['ls_other_label'],
						'label'	=> 'Set Heading for Other column. Defaults to "OTW".'
						);
		add_settings_field(
			'mstw_ls_other_label',
			'Other Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide WIN PERCENTAGE column
		$args = array( 'id' => 'ls_show_percent',
						'name' => 'mstw_ls_options[ls_show_percent]',
						'value' => $options['ls_show_percent'],
						'label' => 'Check to show the Win Percentage column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_percent',
			'Show Win Percentage Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// WIN PERCENTAGE column label
		$args = array( 	'id' => 'ls_percent_label',
						'name'	=> 'mstw_ls_options[ls_percent_label]',
						'value'	=> $options['ls_percent_label'],
						'label'	=> 'Set Heading for Win Percentage column. Defaults to "Percent".'
						);
		add_settings_field(
			'mstw_ls_percent_label',
			'Win Percentage Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide POINTS column
		$args = array( 'id' => 'ls_show_points',
						'name' => 'mstw_ls_options[ls_show_points]',
						'value' => $options['ls_show_points'],
						'label' => 'Check to show the Points column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_points',
			'Show Points Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// POINTS column label
		$args = array( 	'id' => 'ls_points_label',
						'name'	=> 'mstw_ls_options[ls_points_label]',
						'value'	=> $options['ls_points_label'],
						'label'	=> 'Set Heading for Points column. Defaults to "Points".'
						);
		add_settings_field(
			'mstw_ls_points_label',
			'Points Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide GAMES BEHIND column
		$args = array( 'id' => 'ls_show_games_behind',
						'name' => 'mstw_ls_options[ls_show_games_behind]',
						'value' => $options['ls_show_games_behind'],
						'label' => 'Check to show the Games Behind column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_games_behind',
			'Show Games Behind Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GAMES BEHIND column label
		$args = array( 	'id' => 'ls_games_behind_label',
						'name'	=> 'mstw_ls_options[ls_games_behind_label]',
						'value'	=> $options['ls_games_behind_label'],
						'label'	=> 'Set Heading for Games Behind column. Defaults to "GB".'
						);
		add_settings_field(
			'mstw_ls_games_behind_label',
			'Games Behind Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide GOALS FOR column
		$args = array( 'id' => 'ls_show_goals_for',
						'name' => 'mstw_ls_options[ls_show_goals_for]',
						'value' => $options['ls_show_goals_for'],
						'label' => 'Check to show the Goals For column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_goals_for',
			'Show Goals For Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GOALS FOR column label
		$args = array( 	'id' => 'ls_goals_for_label',
						'name'	=> 'mstw_ls_options[ls_goals_for_label]',
						'value'	=> $options['ls_goals_for_label'],
						'label'	=> 'Set Heading for Goals For column. Defaults to "GF".'
						);
		add_settings_field(
			'mstw_ls_goals_for_label',
			'Goals For Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide GOALS AGAINST column
		$args = array( 'id' => 'ls_show_goals_against',
						'name' => 'mstw_ls_options[ls_show_goals_against]',
						'value' => $options['ls_show_goals_against'],
						'label' => 'Check to show the Goals Against column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_goals_against',
			'Show Goals Against Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GOALS AGAINST column label
		$args = array( 	'id' => 'ls_goals_against_label',
						'name'	=> 'mstw_ls_options[ls_goals_against_label]',
						'value'	=> $options['ls_goals_against_label'],
						'label'	=> 'Set Heading for Goals Against column. Defaults to "GA".'
						);
		add_settings_field(
			'mstw_ls_goals_against_label',
			'Goals Against Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		); 
		
		// Show/hide GOALS DIFFERENTIAL column
		$args = array( 'id' => 'ls_show_goals_diff',
						'name' => 'mstw_ls_options[ls_show_goals_diff]',
						'value' => $options['ls_show_goals_diff'],
						'label' => 'Check to show the Goals Differential column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_goals_diff',
			'Show Goals Differential Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GOALS DIFFERENTIAL column label
		$args = array( 	'id' => 'ls_goals_diff_label',
						'name'	=> 'mstw_ls_options[ls_goals_diff_label]',
						'value'	=> $options['ls_goals_diff_label'],
						'label'	=> 'Set Heading for Goals Differential column. Defaults to "GD".'
						);
		add_settings_field(
			'mstw_ls_goals_diff_label',
			'Goals Differential Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		); 
		 
		// Show/hide LAST 10 column
		$args = array( 'id' => 'ls_show_last_10',
						'name' => 'mstw_ls_options[ls_show_last_10]',
						'value' => $options['ls_show_last_10'],
						'label' => 'Check to show the Last 10 column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_last_10',
			'Show Last 10 Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GOALS LAST 10 column label
		$args = array( 	'id' => 'ls_last_10_label',
						'name'	=> 'mstw_ls_options[ls_last_10_label]',
						'value'	=> $options['ls_last_10_label'],
						'label'	=> 'Set Heading for Last 10 column. Defaults to "L10".'
						);
		add_settings_field(
			'mstw_ls_last_10_label',
			'Last 10 Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		); 
		
		// Show/hide LAST 5 column
		$args = array( 'id' => 'ls_show_last_5',
						'name' => 'mstw_ls_options[ls_show_last_5]',
						'value' => $options['ls_show_last_5'],
						'label' => 'Check to show the Last 5 column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_last_5',
			'Show Last 5 Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// GOALS LAST 5 column label
		$args = array( 	'id' => 'ls_last_5_label',
						'name'	=> 'mstw_ls_options[ls_last_5_label]',
						'value'	=> $options['ls_last_5_label'],
						'label'	=> 'Set Heading for Last 5 column. Defaults to "L5".'
						);
		add_settings_field(
			'mstw_ls_last_5_label',
			'Last 5 Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		); 
		
		// Show/hide STREAK column
		$args = array( 'id' => 'ls_show_streak',
						'name' => 'mstw_ls_options[ls_show_streak]',
						'value' => $options['ls_show_streak'],
						'label' => 'Check to show the Streak column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_streak',
			'Show Streak Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// STREAK column label
		$args = array( 	'id' => 'ls_streak_label',
						'name'	=> 'mstw_ls_options[ls_streak_label]',
						'value'	=> $options['ls_streak_label'],
						'label'	=> 'Set Heading for Streak column. Defaults to "Streak".'
						);
		add_settings_field(
			'mstw_ls_streak_label',
			'Streak Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		); 
		
		// Show/hide HOME column
		$args = array( 'id' => 'ls_show_home',
						'name' => 'mstw_ls_options[ls_show_home]',
						'value' => $options['ls_show_home'],
						'label' => 'Check to show the Home column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_home',
			'Show Home Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// HOME column label
		$args = array( 	'id' => 'ls_home_label',
						'name'	=> 'mstw_ls_options[ls_home_label]',
						'value'	=> $options['ls_home_label'],
						'label'	=> 'Set Heading for Home column. Defaults to "Home".'
						);
		add_settings_field(
			'mstw_ls_home_label',
			'Home Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		 
		// Show/hide AWAY column
		$args = array( 'id' => 'ls_show_away',
						'name' => 'mstw_ls_options[ls_show_away]',
						'value' => $options['ls_show_away'],
						'label' => 'Check to show the Away column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_away',
			'Show Away Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// AWAY column label
		$args = array( 	'id' => 'ls_away_label',
						'name'	=> 'mstw_ls_options[ls_away_label]',
						'value'	=> $options['ls_away_label'],
						'label'	=> 'Set Heading for Away column. Defaults to "Away".'
						);
		add_settings_field(
			'mstw_ls_away_label',
			'Away Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide DIVISION column
		$args = array( 'id' => 'ls_show_division',
						'name' => 'mstw_ls_options[ls_show_division]',
						'value' => $options['ls_show_division'],
						'label' => 'Check to show the Division column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_division',
			'Show Division Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// DIVISION column label
		$args = array( 	'id' => 'ls_division_label',
						'name'	=> 'mstw_ls_options[ls_division_label]',
						'value'	=> $options['ls_division_label'],
						'label'	=> 'Set Heading for Division column. Defaults to "Div".'
						);
		add_settings_field(
			'mstw_ls_division_label',
			'Division Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// Show/hide CONFERENCE column
		$args = array( 'id' => 'ls_show_conference',
						'name' => 'mstw_ls_options[ls_show_conference]',
						'value' => $options['ls_show_conference'],
						'label' => 'Check to show the Conference column in standings tables.'
						);
		add_settings_field(
			'mstw_ls_show_conference',
			'Show Conference Column:',
			'mstw_ls_checkbox_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		
		// CONFERENCE column label
		$args = array( 	'id' => 'ls_conference_label',
						'name'	=> 'mstw_ls_options[ls_conference_label]',
						'value'	=> $options['ls_conference_label'],
						'label'	=> 'Set Heading for Conference column. Defaults to "Conf".'
						);
		add_settings_field(
			'mstw_ls_conference_label',
			'Conference Column Label:',
			'mstw_ls_text_ctrl',
			'mstw_ls_settings',
			'mstw_ls_main_settings',
			$args
		);
		

	}
// ----------------------------------------------------------------	
// 	Main section instructions and controls	
// ----------------------------------------------------------------	
	function mstw_ls_main_inst( ) {
		echo '<p>' . __( 'Display settings to control your DEFAULT standings tables. ', 'mstw-loc-domain' ) .'<br/>' .  __( 'You can override these defaults with arguments in your shortcodes. ' ) . '</p>';
		/* Just in case we add some colors someday
		'<br/>' . __( 'All color values are in hex, starting with a hash(#), followed by either 3 or 6 hex digits. For example, #123abd or #1a2.', 'mstw-loc-domain' ) .  '</p>';
		*/
	}
	
// ----------------------------------------------------------------	
//	Show/hide rank column
	function mstw_ls_show_rank_ctrl( ) {
		$options = get_option( 'mstw_ls_options' );
		?>
		<input type="checkbox" id="ls_show_rank" name="mstw_ls_options[ls_show_rank]" value="1" 
		<?php checked( "1", $options['ls_show_rank'], true ) ?> />  
		<label for='ls_show_rank'> Check to show the RANK column in standings tables.</label>
			
		<?php  
	} 
	
	// ----------------------------------------------------------------	
	//	Show/hide WINS column
	function mstw_ls_show_wins_ctrl( ) {
		
		$options = get_option( 'mstw_ls_options' );
		?>
		
		<input type="checkbox" id="ls_show_wins" name="mstw_ls_options[ls_show_wins]" value="1" <?php checked( "1", $options['ls_show_wins'], true ) ?> />  
		<label for='ls_show_wins'> Check to show the WINS column in standings tables.</label>
		
		<?php  
	} 
	
/*----------------------------------------------------------------	
 *	MSTW_LS_TEXT_CTRL
 *	Builds text format controls for the admin UI
 *
 * 	Arguments:
 *		$args['id'] 	(string) ID of input field 
 *		$args['name'] 	(string) Name of input field
 *		$args['value'] 	(string) Current value of input field
 *		$args['label'] 	(string) Instructions displayed after the field
 *
 *	return - none. Control is displayed.
 *---------------------------------------------------------------*/
	function mstw_ls_text_ctrl( $args ) { 
		$id = $args['id'];
		$name = $args['name'];
		$value = $args['value'];
		$label = $args['label'];
		
		echo "<input type='text' id='$id' name='$name' value='$value' /> \n";
		echo "<label for='$id'>$label</label> \n";
		
	} //End: mstw_ls_text_ctrl
	
/*----------------------------------------------------------------	
 *	MSTW_LS_CHECKBOX_CTRL
 *	Builds checkbox format controls for the admin UI
 *
 * 	Arguments:
 *		$args['id'] 	(string) ID of input field 
 *		$args['name'] 	(string) Name of input field
 *		$args['value'] 	(string) Current value of input field
 *		$args['label'] 	(string) Instructions displayed after the field
 *
 *	NOTE that the checked value is always '1'.
 *
 *	return - none. Control is displayed.
 *---------------------------------------------------------------*/
	function mstw_ls_checkbox_ctrl( $args ) { 
		$id = 		$args['id'];
		$name = 	$args['name'];
		$value = 	$args['value'];
		$label = 	$args['label'];
		
		echo "<input type='checkbox' id='$id' name='$name' value='1' " . 
				checked( '1', $value, false ) . "/> \n";  
		echo "<label for='$id'>$label</label> \n";
		
	}	//End: mstw_ls_checkbox_ctrl
	
	
/*----------------------------------------------------------------	
 *	Builds Select-Option controls for the admin UI
 *
 * 	Arguments:
 *	$options (array) :	key/value pairs for the options 
 *	$args['id'] (string):			setting name  from option array
 *	$args['name']
 *	$args['value'] (string):	current value of setting
 *	$args['label'] (string):	default to use of setting is blank
 *
 *	return - none. Output is echoed.
 *---------------------------------------------------------------*/
	function mstw_ls_select_option_ctrl( $args ) {
		
		$options = $args['options'];
		$name = $args['name'];
		$id = $args['id'];
		$curr_value = $args['value'];
		
		echo "<select id='$id' name='$name' style='width: 160px' >";
		foreach( $options as $key=>$value ) {
			//echo '<p> key: ' . $key . ' value: ' . $value .'</p>';
			$selected = ( $curr_value == $value ) ? 'selected="selected"' : '';
			echo "<option value='$value' $selected>$key</option>";
		}
		echo "</select> \n";
		echo "<label for='$id'>$label</label> \n";
		
	}
	
	function mstw_ls_sort_order_ctrl( ) {
	$options = get_option( 'mstw_tr_options' );
	$tr_table_default_format = $options['tr_table_default_format'];
	
	// echo the field
    $html = "<p><input type='radio' id='high-school-format' 
				name='mstw_tr_options[tr_table_default_format]' value='high-school'" . 
				checked( "high-school", $options['tr_table_default_format'], false ) . '/>';  
    $html .= "<label for='high-school-format'> High School Format</label></p>";
	
    $html .= "<p><input type='radio' id='college-format' 
				name='mstw_tr_options[tr_table_default_format]' value='college'" . 
				checked( "college", $options['tr_table_default_format'], false ) . '/>';  
    $html .= "<label for='college-format'> College Format</label></p>";

	$html .= "<p><input type='radio' id='pro-format' 
				name='mstw_tr_options[tr_table_default_format]' value='pro'" . 
				checked( "pro", $options['tr_table_default_format'], false ) . '/>';  
    $html .= "<label for='pro-format'> Pro Format</label></p>";

	$html .= "<p><input type='radio' id='hs-baseball-format' 
				name='mstw_tr_options[tr_table_default_format]' value='hs-baseball'" . 
				checked( "hs-baseball", $options['tr_table_default_format'], false ) . '/>';  
    $html .= "<label for='hs-baseball-format'> High School Baseball Format</label></p>";
	
	$html .= "<p><input type='radio' id='coll-baseball-format' 
				name='mstw_tr_options[tr_table_default_format]' value='coll-baseball'" . 
				checked( "coll-baseball", $options['tr_table_default_format'], false ) . '/>';  
    $html .= "<label for='coll-baseball-format'> College Baseball Format</label></p>";
	
	$html .= "<p><input type='radio' id='pro-baseball-format' 
				name='mstw_tr_options[tr_table_default_format]' value='pro-baseball'" . 
				checked( "pro-baseball", $options['tr_table_default_format'], false ) . '/>';  
    $html .= "<label for='pro-baseball-format'> Pro Baseball Format</label></p>";
	
    echo $html;  
}

	/*function mstw_ls_color_ctrl( ) {
		global $just_playing;
	?>
		<input id="ls_color" name="mstw_ls_options[ls_color]" type="text" value="" />
		<div id="colorpicker"></div>
	<?php
		echo '<p>' . plugin_dir_url( ) . 'game-schedules/js/settings.js' . '</p>';
		echo '<p>' . $just_playing . '</p>';
	}
	*/

// ----------------------------------------------------------------	
// 	Date-time format section instructions and controls	
// ----------------------------------------------------------------	

	function mstw_ls_date_time_inst( ) {
		echo '<p>' . __( 'Enter the date-time formats for your shortcodes and widgets. ', 'mstw-loc-domain' ) . '</p>';
	}

// ----------------------------------------------------------------	
//	Validate user input (we want text only)
 
	function mstw_ls_validate_options( $input ) {
		// Create our array for storing the validated options
		$output = array();
		
		/*if ( $input['submit'] ) 
			echo '<p>We got a Submit Button Press</p>';
		if ( $input['reset'] ) 
			echo '<p>We got a Reset Button Press</p>';
		*/
		
		if ( $input['reset'] ) {
			$output = mstw_ls_get_defaults( );
			return $output;
		}
		
		// Pull the previous (last good) options
		$options = get_option( 'mstw_ls_options' );
		
		// Loop through each of the incoming options
		foreach( $input as $key => $value ) {
			// Check to see if the current option has a value. If so, process it.
			if( isset( $input[$key] ) ) {
				switch ( $key ) {
					// add the hex colors
					case 'ls_table_head_text_color':
					case 'ls_table_head_bkgd_color':
					case 'ls_table_title_text_color':
					case 'ls_table_links_color':
					case 'ls_table_even_row_color':
					case 'ls_table_even_row_bkgd':
					case 'ls_table_odd_row_color':
					case 'ls_table_odd_row_bkgd':
					case 'sp_main_bkgd_color':
					case 'sp_main_text_color':
						
						// validate the color for proper hex format
						$sanitized_color = mstw_sanitize_hex_color( $input[$key] );
						
						// decide what to do - save new setting 
						// or display error & revert to last setting
						if ( isset( $sanitized_color ) ) {
							// blank input is valid
							$output[$key] = $sanitized_color;
						}
						else  {
							// there's an error. Reset to the last stored value
							$output[$key] = $options[$key];
							// add error message
							add_settings_error( 'mstw_ls_' . $key,
												'mstw_ls_hex_color_error',
												'Invalid hex color entered in: ' . $key,
												'error');
						}
						break;
					
					default:
						// There should not be user/accidental errors in these fields
						//case 'ls_hide_media':
						$output[$key] = sanitize_text_field( $input[$key] );
						break;
					
				} // end switch
			} // end if
		} // end foreach
		//if ( $input['submit'] ) 
		//	echo '<p>We got a Submit Button Press ... done.</p>';
		// Return the array processing any additional functions filtered by this action
		//return apply_filters( 'sandbox_theme_validate_input_examples', $output, $input );
		return $output;
	}	
	
	function mstw_ls_admin_notices() {
		settings_errors( );
	}
	add_action( 'admin_notices', 'mstw_ls_admin_notices' );
	
	function mstw_ls_get_defaults( ) {
		$defaults = array(	'ls_order_by'				=> 'percent',
							'ls_team_format'			=> 'mascot',
							'ls_show_rank' 				=> 0,
							'ls_rank_label' 			=> 'Rank',
							'ls_show_games_played'		=> 0,
							'ls_games_played_label'		=> 'GP',
							'ls_show_wins'				=> 1,
							'ls_wins_label' 			=> 'W',
							'ls_show_losses'			=> 1,
							'ls_losses_label' 			=> 'L',
							'ls_show_ties'				=> 1,
							'ls_ties_label' 			=> 'T',
							'ls_show_other'				=> 0,
							'ls_other_label' 			=> 'OTW',  //Overtime Wins (Hockey)
							'ls_show_percent'			=> 1,
							'ls_percent_label'			=> 'Percent',
							'ls_show_points'			=> 0,
							'ls_points_label'			=> 'PTS',
							'ls_show_games_behind'		=> 0,
							'ls_games_behind_label'		=> 'GB',
							'ls_show_goals_for'			=> 0,
							'ls_goals_for_label'		=> 'GF',
							'ls_show_goals_against'		=> 0,
							'ls_goals_against_label'	=> 'GA',
							'ls_show_goals_diff'		=> 0,
							'ls_goals_diff_label'		=> 'GD',
							'ls_show_last_10'			=> 0,
							'ls_last_10_label'			=> 'L10',
							'ls_show_last_5'			=> 0,
							'ls_last_5_label'			=> 'L5',
							'ls_show_streak'			=> 0,
							'ls_streak_label'			=> 'Streak',
							'ls_show_home'				=> 0,
							'ls_home_label'				=> 'Home',
							'ls_show_away'				=> 0,
							'ls_away_label'				=> 'Away',
							'ls_show_division'			=> 0,
							'ls_division_label'			=> 'Div',
							'ls_show_conference'		=> 0,
							'ls_conference_label'		=> 'Conf',
							);
	
		return $defaults;
	}

?>