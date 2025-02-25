var recit = recit || {};
recit.http = recit.http || {};

recit.http.contentType = {
    postData: "application/x-www-form-urlencoded; charset=UTF-8",
    json: 'application/json; charset=utf-8',
    file: 'multipart/form-data'
};

recit.http.responseType = {
    text: 'text',
    json: 'json',
    octetStream: 'octet-stream'
};

recit.http.HttpRequest = class
{
    constructor(){
        this.useCORS = false;
        this.timeout = 0; // in ms
        this.inProgress = false;

        this.onLoad = this.onLoad.bind(this);
        this.onError = this.onError.bind(this);
        this.onLoadEnd = this.onLoadEnd.bind(this);
        this.onTimeOut = this.onTimeOut.bind(this);

        this.xhr = new XMLHttpRequest();
        this.xhr.onload = this.onLoad;
        this.xhr.onerror = this.onError;
        this.xhr.onloadend = this.onLoadEnd;
        this.xhr.ontimeout = this.onTimeOut;

        this.clientOnLoad = null;
        this.clientOnError = null;
        this.clientOnLoadEnd = null
        this.contentType = null;
        this.responseDataType = null;
    }

    send(method, url, data, onSuccess, onError, onComplete, contentType, responseDataType){
        // force to await in order to execute one call at time (multiples calls causes the slowness on PHP)
        if(this.inProgress){
            setTimeout(() => this.send(method, url, data, onSuccess, onError, onComplete, contentType, responseDataType), 500);
            return;
        }
        
        this.clientOnLoad = onSuccess || null;
        this.clientOnError = onError || null;
        this.clientOnLoadEnd = onComplete || null;    
        this.contentType = contentType || recit.http.contentType.json;  
        this.responseDataType = responseDataType || recit.http.responseType.json;
        
        this.xhr.open(method, url, true);
        this.xhr.setRequestHeader('Content-Type', contentType); // header sent to the server, specifying a particular format (the content of message body)
        this.xhr.setRequestHeader('Accept', responseDataType); // what kind of response to expect.
        
        if(this.useCORS){
            if ("withCredentials" in this.xhr) {            
                this.xhr.withCredentials = true;
            } 
            else if (typeof XDomainRequest !== "undefined") {
                // Otherwise, check if XDomainRequest. XDomainRequest only exists in IE, and is IE's way of making CORS requests.
                this.xhr = new XDomainRequest();
                this.xhr.open(method, url);
            } 
            else {
                throw new Error('CORS not supported');
            }
        }
        
        if(this.timeout > 0){ 
            this.xhr.timeout = this.timeout; 
        }

        this.inProgress = true;
        this.xhr.send(data);
    }

    onLoad(event){
        if(this.clientOnLoad !== null){
            let result = null;

            try{               
                switch(this.responseDataType){
                    case recit.http.responseType.json: result = JSON.parse(event.target.response); break;
                    default: result = event.target.response; // text
                }
            }
            catch(error){
                console.log(error, this);
            }
            
            this.clientOnLoad.call(this, result);
        }
    }

    onError(event){
        if(this.clientOnError !== null){
            this.clientOnError.call(this, event.target, event.target.statusText);
        }
        else{
            console.log("Error:" + event.target);
        }
    }

    onLoadEnd(event){
        if(this.clientOnLoadEnd !== null){ 
            this.clientOnLoadEnd.call(event.target);
        }
        this.inProgress = false;
    }

    onTimeOut(event){
        console.log("Cancelled HTTP request: timeout")
    }
};

recit.http.WebApi = class
{
    constructor(){
        //this.gateway = this.getGateway();
        this.gateway = "";
        this.http = new recit.http.HttpRequest();
        this.domVisualFeedback = null;

        this.post = this.post.bind(this);
        this.onError = this.onError.bind(this);
        this.onComplete = this.onComplete.bind(this);
    }

    /*getGateway(){
        return `${M.cfg.wwwroot}/local/recitcommon/php/RecitApi.php`;
    }*/
    
    onError(jqXHR, textStatus) {
        alert("Error on server communication ("+ textStatus +").\n\nSee console for more details");
        console.log(jqXHR);
    };

    post(url, data, callbackSuccess, callbackError, skipFeedback){
        skipFeedback = (typeof skipFeedback === 'undefined' ? false : skipFeedback);
        
        if(skipFeedback){
            this.showLoadingFeedback();
        }
        
        callbackError = callbackError || this.onError;
        data = JSON.stringify(data);

        this.http.send("post", url, data, callbackSuccess, callbackError, this.onComplete);
    }

    onComplete(){
        this.hideLoadingFeedback();
    }

    showLoadingFeedback(){
        if(this.domVisualFeedback === null){ return; }
        this.domVisualFeedback.style.display = "block";
    }

    hideLoadingFeedback(){
        if(this.domVisualFeedback === null){ return; }
        this.domVisualFeedback.style.display = "none";
    }

}


/////////////////////////////////////////////////////////////////////////////////////////////////
// RICH EDITOR WRAPPER (Design Pattern Decorator)
/////////////////////////////////////////////////////////////////////////////////////////////////
recit.components = recit.components || {}
recit.components.EditorDecorator = class
{
    constructor(id){
        this.init = this.init.bind(this);
        this.onFocusOut = this.onFocusOut.bind(this);

		this.id = id;
        this.dom = document.getElementById(this.id);
        this.format = this.dom.getAttribute("data-format");
        this.onFocusOutCallback = null;

        this.init();
    }

    checkDom(){
        return (this.dom !== null);
    }

    init(){
        if(!this.checkDom()){ return; }

        switch(this.format){
            case 'atto_texteditor':
                break;
            case 'recit_rich_editor': // created manually
                window.RecitRichEditorCreateInstance(this.dom, null, 'word');
                break;
            case 'recit_texteditor':    // created by Utils.createEditor
                break;
        }
    }

    onFocusOut(){
        if(!this.checkDom()){ return; }

        if(this.onFocusOutCallback !== null){
            this.onFocusOutCallback();
        }
    }

    show(){
        if(!this.checkDom()){ return; }
        
        switch(this.format){
            case 'atto_texteditor':
                let attoContent = this.dom.querySelector(".editor_atto_content");
            
                if(attoContent.onblur === null){
                    attoContent.onblur = this.onFocusOut;
                }
                break;
        }

        this.dom.style.display = 'block';
    }

    close(){
        this.setValue("");
    }

    setValue(value){
        if(!this.checkDom()){ return; }

        switch(this.format){
            case 'atto_texteditor':
                this.dom.getElementsByClassName("editor_atto_content")[0].innerHTML = value;
                this.dom.querySelector(`[name="${this.id}[text]"]`).value = value;
                //this.atto.editor.setHTML(value);
                break;
            case 'editor_tiny\\editor':
                this.dom.getElementsByTagName("textarea")[0].value = value;
                this.dom.querySelector('iframe').contentDocument.body.innerHTML = value;
                break;
            case 'textarea_texteditor':
                this.dom.getElementsByTagName("textarea")[0].value = value;
                break;
            case 'recit_rich_editor':
            case 'recit_texteditor':
                this.dom.querySelector(`[data-recit-rich-editor="content"]`).innerHTML = value;
                break;
            default: 
                alert("Editor: unknown format");
        }
    }

    setEditorValuesFromDOM(obj){
        for(let attr in obj){
            let name = `${this.id}[${attr}]`;
            let el = this.dom.querySelector(`[name="${name}"]`);
            if(el !== null){
                obj[attr] = el.value;
            }
        }
    }

    getValue(){        
        let result  = {text: "", format: "", itemid: 0};

        if(!this.checkDom()){ return result; }

        switch(this.format){
            case 'atto_texteditor':
				this.setEditorValuesFromDOM(result);
                break;
            case 'textarea_texteditor':
                result.text = this.dom.getElementsByTagName("textarea")[0].value;
                break;
            case 'recit_rich_editor':
            case 'recit_texteditor':
                result.text = this.dom.querySelector(`[data-recit-rich-editor="content"]`).innerHTML;
                break;
            case 'editor_tiny\\editor':
                this.setEditorValuesFromDOM(result);
                break;
            default: 
                alert("Editor: unknown format");
        }

        return result;
    }
}