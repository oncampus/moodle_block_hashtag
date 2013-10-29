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
        return true;
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
		
		$this->content = new stdClass;
		$this->content->text = '';
		$this->content->footer = '';
		
		// temporary return empty block while cURL is being debugged
		#return $this->content;
		//////////////////////
		
		global $DB, $SESSION;
		
		// include library fuer DB zugriff auf portal und IR nummer finden
		$file = "/opt/www/moodle/oncampus/modulfeedbacklib.php";
		if (is_file($file)) {
			require_once($file);
		}


		$course_id = $this->page->course->id;
		$course_idnumber = $this->page->course->idnumber;
		$debug .= "cid: ".$course_idnumber;
		
		// Hole Hashtag aus Session oder ueber portal und moodalis
		if(isset($SESSION->hash[$course_id])){
			
			$hashtag = $SESSION->hash[$course_id];
			$debug .= "sess: ".$hashtag;
			
		}else{
			
			if(function_exists('getIR')){
				$irnumber = getIR($course_idnumber);
				$debug .= "ir: ".serialize($irnumber);
				$irnumber = $irnumber[0][0];
				$debug .= "ir: ".$irnumber;
			}else{
				$this->content->text .= 'Library konnte nicht geladen werden';
				return $this->content;
			}
			
			if(isset($irnumber) && is_numeric($irnumber)){
				
				$url = get_config('block_hashtag','moodalis_link')."?ir=".$irnumber;
				
				$debug .= "\nurl: ".$url;
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_PROXYPORT,80);
				curl_setopt($ch, CURLOPT_PROXY, "proxy.oncampus.de");
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 3);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				$result = curl_exec($ch);
				curl_close($ch);
				
				$result = unserialize($result);
				
				$debug .= " res1: ".$result;
					
				if(count($result) > 0){
					$debug .= "res2: ".$result;
					// Speichern fuer Ausgabe im text
					$hashtag = $result;
					// spiechern fuer spaetere Seitenaufrufe
					$SESSION->hash[$course_id] = $hashtag;
				}
				
			}else{
				$debug .= "null ";
			}

		}
		
		// DEBUG
		#$this->content->text .= "debug: ".$debug;
		
        // Ausgabe im Block
		if($hashtag){
			$this->content->text .= "<a target=\"_blank\" alt=\"$hashtag\" title=\"".get_string('link_to', 'block_hashtag')."$hashtag\" href='http://www.twitter.com/search/$hashtag'>#$hashtag</a>";
			$this->content->text .= "<br /><br/>".get_string('more_infos', 'block_hashtag')."<a target=\"_blank\" href=\"".get_config('block_hashtag','oncampuspedia_link')."\">oncampuspedia</a>";
		}
		
        // Return the content object
        return $this->content;
    }
}
