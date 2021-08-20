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
 * @package   filter_recitcahiertraces
 * @copyright 2019 RÉCIT 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . "/mod/recitcahiertraces/classes/PersistCtrl.php");
require_once($CFG->dirroot . "/local/recitcommon/php/Utils.php");

use recitcommon\Utils;
use recitcahiertraces\PersistCtrl;

class filter_recitcahiertraces extends moodle_text_filter {
   
    protected $nbEditorAtto = 0;
  //  protected $editorOption = "1"; // 1 = atto, 2 = recit editor

     /**
     * Set any context-specific configuration for this filter.
     *
     * @param context $context The current context.
     * @param array $localconfig Any context-specific configuration for this filter.
     */
    public function __construct($context, array $localconfig) {
        //global $PAGE, $CFG;

        parent::__construct($context, $localconfig);

       /* if($context instanceof context_course){
            // the CSS needs to be loaded here because on the function setup it is too late
            $cssRecitEditor = $CFG->wwwroot .'/local/recitcommon/js/recit_rich_editor/build/index.css';
            if(file_exists($cssRecitEditor)){
                $PAGE->requires->css(new moodle_url($cssRecitEditor), true);
            }
        }*/
    }

	public function setup($page, $context) {
		global $CFG, $OUTPUT;

       // $this->editorOption = get_config('filter_recitcahiertraces', 'editorOption');

		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/RecitApi.js'), true);
		$page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/Components.js'), true);
        $page->requires->js(new moodle_url($CFG->wwwroot . '/local/recitcommon/js/Utils.js'), true);
        $page->requires->js(new moodle_url($CFG->wwwroot .'/filter/recitcahiertraces/filter.js'), true);        

     /*   if($this->editorOption == "2"){
            $page->requires->js(new moodle_url($CFG->wwwroot .'/local/recitcommon/js/recit_rich_editor/build/index.js'), true);
        }*/

        $page->requires->string_for_js('msgSuccess', 'filter_recitcahiertraces');
        $page->requires->string_for_js('msgConfirmReset', 'filter_recitcahiertraces');
       // $page->requires->string_for_js('msgSaveAuto', 'filter_recitcahiertraces');
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
                    try{
                        $obj = PersistCtrl::getInstance($DB)->getUserNote(null, $USER->id, $json->intCode);
                    }
                    catch(Exception $ex){
                        return $ex->GetMessage();
                    }   
                    
                    if($obj == null){
                        $text = "Cahier de traces v2 - Erreur: code d'intégration intCode: $json->intCode introuvable.";
                    }
				}

				// if $obj is null then the note does not exist
				if($obj != null){
					if(!isset($json->nbLines)){ $json->nbLines = 15; }
                    if(!isset($json->color)){ $json->color = ''; }
                    if(!isset($json->btnSaveVariant)){ $json->btnSaveVariant = 'btn-success'; }
                    if(!isset($json->btnResetVariant)){ $json->btnResetVariant = 'btn-secondary'; }
                    if(!isset($json->inputOption)){ $json->inputOption = '1'; }

                    $replace = $this->getPersonalNoteForm($obj, $USER->id, $json);
                    $text = $this->str_replace_first($match[0], $replace, $text);
				}
			}
		}

		return $text;
	}
    
	public function getPersonalNoteForm($dbData, $userId, $intCode){	
        return $this->getPersonalNoteFormEmbedded($dbData, $userId, $intCode);
    }	
    
    protected function getEditorOption($name, $dbData, $intCode){
       /* if($this->editorOption == "2"){
            return "<div id='{$name}_container' data-format='recit_rich_editor'>{$dbData->note->text}</div>";
        }
        else{*/
            $context = \context_course::instance($dbData->noteDef->group->ct->courseId);
            return Utils::createEditorHtml(true, "{$name}_container", $name, $dbData->noteContent->text, $intCode->nbLines, $context, $dbData->noteContent->itemid);
       // }
    }

    protected function getPersonalNoteFormEmbedded($dbData, $userId, $intCode){
        global $CFG, $PAGE;

        $this->nbEditorAtto++;
        $nCmId = $PAGE->cm->id;
        $name = sprintf( "ncmid%satto%s", $nCmId, $this->nbEditorAtto);
       
        $result = "<div class='personal-note-embedded' data-pn-name='$name' data-pn-nid='{$dbData->noteDef->id}' data-pn-ncmid='{$nCmId}' data-pn-userid='$userId' data-pn-courseid='{$dbData->noteDef->group->ct->courseId}'>";	
        $result .= "<div style='display: flex; justify-content: space-between;'>";
        $result .= sprintf("<label class='title' style='%s'>%s</label>", (!empty($intCode->color) ? "color: {$intCode->color}" : ""), $dbData->noteDef->title);
        $result .= "<span>";
//        $result .= "<span class='text-muted p-2'>Cahier de traces <img src='$CFG->wwwroot/filter/recitcahiertraces/pix/icon.png' alt='RÉCIT' width='20px' height='20px'/></span>";
        $result .= "</span>";
        $result .= "</div>";

        $result .= $this->getEditorOption($name, $dbData, $intCode);
        
		if(strlen($dbData->noteDef->teacherTip) > 0){
            $display = ($dbData->isTemplate == 1 ? 'none' : 'block');
            $result .= sprintf("<div id='{$name}_feedback' style='display: $display; margin-top: 1rem;' class='alert alert-warning' role='alert'> <strong>%s</strong><br/>%s</div>", 
                                get_string('teacherTip', "filter_recitcahiertraces"), $dbData->noteDef->teacherTip);
		}
        
        $result .= "<div class='btn-toolbar' style='justify-content: space-between; margin: 1rem 0 1rem 0;'>";
    
        $result .= "<div class='btn-group'>";
        $result .= sprintf("<a href='{$CFG->wwwroot}/mod/recitcahiertraces/view.php?id={$dbData->noteDef->group->ct->mCmId}' class='btn btn-primary action' target='_blank' title='%s'><i class='fa fa-address-book'></i> %s</a>",
                        get_string('seeMyNotes', "filter_recitcahiertraces"), get_string('seeMyNotes', "filter_recitcahiertraces"));
        $result .= "</div>";

        $result .= "<div class='btn-group'>";
        $result .= sprintf("<button class='btn $intCode->btnResetVariant action' onclick='recitFilterCahierTraces.onReset(\"%s\")' title='%s'><i class='fa fa-times-circle'></i> %s</button>", 
						$name, get_string('reset', "filter_recitcahiertraces"), get_string('reset', "filter_recitcahiertraces"));
		$result .= sprintf("<button class='btn $intCode->btnSaveVariant action' onclick='recitFilterCahierTraces.onSave(\"%s\")' title='%s'><i class='fa fa-save'></i> %s</button>", 
                        $name, get_string('save', "filter_recitcahiertraces"), get_string('save', "filter_recitcahiertraces"));
        $result .= "</div>";

        $result .= '</div>';

        $result .= "<div id='{$name}_loading' class='recit-loading' style='display:none;'>";
        $result .= "<i class='fa fa-spinner fa-pulse fa-3x fa-fw'></i>";
        $result .= "<span class='sr-only'>Loading...</span>";
        $result .= "</div>";

        $result .= "</div>";		
        		
        return $result;
    }
}
