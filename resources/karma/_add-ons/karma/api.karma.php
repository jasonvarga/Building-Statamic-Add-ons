<?php
class API_karma extends API
{

	/**
	 * Get Points
	 * Retrieve a specific member's points
	 **/
	public function getPoints($username=null)
	{
		// Get points
		return $this->tasks->getPoints($username);
	}

}