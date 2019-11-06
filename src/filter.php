<?php

//require_once("$CFG->libdir/formslib.php");
//require_once("$CFG->libdir/form/editor.php");
require_once($CFG->dirroot . "/mod/recitcahiercanada/common/php/PersistCtrl.php");
require_once($CFG->dirroot . "/mod/recitcahiercanada/common/php/Utils.php");

class filter_recitcahiercanada extends moodle_text_filter {
	
	public function setup($page, $context) {
		global $CFG, $OUTPUT;
		
		$page->requires->js(new moodle_url($CFG->wwwroot . '/mod/recitcahiercanada/common/js/WebApi.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/mod/recitcahiercanada/common/js/Components.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/mod/recitcahiercanada/common/js/Utils.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot .'/filter/recitcahiercanada/filter.js'), true);
	}	
	 
	//<script type="text/javascript" src="http://localhost/moodle/filter/recitcahiercanada/filter.js"></script>

	public function filter($text, array $options = array()) {
		global $DB, $USER;

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
				
				if(isset($json->cccmid)){
					$obj = PersistCtrl::getInstance($DB)->getPersonalNote($json->cccmid, $USER->id);
					// if $obj is null then the note does not exist
					if($obj != null){
						if(!isset($json->nbLines)){
							$json->nbLines = 15;
						}
						$text = str_replace($match[0], $this->getPersonalNoteForm($json->cccmid, $USER->id, $obj->noteTitle, $obj->note, $json->nbLines), $text);
					}
				}
			}
		}

		return $text;
	}

	public function getPersonalNoteForm($ccCmId, $userId, $label, $content, $nbRows){	
		$name = "cccmid$ccCmId";

		// , context_module::instance($cmId)
		$result = "<div>";	
		//$result .= sprintf("<label style='font-weight: 500; color: #555;'>%s: %s</label>", get_string('noteTitle', "filter_recitcahiercanada"), $label);
		$result .= sprintf("<label class='recitcahierlabel'>%s</label>", $label);
		$result .= Utils::createEditorHtml(true, "{$name}Container", $name, $content, $nbRows);
		$result .= "</br>";
		$result .= sprintf("<button class='recit-btn recit-btn-primary' onclick='recitFilterCahierCanada.onSave(\"%s\", \"%ld\", \"%ld\")'>%s</button>", 
						$name, $ccCmId, $userId,  get_string('save', "filter_recitcahiercanada"));
		$result .= "</div>";				
		return $result;
		/*$name = "cccmid$ccCmId";
		$result = sprintf("<div id='%sContainer' data-format='%s'>", $name, get_class(editors_get_preferred_editor()));
		$result .= sprintf("<label style='font-weight: 500; color: #555;'>%s: %s</label>", get_string('noteTitle', "filter_recitcahiercanada"), $label);
		$editor = new MoodleQuickForm_editor($name, $label, array("id" => $name), array('autosave' => false));
		$editor->setValue(array("text" => $content));
		$result .= $editor->toHtml();
		$result .= "</br>";
		$result .= sprintf("<button class='recit-btn recit-btn-primary' onclick='recitFilterCahierCanada.onSave(\"%s\", \"%ld\", \"%ld\")'>%s</button>", 
						$name, $ccCmId, $userId,  get_string('save', "filter_recitcahiercanada"));
		$result .= "</div>";
		return $result;*/
	}	
}
