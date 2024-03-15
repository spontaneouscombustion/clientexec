$(document).ready(function(){
    if ( typeof(gHash) != "undefined" ) {
        $('<input>').attr({
            type: 'hidden',
            name: 'sessionHash',
            value: gHash
        }).appendTo('form');
    }
    // needed in Safari, cuz of https://github.com/clientexec/webapp/issues/779
    // Solution was taken from http://stackoverflow.com/questions/5297122/preventing-cache-on-back-button-in-safari-5
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload()
        }
    };
});

function submitonce(theform){
    if (document.all||document.getElementById){
        for (i=0;i<theform.length;i++){
            var tempobj=theform.elements[i]
            if(tempobj.type.toLowerCase()=="submit"||tempobj.type.toLowerCase()=="button")
            tempobj.disabled=true
        }
    }
}
