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
        this.onSaveAuto = this.onSaveAuto.bind(this);
        this.onReset = this.onReset.bind(this);
        this.onCallback = this.onCallback.bind(this);
        this.init = this.init.bind(this);

        this.inputList = {};
        this.init();
    }

    init(){
        let tmp = document.querySelectorAll(`div[data-pn-name]`);

        for(let item of tmp){
            let name = item.getAttribute('data-pn-name');
            this.inputList[name] = {};
            this.inputList[name].dom = item;
            this.inputList[name].ccCmId = item.getAttribute('data-pn-cccmid');
            this.inputList[name].userId = item.getAttribute('data-pn-userid');
            this.inputList[name].courseId = item.getAttribute('data-pn-courseid');
            this.inputList[name].editor = new recit.components.EditorDecorator(`${name}Container`);
            this.inputList[name].editor.onFocusOutCallback = () => this.onSaveAuto(name);
        }
    }
    
    onSaveAuto(name){
        if(window.confirm(M.str.filter_recitcahiercanada.msgSaveAuto)){
            this.onSave(name);
        }
    }

    onSave(name){
        let input = this.inputList[name];
		let data = {personalNoteId: 0, ccCmId: input.ccCmId, userId: input.userId, note: input.editor.getValue(), courseId: input.courseId };		
        recit.http.WebApi.instance().saveStudentNote(data, (result) => this.onCallback(result));
    }

    onReset(name){
        let input = this.inputList[name];
        let data = {personalNoteId: 0, ccCmId: input.ccCmId, userId: input.userId, note: {text: "", itemid: 0}, courseId: input.courseId };		
        
        if(window.confirm(M.str.filter_recitcahiercanada.msgConfirmReset)){
            recit.http.WebApi.instance().saveStudentNote(data, (result) => this.onCallback(result));
        }
    }

    onCallback(result){
        if(!result.success){
            alert(result.msg);				
            return;
        }

        // refresh the many instances of the integration code
        for(let attr in this.inputList){
            // get all the common editors (same ccCmId)
            if(attr.indexOf(`cccmid${result.data.ccCmId}`) >= 0){
                this.inputList[attr].editor.setValue(result.data.note.text);
                let feedbackList = this.inputList[attr].dom.querySelectorAll(`[id="ctFeedback${this.inputList[attr].ccCmId}"]`);	
                for(let feedback of feedbackList){
                    feedback.style.display = (result.data.isTemplate === 1 ? 'none' : 'block');
                }
            }
        }

        alert(M.str.filter_recitcahiercanada.msgSuccess);
    }
}

var recitFilterCahierCanada = null;

recit.utils.onDocumentReady(function(){
    recitFilterCahierCanada = new recit.filter.cahiercanada.Main();
});