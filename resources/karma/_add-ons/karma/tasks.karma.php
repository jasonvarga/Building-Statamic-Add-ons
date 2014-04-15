<?php
class Tasks_karma extends Tasks
{

	/**
	 * Define scheduled tasks
	 */
	public function define()
	{
		// Reset points every week
		$this->add(1, 'resetPoints');
	}

	//---------------------------------------------

	/**
	 * Reset Points
	 * Set all members' points to zero
	 */
	public function resetPoints()
	{
		// Get all members
		$members = $this->getMembers();

		// Iterate over members and reset
		foreach ($members as $mbr) {
			$member = Member::load($mbr['username']);
			$member->set('points', 0);
			$member->save();
		}
	}

	//---------------------------------------------

	/**
	 * Get members
	 * Retrieve all members
	 **/
	public function getMembers()
	{
		// Get all the members
		$members = MemberService::getMembers()->extract();

		// Make sure members have a points field
		foreach ($members as $i => $member) {
			// If their points exist, use them, otherwise set to 0
			$members[$i]['points'] = (isset($member['points'])) ? $member['points'] : 0;
		}

		// Return them
		return $members;
	}

	//---------------------------------------------

	/**
	 * Get points
	 * Retrieve a specific member's points
	 **/
	public function getPoints($username=null)
	{
		// Get member
		// Username supplied?
		if ($username) {
			$member = Member::load($username);
		}
		// No member specified? Get current user
		else {

			// Not logged in? Back out.
			if (!Auth::isLoggedIn())
				return false;

			$member = Auth::getCurrentMember();
		}

		// Get points, or 0 if the field doesn't exist
		$points = $member->get('points');
		return ($points) ? $points : 0;
	}

}