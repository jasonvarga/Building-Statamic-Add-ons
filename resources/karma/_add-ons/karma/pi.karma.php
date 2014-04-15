<?php
class Plugin_karma extends Plugin
{

	/**
	 * Points
	 * Output the points of a specific member
	 **/
	public function points()
	{
		// Get username parameter
		$username = $this->fetchParam(array('member','username'));

		// Get points
		return $this->tasks->getPoints($username);
	}

	//---------------------------------------------

	/**
	 * Members
	 * Loop through members
	 **/
	public function members()
	{
		// Get parameters
		$sort_by = $this->fetchParam('sort_by', 'points');
		$sort_dir = $this->fetchParam('sort_dir', 'desc');

		// Get members
		$members = $this->tasks->getMembers();

		// Perform sorting
		if ($sort_by) {
			usort($members, function($a, $b) use ($sort_by) {
				return Helper::compareValues($a[$sort_by], $b[$sort_by]);
			});
		}

		// Flip order if necessary
		if ($sort_dir == 'desc') {
			$members = array_reverse($members);
		}

		// Output
		return Parse::tagLoop($this->content, $members);
	}

	//---------------------------------------------

	/**
	 * Give points form
	 * Output a form for giving points to a member
	 **/
	/**
	 * Give points form
	 * Output a form for giving points to a member
	 **/
	public function give_points_form()
	{
		// Get the parameters
		$exclude_me = $this->fetchParam('exclude_me', false, null, true);
		$return = $this->fetchParam('return', URL::getCurrent());

		// Get members
		$members = $this->tasks->getMembers();

		// Remove me from members list, if specified.
		if ($exclude_me && Auth::isLoggedIn()) {
			$username = Auth::getCurrentMember()->get('username');
			unset($members[$username]);
		}

		// Set template data
		$vars = array(
			'members' => $members,
			'my_name' => Auth::isLoggedIn() ? Auth::getCurrentMember()->get('first_name') : 'Guest'
		);

		// Create the form output
		$output = '<form method="POST" action="/TRIGGER/karma/give_points" class="give-karma-form">';
		$output .= "<input type=\"hidden\" name=\"hidden[return]\" value=\"{$return}\" />";
		$output .= Parse::template($this->content, $vars);
		$output .= '</form>';
		return $output;
	}

	//---------------------------------------------

	/**
	 * Give points success
	 * A conditional for whether or not a submission was successful
	 **/
	public function give_points_success()
	{
		return $this->flash->get('give_points_success', false);
	}

	//---------------------------------------------

	/**
	 * Allowed Pages
	 * Loop through pages the user has access to
	 */
	public function allowed_pages()
	{
		// Get all pages
		$content_set = ContentService::getContentByFolders('*');

		// Filter out pages that don't have a protect var
		$content_set->filter(array(
			'conditions' => '_protect'
		));

		// Get points
		$points = $this->tasks->getPoints();

		// Filter out pages we don't have access to
		$content_set->customFilter(function($entry) use ($points) {
			$protect = $entry['_protect']['allow']['_addon'];
			return (version_compare($points, $protect['value'], $protect['comparison']));
		});

		// Supplement with points variable
		$content_set->customSupplement('points', function($entry_url){
			$entry = Content::get($entry_url);
			return $entry['_protect']['allow']['_addon']['value'];
		});

		// Supplement with rule variable
		$content_set->customSupplement('rule', function($entry_url){
			$entry = Content::get($entry_url);
			$value = $entry['_protect']['allow']['_addon']['value'];
			$comparison = $entry['_protect']['allow']['_addon']['comparison'];
			return "$comparison $value";
		});

		// No results
		if ($content_set->count() == 0) {
			return Parse::template($this->content, array('no_results' => true ));
		}

		// Output
		$vars = $content_set->get(false, false);
		return Parse::tagLoop($this->content, $vars);
	}




}