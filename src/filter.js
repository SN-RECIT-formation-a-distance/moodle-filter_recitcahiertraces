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
 
var recit = recit || {};
recit.filter = recit.filter || {};
recit.filter.cahiercanada = recit.filter.cahiercanada || {};

recit.filter.cahiercanada.Main = class
{
    constructor(){       
        this.onSave = this.onSave.bind(this);
    }
	
	showFeedback(ccCmId){
		let feedback = document.getElementById(`ctFeedback${ccCmId}`);	
		
		if(feedback !== null){
			feedback.style.display = 'block';
		}
	}
	
    onSave(name, ccCmId, userId){
		let data = {personalNoteId: 0, ccCmId: ccCmId, userId: userId, note: "" };		
		let that = this;
		
        let callback = function(result){
            if(!result.success){
                alert(result.msg);				
                return;
            }

			that.showFeedback(ccCmId);
            alert("L'action a été complétée avec succès.");
        }
		
		let editor = new recit.components.EditorDecorator(name+"Container");
		data.note = editor.getValue();

        recit.http.WebApi.instance().saveStudentNote(data, callback);
    }
}

var recitFilterCahierCanada = null;

recit.utils.onDocumentReady(function(){
    recitFilterCahierCanada = new recit.filter.cahiercanada.Main();
});