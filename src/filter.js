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
 
var recit = recit || {};
recit.filter = recit.filter || {};
recit.filter.cahiertraces = recit.filter.cahiertraces || {};

recit.filter.cahiertraces.WebApi = class
{
    constructor(){
        this.saveStudentNote = this.saveStudentNote.bind(this);
        this.webApi = new recit.http.WebApi();
        this.gateway = `${M.cfg.wwwroot}/mod/recitcahiertraces/classes/WebApi.php`;
    }

    saveStudentNote(data, onSuccess){
        let options = {};
        options.service = "saveUserNote";
        options.data = data;
        options.flags = {mode: 's'};
        this.webApi.post(this.gateway, options, onSuccess);
    }
}

recit.filter.cahiertraces.Main = class
{
    constructor(){       
        this.onSave = this.onSave.bind(this);
        //this.onSaveAuto = this.onSaveAuto.bind(this);
        this.onReset = this.onReset.bind(this);
        this.onCallback = this.onCallback.bind(this);
        this.init = this.init.bind(this);

        this.inputList = {};
        this.webApi = null;
        this.init();
    }

    init(){
        let tmp = document.querySelectorAll(`div[data-pn-name]`);

        for(let item of tmp){
            let name = item.getAttribute('data-pn-name');
            this.inputList[name] = {};
            this.inputList[name].dom = item;
            this.inputList[name].nCmId = item.getAttribute('data-pn-ncmid');
            this.inputList[name].nId = item.getAttribute('data-pn-nid');
            this.inputList[name].userId = item.getAttribute('data-pn-userid');
            this.inputList[name].courseId = item.getAttribute('data-pn-courseid');
            this.inputList[name].view = this.inputList[name].dom.querySelector(`[id="${name}_view"]`) || null;
            this.inputList[name].loading = this.inputList[name].dom.querySelector(`[id="${name}_loading"]`);
            this.inputList[name].editor = new recit.components.EditorDecorator(`${name}_container`);
            this.inputList[name].feedback = this.inputList[name].dom.querySelector(`[id="${name}_feedback"]`);
        }

        this.createCssClasses();

        this.webApi = new recit.filter.cahiertraces.WebApi();
    }

    createCssClasses(){
        let style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = `.recit-loading { display: none; font-size: 40px; position: fixed; top: 50%; left: 50%; z-index: 2000; 
                            transform: translate(-50%, -50%); transform: -webkit-translate(-50%, -50%); transform: -moz-translate(-50%, -50%); transform: -ms-translate(-50%, -50%); }`;
        document.getElementsByTagName('head')[0].appendChild(style);
    }

    onCancel(name){
        this.inputList[name].editor.setValue(this.inputList[name].view.innerHTML);
    }

    onSave(name){
        let input = this.inputList[name];
        let data = {unId: 0, nId: input.nId, nCmId: input.nCmId, userId: input.userId, note: input.editor.getValue(), courseId: input.courseId };		
        this.webApi.saveStudentNote(data, (result) => this.onCallback(result));
        input.loading.style.display = 'block';
    }

    onReset(name){
        let input = this.inputList[name];
        let data = {unId: 0,  nId: input.nId, nCmId: input.nCmId, userId: input.userId, note: {text: "", itemid: 0}, courseId: input.courseId };		
        
        if(window.confirm(M.str.filter_recitcahiertraces.msgConfirmReset)){
            this.webApi.saveStudentNote(data, (result) => this.onCallback(result));
            input.loading.style.display = 'block';
        }
    }

    onCallback(result){
        if(!result.success){
            alert(result.msg);				
            return;
        }

        // refresh the many instances of the integration code
        for(let attr in this.inputList){
            // get all the common editors (same nCmId)
            if(attr.indexOf(`ncmid${result.data.nCmId}`) >= 0){
                this.inputList[attr].editor.setValue(result.data.noteContent.text);

                if(this.inputList[attr].view !== null){
                    this.inputList[attr].view.innerHTML = result.data.noteContent.text;
                }
                
                if(this.inputList[attr].feedback !== null){
                    this.inputList[attr].feedback.style.display = (result.data.isTemplate === 1 ? 'none' : 'block');
                }
                
                this.inputList[attr].loading.style.display = 'none';
            }
        }

        alert(M.str.filter_recitcahiertraces.msgSuccess);
    }
}

var recitFilterCahierTraces = null;

recit.utils.onDocumentReady(function(){
recitFilterCahierTraces = new recit.filter.cahiertraces.Main();
});