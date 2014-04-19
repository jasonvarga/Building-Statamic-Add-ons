<?php
class Tasks_bluebird extends Tasks
{

	/**
	 * Twitter's API endpoint
	 **/
	private $api_url = 'https://api.twitter.com/1.1/';

	/**
	 * Parameters from plugin (or elsewhere) to be re-used throughout methods
	 **/
	private $params;

	//------------------------------------------------------

	/**
	 * Fetch a user's timeline from the API
	 **/
	public function getTweetsFromAPI()
	{
		$url = $this->api_url . 'statuses/user_timeline.json';

		if ($this->params['exclude_replies'] == true) {
			$query = array(
				'count'            => $this->params['count'],
				'screen_name'      => $this->params['screen_name'],
				'include_rts'      => $this->params['include_rts'],
				'exclude_replies'  => $this->params['exclude_replies'],
				'include_entities' => $this->params['include_entities']
			);
		} else {
			$query = array(
				'count'            => $this->params['count'],
				'screen_name'      => $this->params['screen_name'],
				'include_rts'      => $this->params['include_rts'],
				'include_entities' => $this->params['include_entities']
			);
		}

		$response = $this->performRequest($url, $query);
		return $this->formatTweets(json_decode($response));
	}

	//------------------------------------------------------

	/**
	 * Talk to the Twitter API
	 **/
	private function performRequest($url, $query)
	{
		// Get config
		$oauth_access_token        = $this->fetchConfig('access_token', null, null, false, false);
		$oauth_access_token_secret = $this->fetchConfig('access_token_secret', null, null, false, false);
		$consumer_key              = $this->fetchConfig('consumer_key', null, null, false, false);
		$consumer_secret           = $this->fetchConfig('consumer_secret', null, null, false, false);

		// Set up oauth
		$oauth = array(
			'oauth_consumer_key'     => $consumer_key,
			'oauth_nonce'            => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token'            => $oauth_access_token,
			'oauth_timestamp'        => time(),
			'oauth_version'          => '1.0'
		);

		// Build params and URL
		$base_params = empty($query) ? $oauth : array_merge($query, $oauth);
		$base_info   = $this->buildBaseString($url, 'GET', $base_params);
		$url         = $url . '?' . http_build_query($query);

		// Create oauth signature
		$composite_key            = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
		$oauth_signature          = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
		$oauth['oauth_signature'] = $oauth_signature;

		// Create request header
		$header = array($this->buildAuthorizationHeader($oauth), 'Expect:');

		// Prepare and perform cURL request
		$options = array(
			CURLOPT_HTTPHEADER     => $header,
			CURLOPT_HEADER         => false,
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false
		);
		$feed = curl_init();
		curl_setopt_array($feed, $options);
		$json = curl_exec($feed);
		curl_close($feed);

		// Send it back
		return $json;
	}

	/**
	 * Build the URL for posting
	 */
	private function buildBaseString($baseURI, $method, $params)
	{
		$r = array();
		ksort($params);
		foreach ($params as $key=>$value) {
			$value = ($value == false) ? 0 : $value; // ensure falses becomes 0
			$r[] = "$key=" . rawurlencode($value);
		}
		return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r)); //return complete base string
	}

	/**
	 * Build the oAuth header
	 */
	private function buildAuthorizationHeader($oauth)
	{
		$r = 'Authorization: OAuth ';
		$values = array();
		foreach ($oauth as $key=>$value)
			$values[] = "$key=\"" . rawurlencode($value) . "\"";
		$r .= implode(', ', $values);
		return $r;
	}

	/**
	 * Format the response into a Statamic-parsable fashion
	 */
	private function formatTweets($twitter_data)
	{
		$tweets = $this->theNest($twitter_data);

		$output = array(
			'tweets' => array(),
			'user'   => array()
		);

		foreach ($tweets as $tweet) {

			$tweetText = $tweet['text'];

			if (isset($tweet['entities'])) {
				if (is_array($tweet['entities']['urls'])) { $entityUrl = $tweet['entities']['urls']; }
				if (is_array($tweet['entities']['hashtags'])) { $entityHash = $tweet['entities']['hashtags']; }
				if (is_array($tweet['entities']['user_mentions'])) { $entityUser = $tweet['entities']['user_mentions']; }
			}

			if (!empty($entityUrl) || !empty($entityHash) || !empty($entityUser)) {

				foreach ($entityUrl as $url) {
					$find = $url['url'];
					$replace = '<a href="'.$find.'">'.$url['display_url'].'</a>';
					$tweetText = str_replace($find, $replace, $tweetText);
				}

				foreach ($entityHash as $hashtag) {
					$find = '#'.$hashtag['text'];
					$replace = '<a href="https://twitter.com/#!/search/%23'.$hashtag['text'].'">'.$find.'</a>';
					$tweetText = str_replace($find, $replace, $tweetText);
				}

				foreach ($entityUser as $user_mention) {
					$find = "@".$user_mention['screen_name'];
					$replace = '<a href="https://twitter.com/'.$user_mention['screen_name'].'">'.$find.'</a>';
					$tweetText = str_replace($find, $replace, $tweetText);
				}

			}

			if (!empty($tweet['entities']['media'])) {
				foreach ($tweet['entities']['media'] as $media) {
					$find = $media['url'];
					$replace = '<a href="'.$media['url'].'">'.$media['display_url'].'</a>';
					$tweetText = str_replace($find, $replace, $tweetText);
				}
			}

			$tweet['text'] = $tweetText;
			$tweet['tweet_url'] = "https://twitter.com/" . $tweet['user']['screen_name'] . "/status/" . $tweet['id'];

			array_push($output["tweets"], $tweet);
			array_push($output["user"], $tweet['user']);

		}

		for ($i = 1; $i <= $this->params['count']; $i++) {
			unset($output["user"][$i]);
		}

		return $output;
	}

	/**
	 * Twitter formatting magic
	 */
	private function theNest($d)
	{
		if (is_object($d)) {
			$d = get_object_vars($d);
		}
		if (is_array($d)) {
			return array_map(array($this, __FUNCTION__), $d);
		} else {
			return $d;
		}
	}

	//------------------------------------------------------

	/**
	 * Debugging has never been more humorous
	 **/
	public function birdTurd($turd)
	{
		echo "<pre>";
		echo var_dump($turd);
		echo "</pre>";
	}

}