<?php
class Fieldtype_karma extends Fieldtype
{

	/**
	 * Render the field
	 */
	public function render()
	{
		// Save some keystrokes
		$name = 'page[yaml][_protect][allow][_addon]';

		// Since we're not editing our actual fieldset assigned field - we need to get the content of the entry
		if (Request::get('new')) {
			$content = array();
		} else {
			$url = $this->getURL();
			$content = Content::get($url);
		}

		// Get the existing values and fall back to blanks.
		if (isset($content['_protect'])) {
			$data = array_get($content, '_protect:allow:_addon');
		} else {
			$data = array(
				'comparison' => '',
				'value'      => '',
				'error'      => ''
			);
		}

		// Build the basic fields
		$method_field = "<input type='hidden' name='{$name}[method]' value='karma:getPoints' />";
		$value_field = "<input type='number' name='{$name}[value]' min='0' step='1' placeholder='Value' value='{$data['value']}' />";
		$error_field = "<input type='text' name='{$name}[error]' placeholder='Error message' value='{$data['error']}' />";

		// Create the comparisons select field
		$comparisons = array(
			'>=' => 'Greater than or equal to',
			'>'  => 'Greater than',
			'<'  => 'Less than',
			'<=' => 'Less than or equal to',
			'==' => 'Equal to'
		);
		$options = '';
		foreach ($comparisons as $val => $label) {
			$selected = ($val == $data['comparison']) ? 'selected' : '';
			$options .= "<option value='$val' $selected>$label</option>";
		}
		$comparison_field = "<select name='{$name}[comparison]'>$options</select>";

		// Build the entire fieldset
		$output = "
			<div class='karma-config'>
				<div class='input-select karma-comparison'>
					<div class='input-select-wrap'>$comparison_field</div>
				</div>
				<div class='karma-value'>$value_field</div>
			</div>
			<div class='karma-error'>$error_field</div>
			$method_field
		";

		// Output
		return $output;
	}

	/**
	 * Get URL of entry from the CP URL
	 */
	private function getURL()
	{
		// Get the query string and remove the ordering
		$url = Path::pretty(Request::get('path'));
		// Remove the 'page' if it's a page.md
		$url = (Pattern::endsWith($url, 'page'))
		       ? URL::popLastSegment($url)
		       : $url;

		return $url;
	}

}