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
       // $page->requires->string_for_js('msgSaveAuto', 'filter_recitcahiercanada');
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
                        $obj = CahierTracesPersistCtrl::getInstance($DB)->getPersonalNote(null, $USER->id, $json->intCode, $PAGE->cm->id);					
                    }
                    catch(Exception $ex){
                        return $ex->GetMessage();
                    }   
                    
                    if($obj == null){
                        $text = "Erreur: code d'intégration intCode: $json->intCode introuvable.";
                    }
					$intCode = $json->intCode;
				}
				else if(isset($json->cccmid)){
                    try{
                        $obj = CahierTracesPersistCtrl::getInstance($DB)->getPersonalNote($json->cccmid, $USER->id);
                    }
                    catch(Exception $ex){
                        return $ex->GetMessage();
                    } 
                    
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
                    if(!isset($json->inputOption)){ $json->inputOption = '1'; }

                    $replace = $this->getPersonalNoteForm($obj, $USER->id, $json);
                    $text = $this->str_replace_first($match[0], $replace, $text);
				}
			}
		}

		return $text;
	}

    
	public function getPersonalNoteForm($dbData, $userId, $intCode){	
        switch($intCode->inputOption){
            case '1':
                return $this->getPersonalNoteFormEmbedded($dbData, $userId, $intCode);
            case '2':
                return $this->getPersonalNoteFormPopup($dbData, $userId, $intCode);
        }
    }	
    
    protected function getPersonalNoteFormEmbedded($dbData, $userId, $intCode){
        global $CFG;

        $this->nbEditorAtto++;
        $name = sprintf( "cccmid%satto%s", $dbData->ccCmId, $this->nbEditorAtto);

        $context = context_course::instance($dbData->courseId);
        
		$result = "<div style='padding: 1rem;' data-pn-name='$name' data-pn-cccmid='$dbData->ccCmId' data-pn-userid='$userId' data-pn-courseid='$dbData->courseId'>";	
		$result .= sprintf("<label style='font-weight: 500; font-size: 20px; color: {$intCode->color}' class='recitcahierlabel'>%s</label>", $dbData->noteTitle);
		$result .= Utils::createEditorHtml(true, "{$name}_container", $name, $dbData->note->text, $intCode->nbLines, $context, $dbData->note->itemid);
		$result .= "<br/>";

		if(strlen($dbData->teacherTip) > 0){
            $display = ($dbData->isTemplate == 1 ? 'none' : 'block');
            $result .= sprintf("<div id='{$name}_feedback' style='display: $display;' class='alert alert-warning' role='alert'> <strong>%s</strong><br/>%s</div>", 
                                get_string('teacherTip', "filter_recitcahiercanada"), $dbData->teacherTip);
			$result .= "<br/>";	
		}
        
        $result .= "<div class='btn-group' style='display: flex; justify-content: center'>";
        $result .= sprintf("<button class='btn $intCode->btnResetVariant' onclick='recitFilterCahierCanada.onReset(\"%s\")'>%s</button>", 
						$name, get_string('reset', "filter_recitcahiercanada"));
		$result .= sprintf("<button class='btn $intCode->btnSaveVariant' onclick='recitFilterCahierCanada.onSave(\"%s\")'>%s</button>", 
                        $name, get_string('save', "filter_recitcahiercanada"));
        $result .= '</div>';

        $result .= "<div id='{$name}_loading' style='display: none; font-size: 40px; position: fixed; top: 50%; left: 50%; z-index: 1000; color: #efefef;
        transform: translate(50%, -50%); transform: -webkit-translate(50%, -50%); transform: -moz-translate(50%, -50%); transform: -ms-translate(50%, -50%);'>";
        $result .= "<i class='fa fa-spinner fa-pulse fa-3x fa-fw'></i>";
        $result .= "<span class='sr-only'>Loading...</span>";
        $result .= "</div>";

        $result .= "</div>";		
        		
        return $result;
    }

    protected function getPersonalNoteFormPopup($dbData, $userId, $intCode){
        global $CFG;

        $this->nbEditorAtto++;
        $name = sprintf( "cccmid%satto%s", $dbData->ccCmId, $this->nbEditorAtto);

        $context = context_course::instance($dbData->courseId);

        $result = "<div class='card' style='margin: 3rem;'  data-pn-name='$name' data-pn-cccmid='$dbData->ccCmId' data-pn-userid='$userId' data-pn-courseid='$dbData->courseId'>";
        $result .= "<div class='card-header' style='color: #373a3c;'><strong>{$dbData->noteTitle}</strong><span class='pull-right text-muted p-2'>Cahier de traces <img src='$CFG->wwwroot/filter/recitcahiercanada/pix/icon.png' alt='RÉCIT' width='20px' height='20px'/></span></div>";
        $result .= "<div class='card-body' style='min-height: 150px;' id='{$name}_view'>";
        //$result .= "<h5 class='card-title'>Note: {$dbData->noteTitle}</h5>";
        //$result .= "<p class='card-text'>{$dbData->note->text}</p>";
            $result .= "{$dbData->note->text}";        
        $result .= "</div>";

        $result .= "<div class='card-body'>";
            if(strlen($dbData->teacherTip) > 0){
                $display = ($dbData->isTemplate == 1 ? 'none' : 'block');
                $result .= sprintf("<div id='{$name}_feedback' style='display: $display;' class='alert alert-warning' role='alert'> <strong>%s</strong><br/>%s</div>", 
                                    get_string('teacherTip', "filter_recitcahiercanada"), $dbData->teacherTip);
            }
        $result .= "</div>";

        $result .= "<div class='card-footer'>";
        
        $result .= "<div class='btn-group'>";
        $result .= sprintf("<button class='btn btn-secondary btn-sm' onclick='recitFilterCahierCanada.onReset(\"%s\")'><i class='fa fa-times-circle'></i> %s</button>", $name, get_string('reset', "filter_recitcahiercanada"));
        $result .= sprintf("<button class='btn btn-primary btn-sm' data-toggle='modal' data-target='#{$name}_modal'><i class='fa fa-edit'></i> %s</button>", get_string('modify', "filter_recitcahiercanada"));
        $result .= "</div>";
        $result .= "<a href='{$CFG->wwwroot}/mod/recitcahiercanada/view.php?id={$dbData->mcmId}' class='btn btn-link btn-sm pull-right' target='_blank'><i class='fa fa-pencil'></i>Voir mes notes</a>";
        $result .= "</div>";        

        $modal = "<div class='modal fade' id='{$name}_modal' tabindex='-1' role='dialog' aria-labelledby='modal' aria-hidden='true' data-backdrop='static'>";
            $modal .= '<div class="modal-dialog" role="document" style="max-width: 800px">';
                $modal .= '<div class="modal-content">';
                    $modal .= '<div class="modal-header">';
                        $modal .= "<h5 class='modal-title'>{$dbData->noteTitle}</h5>";
                        $modal .= sprintf("<button type='button' class='close' data-dismiss='modal' aria-label='Close' onclick='recitFilterCahierCanada.onCancel(\"%s\")'><span aria-hidden='true'>&times;</span></button>", $name);
                    $modal .= '</div>';
                    $modal .= '<div class="modal-body">';                        
                    $modal .= Utils::createEditorHtml(true, "{$name}_container", $name, $dbData->note->text, $intCode->nbLines, $context, $dbData->note->itemid);
                    $modal .= '</div>';

                    $modal .= '<div class="modal-footer">';
                        $modal .= sprintf("<button type='button' class='btn btn-secondary' data-dismiss='modal' onclick='recitFilterCahierCanada.onCancel(\"%s\")'>%s</button>", $name, get_string('cancel', "filter_recitcahiercanada"));
                        $modal .= sprintf("<button type='button' class='btn btn-primary' data-dismiss='modal' onclick='recitFilterCahierCanada.onSave(\"%s\")'>%s</button>", $name, get_string('save', "filter_recitcahiercanada"));
                    $modal .= '</div>';
                $modal .= '</div>';
            $modal .= '</div>';
        $modal .= '</div>';

        $result .= $modal;

        $result .= "<div id='{$name}_loading' class='recit-loading'>";
        $result .= "<i class='fa fa-spinner fa-pulse fa-3x fa-fw'></i>";
        $result .= "<span class='sr-only'>Loading...</span>";
        $result .= "</div>";

        $result .= "</div>";

        return $result;	
    }
}
