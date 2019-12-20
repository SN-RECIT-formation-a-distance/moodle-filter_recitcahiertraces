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
 * @package   filter_recitcahiercanada
 * @copyright 2019 RÉCIT 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . "/local/recitcommon/php/PersistCtrl.php");
require_once($CFG->dirroot . "/local/recitcommon/php/Utils.php");

class filter_recitcahiercanada extends moodle_text_filter {
	
	public function setup($page, $context) {
		global $CFG, $OUTPUT;
		
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/RecitApi.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/Components.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/Utils.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot .'/filter/recitcahiercanada/filter.js'), true);
	}		

	public function filter($text, array $options = array()) {
		global $DB, $USER, $PAGE;

		if (!is_string($text) or empty($text)) {
			// Non-string data can not be filtered anyway.
			return $text;
		}
	 
		if(preg_match_all('~\{(?:[^{}]|(?R))*\}~', $text,  $matches, PREG_OFFSET_CAPTURE)){
			$matches = $matches[0];
			foreach($matches as $match){
				// $match[0] = text matched 
				// $match[1] = offset
				$json = json_decode($match[0]);
				
				$obj = null;
				if(isset($json->intCode)){
					$obj = PersistCtrl::getInstance($DB)->getPersonalNote(null, $USER->id, $json->intCode, $PAGE->cm->id);					
					$intCode = $json->intCode;
				}
				else if(isset($json->cccmid)){
					$obj = PersistCtrl::getInstance($DB)->getPersonalNote($json->cccmid, $USER->id);
					$intCode = $json->cccmid;
				}

				// if $obj is null then the note does not exist
				if($obj != null){
					if(!isset($json->nbLines)){
						$json->nbLines = 15;
					}
					$text = str_replace($match[0], $this->getPersonalNoteForm($obj->ccCmId, $USER->id, $obj->noteTitle, $obj->note, $json->nbLines, $obj->teacherTip), $text);
				}
				else{
					$text = "Erreur: code d'intégration $intCode introuvable.";
				}
			}
		}

		return $text;
	}

	public function getPersonalNoteForm($ccCmId, $userId, $label, $content, $nbRows, $teacherTip){	
		$name = "cccmid$ccCmId";

		$result = "<div>";	
		$result .= sprintf("<label class='recitcahierlabel'>%s</label>", $label);
		$result .= Utils::createEditorHtml(true, "{$name}Container", $name, $content, $nbRows);
		$result .= "<br/>";

		if(strlen($teacherTip) > 0){
			$result .= sprintf("<div id='ctFeedback$ccCmId' style='display: none;' class='alert alert-warning' role='alert'> <strong>%s</strong><br/>%s</div>", get_string('teacherTip', "filter_recitcahiercanada"), $teacherTip);
			$result .= "<br/>";	
		}
		
		$result .= sprintf("<button class='btn btn-primary' onclick='recitFilterCahierCanada.onSave(\"%s\", \"%ld\", \"%ld\")'>%s</button>", 
						$name, $ccCmId, $userId, get_string('save', "filter_recitcahiercanada"));
		$result .= "</div>";				
		return $result;		
	}	
}
