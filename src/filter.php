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
require_once($CFG->dirroot . "/local/recitcommon/php/PersistCtrlCahierTraces.php");
require_once($CFG->dirroot . "/local/recitcommon/php/Utils.php");

class filter_recitcahiercanada extends moodle_text_filter {
    
    protected $nbEditorAtto = 0;

	public function setup($page, $context) {
		global $CFG, $OUTPUT;
		
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/RecitApi.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/Components.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/Utils.js'), true);
        $page->requires->js(new moodle_url($CFG->wwwroot .'/filter/recitcahiercanada/filter.js'), true);

        $page->requires->string_for_js('msgSuccess', 'filter_recitcahiercanada');
        $page->requires->string_for_js('msgConfirmReset', 'filter_recitcahiercanada');
        
	}		

    public function str_replace_first($search, $replace, $subject) {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

	public function filter($text, array $options = array()) {
		global $DB, $USER, $PAGE;

		if (!is_string($text) or empty($text)) {
			// Non-string data can not be filtered anyway.
			return $text;
        }
        
        if($PAGE->cm == null){
            return $text;
        }
     
        // ATTENTION: other filter plugins (like Generico) may match this condition too
		if(preg_match_all('~\{(?:[^{}]|(?R))*\}~', $text,  $matches, PREG_OFFSET_CAPTURE)){
            $matches = $matches[0];
            
			foreach($matches as $match){
				// $match[0] = text matched 
				// $match[1] = offset
				$json = json_decode($match[0]);
				
				$obj = null;
				if(isset($json->intCode)){
                    $obj = CahierTracesPersistCtrl::getInstance($DB)->getPersonalNote(null, $USER->id, $json->intCode, $PAGE->cm->id);					
                    if($obj == null){
                        $text = "Erreur: code d'intégration intCode: $json->intCode introuvable.";
                    }
					$intCode = $json->intCode;
				}
				else if(isset($json->cccmid)){
                    $obj = CahierTracesPersistCtrl::getInstance($DB)->getPersonalNote($json->cccmid, $USER->id);
                    if($obj == null){
                        $text = "Erreur: code d'intégration cccmid: $json->cccmid introuvable.";
                    }
                    $intCode = $json->cccmid;                    
				}

				// if $obj is null then the note does not exist
				if($obj != null){
					if(!isset($json->nbLines)){ $json->nbLines = 15; }
                    if(!isset($json->color)){ $json->color = 'inherit'; }
                    if(!isset($json->btnSaveVariant)){ $json->btnSaveVariant = 'btn-primary'; }
                    if(!isset($json->btnResetVariant)){ $json->btnResetVariant = 'btn-secondary'; }

                    $replace = $this->getPersonalNoteForm($obj, $USER->id, $json);
                    $text = $this->str_replace_first($match[0], $replace, $text);
				}
			}
		}

		return $text;
	}

	public function getPersonalNoteForm($dbData, $userId, $intCode){	
        $this->nbEditorAtto++;

		//global $COURSE;
		$name = sprintf( "cccmid%satto%s", $dbData->ccCmId, $this->nbEditorAtto);

		$context = context_course::instance($dbData->courseId);

		$result = "<div style='padding: 1rem;'>";	
		$result .= sprintf("<label style='font-weight: 500; font-size: 20px; color: {$intCode->color}' class='recitcahierlabel'>%s</label>", $dbData->noteTitle);
		$result .= Utils::createEditorHtml(true, "{$name}Container", $name, $dbData->note->text, $intCode->nbLines, $context, $dbData->note->itemid);
		$result .= "<br/>";

		if(strlen($dbData->teacherTip) > 0){
            $result .= sprintf("<div id='ctFeedback{$dbData->ccCmId}' style='display: none;' class='alert alert-warning' role='alert'> <strong>%s</strong><br/>%s</div>", 
                                get_string('teacherTip', "filter_recitcahiercanada"), $dbData->teacherTip);
			$result .= "<br/>";	
		}
        
        $result .= "<div class='btn-group' style='display: flex; justify-content: center'>";
        $result .= sprintf("<button class='btn $intCode->btnResetVariant' onclick='recitFilterCahierCanada.onReset(\"%s\", \"%ld\", \"%ld\", \"%ld\")'>%s</button>", 
						$name, $dbData->ccCmId, $userId, $dbData->courseId, get_string('reset', "filter_recitcahiercanada"));
		$result .= sprintf("<button class='btn $intCode->btnSaveVariant' onclick='recitFilterCahierCanada.onSave(\"%s\", \"%ld\", \"%ld\", \"%ld\")'>%s</button>", 
                        $name, $dbData->ccCmId, $userId, $dbData->courseId, get_string('save', "filter_recitcahiercanada"));
        $result .= '</div>';

        $result .= "</div>";		
        		
		return $result;		
	}	
}
