<?php
/*
Plugin Name: MJJ Reassign User Content
Description: Dropdown to reassign content to a pre-selected group when deleting a user.
Author: Mary (JJ) Jay
Version: 1
*/

/**
*
*/
class MJJReassign
{

	function __construct()
	{

		// hook in /wp-admin/users.php
		add_action( 'mjj_reassign_to_user', array( $this, 'reassign_to_users' ) );

		// let's just add it to the edit screen, no need for a column
		add_action( 'edit_user_profile', array( $this, 'edit_reassignee' ) );

		// and save it on the edit user screen
		add_action('edit_user_profile_update', array( $this, 'update_reassignee' ) );

		// add to the new form page
		add_action( 'user_new_form', array( $this, 'maybe_new_reassignee' ) );

		// after the new user is registered, check to see if you can reassign to her
		add_action( 'user_register', array( $this, 'update_reassignee' ), 10, 1 );

		// it would be nice to have it with edit | delete on the list too maybe, not included though
	}

	// add in the dropdown list with array of users allowed to have content reassigned to them (ie reassignees)
	// y'know what, let's just edit the fucking file. I've replaced wp_dropdown_users with do_action('reassign_to_user') in /wp-admin/users.phpL245
	function reassign_to_users(){

		// Bail if the current user cannot delete users -- this is already in the page but y'know
		if( !current_user_can( 'delete_users' ) ){
			return false;
		}

		//this will return the array of reassignees
		$reassignees = get_option( 'mjj_reassignees' );

		// if there aren't any, say so, then bail
		if( !$reassignees ){
			echo 'You need to choose users who can be used here. Currently you haven&rsquo;t chosen any.';
			return false;
		}

		// otherwise, print out the select
		echo '<select id="reassign_user" name="reassign_user">';
			foreach ($reassignees as $re_name => $re_id ) {
				echo '<option value="' . $re_id . '">' . $re_name . '</option>';
			}
		echo '</select>';

		return true;
	}

	function edit_reassignee( $user ){

		// Bail if current user cannot edit this user
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		// This will return the array of reassignees
		$reassignees = get_option( 'mjj_reassignees' );

		// Is this user in the reassignee array? If yes, then the box will be checked
		if( $reassignees ){
			$reassign = in_array( $user->ID, $reassignees ) ? 'checked' : '';
		}
		else{
			$reassign = '';
		}

		// Make the reassign metabox
		$this -> choose_reassignee( $reassign );

	}

	function maybe_new_reassignee(){

		// Bail if current user cannot register users
		if ( ! current_user_can( 'create_users' ) ) {
			return;
		}

		$this -> choose_reassignee( );

	}

	function choose_reassignee( $reassign ){

		echo '<table class="form-table"><tbody><tr class="can-be-reassignee">';
		echo '<th><label for="is_reassignee">Reassignee?</label></th>';
		echo '<td>';
		echo '<fieldset><legend class="screen-reader-text"><span>Allow posts to be reassigned to this user</span></legend>';
		echo '<label for="is_reassignee">';
		echo '<input name="is_reassignee" type="checkbox" id="is_reassignee" ' . $reassign . ' value="1" />';
		echo 'Allow posts to be reassigned to this user when deleting other users.</label><br /></fieldset>';
		echo '</td></tr></tbody></table>';

	}

	function update_reassignee( $reassignee_id ) {

    	if ( current_user_can( 'edit_user', $reassignee_id ) ){

    		$reassignee_name = get_userdata( $reassignee_id )->user_login;
    		$reassignee_list = get_option( 'mjj_reassignees' );

    		$make_reassignee = 1 == $_POST['is_reassignee'] ? 1 : 0;

    		if( 1 == $make_reassignee ){
    			$this -> add_reassignee( $reassignee_id, $reassignee_name, $reassignee_list );
    		}
    		else{
    			$this -> delete_reassignee( $reassignee_id, $reassignee_name, $reassignee_list );
    		}
    	}
 	}

 	function add_reassignee( $reassignee_id, $reassignee_name, $reassignee_list ){

 		$reassignee_keys = array_keys( $reassignee_list, $reassignee_id );
    	if( ! $reassignee_keys ){
    		$reassignee_list[ $reassignee_name ] = $reassignee_id;
    		update_option( 'mjj_reassignees', $reassignee_list );
    	}
    	elseif( $reassignee_key && $reassignee_key != $reassignee_name ){
    		unset( $reassignee_list[ $reassignee_key ] );
    		$reassignee_list[ $reassignee_name ] = $reassignee_id;
    		update_option( 'mjj_reassignees', $reassignee_list );
    	}
    	else{
    		return;
    	}
 	}

 	function delete_reassignee( $reassignee_id, $reassignee_name, $reassignee_list ){

 		$reassignee_keys = array_keys( $reassignee_id, $reassignee_list );

    	if( empty( $reassignee_keys ) ){
    		return;
    	}
    	elseif( !empty( $reassignee_keys ) ){
    		foreach( $reassignee_keys as $reassignee_key ){
    			if( $reassignee_key != $reassignee_name ){
    				unset( $reassignee_list[ $reassignee_key ] );
    			}
    		}
    		$reassignee_list[ $reassignee_name ] = $reassignee_id;
    		update_option( 'mjj_reassignees', $reassignee_list );
    	}
    	else{
    		return;
    	}
 	}
}

new MJJReassign();
