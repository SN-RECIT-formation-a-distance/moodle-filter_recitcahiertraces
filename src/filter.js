var recit = recit || {};
recit.filter = recit.filter || {};
recit.filter.cahiercanada = recit.filter.cahiercanada || {};

recit.filter.cahiercanada.Main = class
{
    constructor(){       
        this.onSave = this.onSave.bind(this);
    }

    onSave(name, ccCmId, userId){
        let callback = function(result){
            if(!result.success){
                alert(result.msg);
                return;
            }

            alert("saved");
        }
        let editor = new recit.components.EditorDecorator(name+"Container");

        let data = {personalNoteId: 0, ccCmId: ccCmId, userId: userId, note: editor.getValue() };
        recit.http.WebApi.instance().saveStudentNote(data, callback);
    }
}

var recitFilterCahierCanada = null;

recit.utils.onDocumentReady(function(){
    recitFilterCahierCanada = new recit.filter.cahiercanada.Main();
});