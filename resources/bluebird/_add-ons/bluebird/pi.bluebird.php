<?php
class Plugin_bluebird extends Plugin {

	/**
	 * Get tweets from a user's timeline
	 **/
	public function tweets()
	{
		// Get parameters
		$params = array(
			'count'            => $this->fetchParam('count', 5, 'is_numeric'),
			'screen_name'      => $this->fetchParam('screen_name', null),
			'include_rts'      => $this->fetchParam('include_rts', true, null, true),
			'include_entities' => $this->fetchParam('include_entities', true, null, true),
			'exclude_replies'  => $this->fetchParam('exclude_replies', false, null, true),
			'cache_length'     => $this->fetchParam('cache', 60) // Cache time in seconds
		);

		// Get tweets
		return $this->tasks->getTweets($params);
	}

	/**
	 * Alias to tweets
	 * for backwards compatability and because most
	 * people will probably just use it that way.
	 **/
	public function index()
	{
		return $this->tweets();
	}

	/**
	 * Get a single tweet from an ID
	 **/
	public function tweet()
	{
		// Get parameter
		$id = $this->fetchParam('id');

		// No ID? Bail out.
		if (!$id) {
			$this->log->warn('No tweet ID passed.');
			return;
		}

		// Get tweet
		return $this->tasks->getTweet($id);
	}

}