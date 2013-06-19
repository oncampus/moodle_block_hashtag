<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hashtag Block page.
 *
 * @package    block
 * @subpackage blog_menu
 * @copyright  2009 Nicolas Connault
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The hashtag block class
 */
class block_hashtag extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_hashtag');
    }

    function instance_allow_multiple() {
        return true;
    }

    function has_config() {
        return false;
    }

    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false);
    }

    function instance_allow_config() {
        return true;
    }

    function get_content() {
    	
    	if ($this->content !== NULL) {
            return $this->content;
        }
		
		global $DB;
		
		$course_id = $this->page->course->id;
		
		// get ir numbers from linked content by url
		$query = "SELECT externalurl FROM mdl_url WHERE course = $course_id AND externalurl LIKE '%/ir%'";
		$return = $DB->get_records_sql($query);
		
		$ir_numbers = array();
		$hashtags = array();
		foreach($return as $r){
			// get from ir to the end
			$ir_number = substr($r->externalurl, strpos($r->externalurl, "/ir")+3);
			// cut the end
			$ir_number = substr($ir_number, 0 , strpos($ir_number, "/"));
			
			// collect hashtag
			if(!in_array($ir_number, $ir_numbers)){
				$ir_numbers[] = $ir_number;
				
				$url = get_config('block_hashtag','moodalis_link')."?ir=".$ir_number;
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 180);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
				$result = curl_exec($ch);
				curl_close($ch);
				
				$result = unserialize($result);
				
				if(count($result) > 0)
					$hashtags[] = ($result);
				
			}
		}
		
		$this->content = new stdClass;
		$this->content->text = '';
		$this->content->footer = '';
		
		// print each single hashtag
		array_unique($hashtags);
		
		if(count($hashtags) > 0){
			foreach($hashtags as $hashtag){
				$this->content->text  .= "<br /><a target=\"_blank\" alt=\"$hashtag\" title=\"".get_string('link_to', 'block_hashtag')."$hashtag\" href='http://www.twitter.com/search/$hashtag'>#$hashtag</a>";
			}
			$this->content->text .= "<br /><br/>".get_string('more_infos', 'block_hashtag')."<a target=\"_blank\" href=\"".get_config('block_hashtag','oncampuspedia_link')."\">oncampuspedia</a>";
		}
		
        // Return the content object
        return $this->content;
    }
}
