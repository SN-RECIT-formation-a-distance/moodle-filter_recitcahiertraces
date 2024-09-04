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

 namespace filter_recitcahiertraces;


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/mod/recitcahiertraces/classes/PersistCtrl.php");
require_once($CFG->dirroot . "/mod/recitcahiertraces/classes/recitcommon/Utils.php");

use recitcahiertraces\Utils;
use recitcahiertraces\PersistCtrl;
use moodle_url;

class text_filter extends \core_filters\text_filter {

    protected $nbEditorAtto = 0;

     /**
     * Set any context-specific configuration for this filter.
     *
     * @param context $context The current context.
     * @param array $localconfig Any context-specific configuration for this filter.
     */
    public function __construct($context, array $localconfig) {
        parent::__construct($context, $localconfig);
    }

	public function setup($page, $context) {
		global $CFG, $OUTPUT;

        $page->requires->js(new moodle_url($CFG->wwwroot .'/filter/recitcahiertraces/RecitApi.js'), true);
        $page->requires->js(new moodle_url($CFG->wwwroot .'/filter/recitcahiertraces/filter.js'), true);

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
		global $DB, $USER, $PAGE, $COURSE;

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
                        $obj = PersistCtrl::getInstance($DB)->getUserNote(null, $USER->id, $json->intCode, $COURSE->id);
                    }
                    catch(Exception $ex){
                    }   
                    
                    if($obj == null){
                        $replace = get_string('codenotfound', "filter_recitcahiertraces")." ".$json->intCode;
                        $text = $this->str_replace_first($match[0], $replace, $text);
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
        $context = \context_course::instance($dbData->noteDef->group->ct->courseId);
        return Utils::createEditorHtml(true, "{$name}_container", $name, $dbData->noteContent->text, $intCode->nbLines, $context, $dbData->noteContent->itemid);
    }

    protected function getPersonalNoteFormEmbedded($dbData, $userId, $intCode){
        global $CFG, $PAGE;

        $this->nbEditorAtto++;
        $nCmId = $PAGE->cm->id;
        $name = sprintf( "ncmid%satto%s", $nCmId, $this->nbEditorAtto);
       
        $result = "<div class='filter-recitcahiertraces_personal-note-embedded' data-pn-name='$name' data-pn-nid='{$dbData->noteDef->id}' data-pn-unid='{$dbData->id}' data-pn-ncmid='{$nCmId}' data-pn-userid='$userId' data-pn-courseid='{$dbData->noteDef->group->ct->courseId}'>";	
        $result .= "<div style='display: flex; justify-content: space-between;'>";
        $result .= sprintf("<label class='title' style='%s'>%s</label>", (!empty($intCode->color) ? "color: {$intCode->color}" : ""), $dbData->noteDef->title);
        $result .= "<span>";
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

        $result .= "<div id='{$name}_loading' class='filter-recitcahiertraces_recit-loading' style='display:none;'>";
        $result .= "<i class='fa fa-spinner fa-pulse fa-3x fa-fw'></i>";
        $result .= "<span class='sr-only'>".get_string('loading', "filter_recitcahiertraces")."...</span>";
        $result .= "</div>";

        $result .= "</div>";		
        		
        return $result;
    }
}
