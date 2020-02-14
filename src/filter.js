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
        this.onReset = this.onReset.bind(this);
        this.onCallback = this.onCallback.bind(this);
    }
	
	showFeedback(ccCmId){
		let feedback = document.getElementById(`ctFeedback${ccCmId}`);	
		
		if(feedback !== null){
			feedback.style.display = 'block';
		}
	}
	
    onSave(name, ccCmId, userId, courseId){
		let data = {personalNoteId: 0, ccCmId: ccCmId, userId: userId, note: {text: "", itemid: 0}, courseId: courseId };		
        
        let editor = new recit.components.EditorDecorator(name+"Container");
		data.note = editor.getValue();

        recit.http.WebApi.instance().saveStudentNote(data, (result) => this.onCallback(name, result, result.data.note.text, true));
    }

    onReset(name, ccCmId, userId, courseId){
        let data = {personalNoteId: 0, ccCmId: ccCmId, userId: userId, note: {text: "", itemid: 0}, courseId: courseId };		
        
        if(window.confirm(M.str.filter_recitcahiercanada.msgConfirmReset)){
            recit.http.WebApi.instance().saveStudentNote(data, (result) => this.onCallback(name, result, "", false));
        }
    }

    onCallback(name, result, content, showFeedback){
        if(!result.success){
            alert(result.msg);				
            return;
        }

        // refresh the many instances of the integration code
        let commonName = name.substr(0, name.length -1); // remove the editor counter
        let editors = document.querySelectorAll(`textarea[id^="${commonName}"]`);
        for(let el of editors){
            let editor = new recit.components.EditorDecorator(el.getAttribute('id')+"Container");
            editor.setValue(content);
        }
        
        if(showFeedback){
            this.showFeedback(result.data.ccCmId);
        }
        
        alert(M.str.filter_recitcahiercanada.msgSuccess);
    }
}

var recitFilterCahierCanada = null;

recit.utils.onDocumentReady(function(){
    recitFilterCahierCanada = new recit.filter.cahiercanada.Main();
});