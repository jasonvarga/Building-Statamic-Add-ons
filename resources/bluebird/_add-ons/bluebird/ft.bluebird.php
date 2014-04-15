<?php
class Fieldtype_bluebird extends Fieldtype
{

	/**
	 * Render the field
	 */
	public function render()
	{
		// Get tweets
		$parameters = array(
			'count'            => array_get($this->field_config, 'count', 10),
			'screen_name'      => array_get($this->field_config, 'screen_name', 'fredleblanc'),
			'include_rts'      => array_get($this->field_config, 'include_rts', true),
			'include_entities' => array_get($this->field_config, 'include_entities', true),
			'exclude_replies'  => array_get($this->field_config, 'exclude_replies', false),
			'cache_length'     => array_get($this->field_config, 'cache_length', 60)
		);
		$tweets = $this->tasks->getTweets($parameters);
		$tweets = $tweets['tweets'];

		// Build options
		$options = '';
		foreach ($tweets as $tweet) {
			$selected = ($tweet['id'] == $this->field_data) ? 'selected' : '';
			$options .= "<option value='{$tweet['id']}' $selected>{$tweet['text']}</option>";
		}

		// Build the select
		$required = ($this->is_required) ? 'required' : '';
		$select = "
			<div class='input-select'>
				<div class='input-select-wrap'>
					<select name='{$this->fieldname}' id='{$this->field_id}' $required>
						<option value=''>Select a tweet</option>
						$options
					</select>
				</div>
			</div>
		";

		// Display it
		return $select;
	}

}