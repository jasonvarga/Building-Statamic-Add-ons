<?php
class Hooks_karma extends Hooks
{

	/**
	 * Give points trigger URL
	 * /TRIGGER/karma/give_points
	 **/
	public function karma__give_points()
	{
		// Take the hidden POST data out so we are left
		// with a cleaner POST submission to work with.
		$hidden = $_POST['hidden'];
		unset($_POST['hidden']);
		$submission = $_POST;

		// Get form POST data
		$username = $submission['member'];
		$points = (int) $submission['points'];

		// Get member
		$member = Member::load($username);

		// Get existing points, defaulting to 0 if null
		$existing_points = $member->get('points', 0);

		// Add points to the member
		$new_points = $existing_points + $points;
		$member->set('points', $new_points);

		// Save it to file
		$member->save();

		// Set a flash message
		$this->flash->set('give_points_success', true);

		// Redirect
		$return = $hidden['return'];
		URL::redirect($return);
	}

	//---------------------------------------------

	/**
	 * Add to control panel <head> tags
	 */
	public function control_panel__add_to_head()
	{
		if (URL::getCurrent() == '/publish') {
			return $this->css->link('karma');
		}
	}

}