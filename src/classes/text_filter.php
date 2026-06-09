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

        if ($PAGE->cm == null) {
            return $text;
        }

        // ATTENTION: other filter plugins (like Generico) may match this condition too
        if (preg_match_all('~\{(?:[^{}]|(?R))*\}~', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $matches = $matches[0];

            foreach ($matches as $match) {
                // $match[0] = text matched
                // $match[1] = offset
                $json = json_decode($match[0]);

                // Skip tokens that are not note tokens (e.g. from Generico or other filters).
                if (!is_object($json) || !isset($json->intCode)) {
                    continue;
                }

                $obj = null;
                try {
                    $obj = PersistCtrl::getInstance($DB)->getUserNote(null, $USER->id, $json->intCode, $COURSE->id);
                } catch (\Exception $ex) {
                    // Note lookup failed; $obj stays null and we show the error below.
                }

                if ($obj == null) {
                    $replace = get_string('codenotfound', "filter_recitcahiertraces") . " " . $json->intCode;
                    $text = $this->str_replace_first($match[0], $replace, $text);
                    continue;
                }

                // Validate and sanitize all token options before rendering.
                $json->nbLines       = isset($json->nbLines) && is_numeric($json->nbLines)
                                        ? max(1, (int)$json->nbLines) : 15;
                $json->color         = $this->sanitizeCssColor(isset($json->color) ? (string)$json->color : '');
                $json->btnSaveVariant  = $this->sanitizeBootstrapVariant(
                                            isset($json->btnSaveVariant) ? (string)$json->btnSaveVariant : '',
                                            'btn-success');
                $json->btnResetVariant = $this->sanitizeBootstrapVariant(
                                            isset($json->btnResetVariant) ? (string)$json->btnResetVariant : '',
                                            'btn-secondary');
                $json->inputOption   = (isset($json->inputOption) && in_array((string)$json->inputOption, ['1', '2'], true))
                                        ? (string)$json->inputOption : '1';

                $replace = $this->getPersonalNoteForm($obj, $USER->id, $json);
                $text = $this->str_replace_first($match[0], $replace, $text);
            }
        }

        return $text;
    }

    public function getPersonalNoteForm($dbData, $userId, $intCode) {
        return $this->getPersonalNoteFormEmbedded($dbData, $userId, $intCode);
    }

    protected function getEditorOption($name, $dbData, $intCode) {
        $context = \context_course::instance($dbData->noteDef->group->ct->courseId);
        return Utils::createEditorHtml(true, "{$name}_container", $name, $dbData->noteContent->text, $intCode->nbLines, $context, $dbData->noteContent->itemid);
    }

    protected function getPersonalNoteFormEmbedded($dbData, $userId, $intCode) {
        global $CFG, $PAGE;

        $this->nbEditorAtto++;

        // Cast all integer values — never trust them raw from DB objects or caller.
        $nCmId     = (int)$PAGE->cm->id;
        $noteDefId = (int)$dbData->noteDef->id;
        $noteUnId  = (int)$dbData->id;
        $courseId  = (int)$dbData->noteDef->group->ct->courseId;
        $mCmId     = (int)$dbData->noteDef->group->ct->mCmId;

        // $name is derived from two integers — safe but still run through s() when placed in HTML.
        $name = sprintf("ncmid%datto%d", $nCmId, $this->nbEditorAtto);

        // data-pn-userid is intentionally omitted: the JS reads M.cfg.userid from the
        // Moodle page session instead, reducing DOM-based IDOR attack surface and
        // unnecessary personal-data exposure (Law 25 data minimisation).
        $result  = "<div class='filter-recitcahiertraces_personal-note-embedded'";
        $result .= " data-pn-name='" . s($name) . "'";
        $result .= " data-pn-nid='$noteDefId'";
        $result .= " data-pn-unid='$noteUnId'";
        $result .= " data-pn-ncmid='$nCmId'";
        $result .= " data-pn-courseid='$courseId'";
        $result .= ">";

        $result .= "<div style='display: flex; justify-content: space-between;'>";
        $colorStyle = !empty($intCode->color) ? 'color: ' . s($intCode->color) : '';
        $result .= sprintf("<label class='title' style='%s'>%s</label>", $colorStyle, s($dbData->noteDef->title));
        $result .= "<span></span>";
        $result .= "</div>";

        $result .= $this->getEditorOption($name, $dbData, $intCode);

        if (strlen($dbData->noteDef->teacherTip) > 0) {
            $display = ($dbData->isTemplate == 1 ? 'none' : 'block');
            // format_text() runs Moodle's HTML purifier, stripping unsafe tags while
            // preserving the rich-text content teachers enter via the Moodle editor.
            $teacherTip = format_text($dbData->noteDef->teacherTip, FORMAT_HTML, ['context' => $this->context]);
            $result .= sprintf(
                "<div id='%s_feedback' style='display: %s; margin-top: 1rem;' class='alert alert-warning' role='alert'>"
                . "<strong>%s</strong><br/>%s</div>",
                s($name), $display,
                get_string('teacherTip', "filter_recitcahiertraces"),
                $teacherTip
            );
        }

        $result .= "<div class='btn-toolbar' style='justify-content: space-between; margin: 1rem 0 1rem 0;'>";

        // Build the URL via moodle_url so path and query parameters are properly encoded.
        $viewUrl = (new moodle_url('/mod/recitcahiertraces/view.php', ['id' => $mCmId]))->out(false);
        $result .= "<div class='btn-group'>";
        $result .= sprintf(
            "<a href='%s' class='btn btn-primary action' target='_blank' title='%s'>"
            . "<i class='fa fa-address-book'></i> %s</a>",
            s($viewUrl),
            get_string('seeMyNotes', "filter_recitcahiertraces"),
            get_string('seeMyNotes', "filter_recitcahiertraces")
        );
        $result .= "</div>";

        $result .= "<div class='btn-group'>";
        // $intCode->btnResetVariant and btnSaveVariant have already been whitelisted
        // to a Bootstrap variant string in filter(); s() is still applied for defence-in-depth.
        $result .= sprintf(
            "<button class='btn %s action' onclick='recitFilterCahierTraces.onReset(\"%s\")' title='%s'>"
            . "<i class='fa fa-times-circle'></i> %s</button>",
            s($intCode->btnResetVariant), s($name),
            get_string('reset', "filter_recitcahiertraces"),
            get_string('reset', "filter_recitcahiertraces")
        );
        $result .= sprintf(
            "<button class='btn %s action' onclick='recitFilterCahierTraces.onSave(\"%s\")' title='%s'>"
            . "<i class='fa fa-save'></i> %s</button>",
            s($intCode->btnSaveVariant), s($name),
            get_string('save', "filter_recitcahiertraces"),
            get_string('save', "filter_recitcahiertraces")
        );
        $result .= "</div>";

        $result .= '</div>';

        $result .= "<div id='" . s($name) . "_loading' class='filter-recitcahiertraces_recit-loading' style='display:none;'>";
        $result .= "<i class='fa fa-spinner fa-pulse fa-3x fa-fw'></i>";
        $result .= "<span class='sr-only'>" . get_string('loading', "filter_recitcahiertraces") . "...</span>";
        $result .= "</div>";

        $result .= "</div>";

        return $result;
    }

    /**
     * Allow only safe CSS colour literals: hex, rgb(), rgba(), or plain colour names.
     * Blocks CSS expressions, url(), javascript:, and other injection vectors.
     */
    private function sanitizeCssColor(string $color): string {
        if (empty($color)) {
            return '';
        }
        $pattern = '/^(#[0-9a-fA-F]{3,8}'
            . '|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)'
            . '|rgba\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*(?:0|1|0?\.\d+)\s*\)'
            . '|[a-zA-Z]{2,30}'
            . ')$/';
        return preg_match($pattern, $color) ? $color : '';
    }

    /**
     * Whitelist Bootstrap button variant class names.
     * Returns $default when the supplied value is not in the allowed list.
     */
    private function sanitizeBootstrapVariant(string $variant, string $default): string {
        static $allowed = [
            'btn-primary', 'btn-secondary', 'btn-success', 'btn-danger',
            'btn-warning', 'btn-info', 'btn-light', 'btn-dark', 'btn-link',
            'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-success',
            'btn-outline-danger', 'btn-outline-warning', 'btn-outline-info',
            'btn-outline-light', 'btn-outline-dark',
        ];
        return in_array($variant, $allowed, true) ? $variant : $default;
    }
}
