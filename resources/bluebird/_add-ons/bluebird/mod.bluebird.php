<?php
class Modifier_bluebird extends Modifier
{

	/**
	 * Change ID to a tweet URL
	 */
	public function index($value, $parameters=array())
	{
		// Get tweet
		$tweet = $this->tasks->getTweet($value);

		// Create URL
		$url = "https://twitter.com/{$tweet['user']['screen_name']}/status/$value";

		// Return it
		return (isset($parameters[0]) && $parameters[0] == 'raw')
		       ? $url
		       : "<a href=\"$url\">$url</a>";
	}

}