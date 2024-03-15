// fixes bug with jquery and deprecated webkit
$(document).ready(function(){
    // remove layerX and layerY
    var all = $.event.props,
        len = all.length,
        res = [];
    while (len--) {
      var el = all[len];
      if (el != 'layerX' && el != 'layerY') res.push(el);
    }
    $.event.props = res;
});
// end fix

//need to convert to msg
function setStatus(s, time) { }
function resetStatus(s) {}
function unsetStatus(s, time) {}
function msg(title){
        ce.msg(title);
}
// End msg windows

/**
 * Function : dump()
 * Arguments: The data - array,hash(associative array),object
 *    The level - OPTIONAL
 * Returns  : The textual representation of the array.
 * This function was inspired by the print_r function of PHP.
 * This will accept some data as the argument and return a
 * text that will be a more readable version of the
 * array/hash/object that is given.
 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
 */
function dump(arr,level) {
	var dumped_text = "";
	if(!level) {
            level = 0;
        }

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) {
            level_padding += "    ";
        }

	if(typeof(arr) == 'object') { //Array/Hashes/Objects
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

//js equivalent to html_entity_decode
function html_entity_decode(str) {
	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

//Custom jquery functions
function slideToggle(el, bShow){
  var $el = $(el), height = $el.data("originalHeight"), visible = $el.is(":visible");

  // if the bShow isn't present, get the current visibility and reverse it
  if( arguments.length == 1 ) bShow = !visible;

  // if the current visiblilty is the same as the requested state, cancel
  if( bShow == visible ) return false;

  // get the original height
  if( !height ){
    // get original height
    height = $el.show().height();
    // update the height
    $el.data("originalHeight", height);
    // if the element was hidden, hide it again
    if( !visible ) $el.hide().css({height: 0});
  }

  // expand the knowledge (instead of slideDown/Up, use custom animation which applies fix)
  if( bShow ){
    $el.show().animate({height: height}, {duration: 250});
  } else {
    $el.animate({height: 0}, {duration: 250, complete:function (){
        $el.hide();
      }
    });
  }
  return true;
}

// When completed, this function will replace the check() function, that doesn't work with fieldset tags
function getFormErrors(form)
{
    var strAlertMessageArr = new Array();

    var elements = form.getElementsByTagName("input");
    for (var i = 0; i < elements.length; i++) {
        if (elements[i].name.substr(0, 2) == "r_") {
            var requiredElementName = elements[i].name.substr(2);
            for (var j = 0; j < elements.length; j++) {
                if (elements[j].name == requiredElementName && elements[j].value == "") {
                    strAlertMessageArr.push(clientexec.lang("The field % can't be empty.", elements[i].value));
                    break;
                }
            }
        }
        if (elements[i].name.substr(0, 2) == "e_") {
            regexp = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/;
            var elementName = elements[i].name.substr(2);
            for (var j = 0; j < elements.length; j++) {
                if (elements[j].name == elementName && !regexp.test(elements[j].value)) {
                    strAlertMessageArr.push(clientexec.lang('Invalid Email format for field %.', elements[i].value));
                    break;
                }
            }
        }
    }
    return strAlertMessageArr;
}

function lang(phrase)
{
    if ((typeof language != "undefined") && (typeof language[phrase] != "undefined") && (language[phrase] != '')) {
        switch (lang.arguments.length) {
            case 1:
                return language[phrase];
            case 2:
                return _sprintf(language[phrase], lang.arguments[1]);
            case 3:
                return _sprintf(language[phrase], lang.arguments[1], lang.arguments[2]);
            case 4:
                return _sprintf(language[phrase], lang.arguments[1], lang.arguments[2], lang.arguments[3]);
        }

        return language[phrase];
    }

    switch (lang.arguments.length) {
        case 1:
            return phrase;
        case 2:
            return _sprintf(phrase, lang.arguments[1]);
        case 3:
            return _sprintf(phrase, lang.arguments[1], lang.arguments[2]);
    }

    return phrase;
}

// Same as lang() but only used for dynamically generated strings, like varLang(foo).
// Doesn't get parsed by update_languages.php script
function varLang(phrase)
{
    return lang(phrase);
}

//CLEANUP BELOW THIS
//TODO DELETE THIS FILE AND CREATE NEW COMMON.JS FILE
var winHelpHNDL = null;
var winUtilityHNDL = null;
var winDebug = null;
var new_fieldname = "";

//var _mousePosX;
//var _mousePosY;

//Used for color changes in forms
var highlightcolor="lightYellow";
var ns6 = document.getElementById&&!document.all;
var previous='';
var eventobj;
var intended=/INPUT|TEXTAREA/;

var isIE = false;
var isIE7 = false;
var isOther = false;
var isNS4 = false;
var isNS6 = false;

var submitted = false;

if(document.getElementById)
{
    if(!document.all)
    {
        isNS6=true;
    }
    if(document.all)
    {
        isIE=true;
    }
}
else
{
    if(document.layers)
    {
        isNS4=true;
    }
    else
    {
        isOther=true;
    }
}

isIE7 = isIE && document.documentElement && typeof document.documentElement.style.maxHeight!="undefined";

/**
* From my understanding (alpeb) line-breaks inside a text area are represented through \r\n regardless of the platform
*/
function rmbr(string){
    var regexp = /(<br>)|(<br\/>)/gi;
    string = string.replace(regexp, '\r\n');

    return string;
}

function nl2br(string){
    var regexp = /\n/g;
    string = string.replace(regexp, '<br>\n');
    return string;
}

//function used to make sure clicking enter on text box will not force form submit
//use the following in the textbox input section
// onkeypress="return noenter()"
function noenter(e) {
    if(window.event) {
        return !(window.event.keyCode == 13);
    }else{
        return !(e.which == 13);
    }
}

function InArray(tarray,value)
// Returns true if the passed value is found in the
// array.  Returns false if it is not.
{
    var i;
    for (i=0; i < tarray.length; i++) {
        // Matches identical (===), not just similar (==).
        if (tarray[i] === value) {
            return true;
        }
    }
    return false;
}

// Returns the selected value of a select input field.
function selectedValue(id)
{
    return document.getElementById(id).options[document.getElementById(id).selectedIndex].value
}

function MakeVisable(cur,which){
    strength = (which==0) ? 1 : 0.4;
    if (cur.style.MozOpacity){
        cur.style.MozOpacity=strength;
    }else if(cur.filters){
        cur.filters.alpha.opacity = strength * 100;
    }
}

//function used to ensure that at least one checkbox has been clicked
//to perform the action on
function ItemSelected(form,warningmsg, onlyCb){
    intCount = form.elements.length;
    bolShowMessage=true;
    for(x=1;x<=intCount;x++) {
            if(form.elements[x-1].checked && (!onlyCb || form.elements[x-1].id.substring(0, 3) == 'cb_')) {
                    bolShowMessage=false;
                    continue;
            }
    }

    if (bolShowMessage){
            if(warningmsg!="") alert(warningmsg);
            return false;
    }else{
            return true;
    }
}


function ltrim(inputString){
   if (typeof inputString != "string") {return inputString;}
   var retValue = inputString;
   var ch = retValue.substring(0, 1);
   while (ch == " ") { // Check for spaces at the beginning of the string
      retValue = retValue.substring(1, retValue.length);
      ch = retValue.substring(0, 1);
   }
   return retValue;
}

function trim(inputString) {
   // Removes leading and trailing spaces from the passed string. Also removes
   // consecutive spaces and replaces it with one space. If something besides
   // a string is passed in (null, custom object, etc.) then return the input.
   if (typeof inputString != "string") {return inputString;}
   var retValue = inputString;
   var ch = retValue.substring(0, 1);
   while (ch == " ") { // Check for spaces at the beginning of the string
      retValue = retValue.substring(1, retValue.length);
      ch = retValue.substring(0, 1);
   }
   ch = retValue.substring(retValue.length-1, retValue.length);
   while (ch == " ") { // Check for spaces at the end of the string
      retValue = retValue.substring(0, retValue.length-1);
      ch = retValue.substring(retValue.length-1, retValue.length);
   }
   while (retValue.indexOf("  ") != -1) { // Note that there are two spaces in the string - look for multiple spaces within the string
      retValue = retValue.substring(0, retValue.indexOf("  ")) + retValue.substring(retValue.indexOf("  ")+1, retValue.length); // Again, there are two spaces in each of the strings
   }
   return retValue; // Return the trimmed string back to the user
} // Ends the "trim" function

//Regular expression to highlight only form elements
//Function to check whether element clicked is form element
function submitonce(theform){
    if (document.all||document.getElementById){
        for (i=0;i<theform.length;i++){
            var tempobj=theform.elements[i]
            if(tempobj.type.toLowerCase()=="submit"||tempobj.type.toLowerCase()=="button")
            tempobj.disabled=true
        }
    }
}

// *** TABLE CHECKBOX OPERATIONS *****
var checkCount = 0;

function makevisible(cur,which)
{
    strength=(which==0)? 1 : 0.2

    if (cur.style.MozOpacity)
        cur.style.MozOpacity=strength
    else if (cur.filters)
        cur.filters.alpha.opacity=strength*100
}

function updateButtons(form)
{
    if (checkCount > 0) {
        for (i = 0; i < form.length; i++) {
            element = form.elements[i];
            if (element.type == "button" || element.type == "submit") {
                element.disabled = false;
                makevisible(element, 0);
            }
        }
    } else {
        for (i = 0; i < form.length; i++) {
            element = form.elements[i];
            if (element.type == "button" || element.type == "submit") {
                element.disabled = true;
                makevisible(element, 1);
            }
        }
    }
}

function checkCheckBox(checkBox)
{
    if (checkBox.checked) checkCount++;
    else checkCount--;
    updateButtons(checkBox.form);
}

function toggleAll(form)
{
    check = form.toggleAllChk.checked;
    for (i = 0; i < form.length; i++) {
        element = form.elements[i];
        if (element.type == 'checkbox' && element.name != 'toggleAll' && element.checked != check && element.disabled != true) {
            element.checked = check;
            if (check) checkCount++;
            else checkCount = 0;
        }
    }
    updateButtons(form);
}
// *** END TABLE CHECKBOX OPERATIONS *****

function strrpos( haystack, needle, offset){
    var i = (haystack+'').lastIndexOf( needle, offset ); // returns -1
    return i >= 0 ? i : false;
}

function substr( f_string, f_start, f_length ) {
    // http://kevin.vanzonneveld.net
    // +     original by: Martijn Wieringa
    // +     bugfixed by: T.Wild
    // +      tweaked by: Onno Marsman
    // *       example 1: substr('abcdef', 0, -1);
    // *       returns 1: 'abcde'
    // *       example 2: substr(2, 0, -6);
    // *       returns 2: ''

    f_string += '';

    if(f_start < 0) {
        f_start += f_string.length;
    }

    if(f_length == undefined) {
        f_length = f_string.length;
    } else if(f_length < 0){
        f_length += f_string.length;
    } else {
        f_length += f_start;
    }

    if(f_length < f_start) {
        f_length = f_start;
    }

    return f_string.substring(f_start, f_length);
}

function getAbsoluteTop(objectId) {
        // Get an object top position from the upper left viewport corner
        // Tested with relative and nested objects
        o = document.getElementById(objectId)
        oTop = o.offsetTop            // Get top position from the parent object
        while(o.offsetParent!=null) { // Parse the parent hierarchy up to the document element
                oParent = o.offsetParent  // Get parent object reference
                oTop += oParent.offsetTop // Add parent top position
                o = oParent
        }
        // Return top position
        return oTop
}
/* End dynamic menu */

function getElementsByName_iefix(tag, name) {
     var elem = document.getElementsByTagName(tag);
     var arr = new Array();
     for(i = 0,iarr = 0; i < elem.length; i++) {
          att = elem[i].getAttribute("name");
          if(att == name) {
               arr[iarr] = elem[i];
               iarr++;
          }
     }
     return arr;
}

function _sprintf(s)
{
    var re = /%/;
    var i = 0;
    while (re.test(s))
    {
       s = s.replace(re, _sprintf.arguments[++i]);
    }

    return s;
}

jQuery.nl2br = function(varTest){
    return varTest.replace(/(\r\n|\n\r|\r|\n)/g, "<br>");
};

jQuery.br2nl = function(varTest){
    return varTest.replace(/<br>/g, "\r");
};

function passwordRequest(inputMail)
{
    var newElement = document.getElementById(inputMail);

    var newEmail = newElement.value;

    $.ajax({
       url: 'index.php?fuse=admin&action=RequestPassword',
       success: function(t) {
            unsetStatus(false);
            var response = t.responseText;
            document.getElementById('message').innerHTML = '<font class="redtext"><b>'+response+'</b></font>';
       },
       data: {ajaxRequest:"1",
                emailToSend:newEmail
              }
    });

}

function check(form,x,submit)
{

	if (typeof submit == 'undefined') submit = true;

    script_name = "Form Validator ver 2.0";
    action =  "Checks Required, Integer and Date";
    cpyrght = "(c) 1998 - Art Lubin / Artswork";
    email = "perflunk@aol.com";
    var set_up_var = doall(script_name, cpyrght, email);
    var message = "";
    var more_message = "";
    if (set_up_var == 5872){
        x = x - 1;

        for (var i = 0; i <= x; i++) {

            if (typeof form.elements[i].name == 'undefined') {
                continue;
            }

            var messenger = form.elements[i].name;
            messenger = messenger.substring(0, 2);
            var fieldname = form.elements[i].name;
            fieldname = fieldname.substring(2);
            if((form.elements[i].name == "ccnumber" || form.elements[i].name == "ccmonth" || form.elements[i].name == "ccyear") && document.getElementById("creditcardinfo").style.display == "none")
            {
                form.elements[i].value = "";
            }

            var cctypes = null;
            if (messenger == "r_"){
                if (form.elements[i].value!=""){
                    more_message = r_check(form,x,fieldname,i);
                }
            }else if (messenger == "c_"){
                if (form.elements[i].value!=""){
                    //more_message = c_check(form,x,fieldname,i,false);
                    more_message = c_check(form,x,fieldname,i,true);        //blanks are allowed with this line
                    cctypes = form.elements['validcc'].value
                    if (more_message==""){more_message = CheckWithAllowedCardTypes(cctypes,form,x,fieldname,i);}
                }
            }else if (messenger == "C_"){
                //same as case above but blanks are allowed
                if (form.elements[i].value!=""){
                    more_message = c_check(form,x,fieldname,i,true);
                    cctypes = form.elements['validcc'].value
                    if (more_message==""){more_message = CheckWithAllowedCardTypes(cctypes,form,x,fieldname,i);}
                }
            }else if (messenger == "D_"){
                if (form.elements[i].value!=""){
                    more_message = D_check(form,x,fieldname,i);
                }
            }else if (messenger == "i_"){
                more_message = i_check(form,x,fieldname,i);
            }else if (messenger == "d_"){
                more_message = d_check(form,x,fieldname,i);
            }else if (messenger == "e_"){
                more_message = e_check(form,x,fieldname,i);
            }else if (messenger == "n_"){
                more_message = n_check(form, x, fieldname, i);
            }
            if (more_message != ""){
                if (more_message){
                    if (message == ""){
                        message = more_message;
                        more_message="";
                    }else{
                        message = message + "\n" + more_message;
                        more_message="";
                    }
                }
            }
        }

        if (message != ""){
            alert(clientexec.lang("The following form field(s) were incomplete or incorrect")+":\n\n" + message + "\n\n"+clientexec.lang("Please complete or correct the form and submit again."));
            return false;
        }else{

        	if (submit) {
	            submitonce(form);

	            // call form onsubmit event, if it exists
	            try {
	                form.onsubmit();
	            } catch(e) {
	            }

	            if (!submitted) {
	                form.submit();
	                submitted = true;
	            }
            }
            return true;
        }
    }else{
        alert ("The copyright information has been changed. \n In order to use this javascript please keep the copyright information intact. \n\n Script Name: Form Validator ver 2.0 \n Copyright: (c) 1998 - Art Lubin / Artswork \n Email: perflunk@aol.com");
        return false;
    }
}


function isNum(str)
{
    // 0.234, .234, 234, 234.234
    regexp = /(^\d+\.?\d*)|(^\d*\.?\d+)/;
    if (regexp.test(str)) return true;
    else return false;
}

function n_check(form, x, fieldname, i)
{
    var msg_addition = "";
    error=0;
    for (var y = 0; y <= x; y++) {
        if (form.elements[y].name == fieldname) break;
    }
    numberField = form.elements[y].value;
    if(!isNum(numberField)) {error = 1;} else {
        if (numberField.indexOf ('.3') > 1)  error = 1;
    }
    if (error == 1) msg_addition = form.elements[i].value;
    return(msg_addition);
}
function c_check(form,x,fieldname,i,blankallowed)
{
       /*************************************************************************\
       luhn check
       \*************************************************************************/
        var msg_addition = "";
        error=0;
        for (var y = 0; y <= x; y++){if (form.elements[y].name == fieldname) break;}
        CardNumber = form.elements[y].value;
        error = checkCCNumber(CardNumber, blankallowed);
        if (error == 1) {
            msg_addition = form.elements[i].value;
        }
        return(msg_addition);
}

function checkCCNumber(CardNumber, blankallowed)
{
   /*************************************************************************\
   luhn check
   \*************************************************************************/
    error = 0;
    if (CardNumber != "") {
        if (! isNum(CardNumber)) {
            error = 1;
        } else {
            var  no_digit = CardNumber.length;
            var oddoeven = no_digit & 1;
            var sum = 0;
            for (var count = 0; count < no_digit; count++) {
                var digit = parseInt(CardNumber.charAt(count));
                if (!((count & 1) ^ oddoeven)) {
                    digit *= 2;
                    if (digit > 9) digit -= 9;
                }
                sum += digit;
            }
            if (sum % 10 == 0) {
                error = 0;
            } else {
                error = 1;
            }
        }
    } else if (!blankallowed) {
        error = 1;
    }
    return(error);
}



function D_check(form,x,fieldname,i)
{
    for (var y = 0; y <= x; y++)
    {
            if (form.elements[y].name == fieldname) break;
    }
    var msg_addition = "";

    regexp = /^\w+([\.-]?\w+)*(\.\w{2,3})+$/;
    if (regexp.test(form.elements[y].value))  error = 0;
    else{
         error=1;
    }

    if (error == 1) msg_addition = form.elements[i].value;
    return(msg_addition);
}

function r_check(form,x,fieldname,i)
{
        var msg_addition = "";
        new_fieldname = fieldname;
        for (var y = 0; y <= x; y++)
            {

                if ((form.elements[y].type == "radio" || form.elements[y].type == "checkbox") && form.elements[y].name == new_fieldname && form.elements[y].checked == true)
                    {
                            msg_addition = "";
                            break;
                    }
                else if ((form.elements[y].type == "radio" || form.elements[y].type == "checkbox") && form.elements[y].name == new_fieldname && form.elements[y].checked == false)
                    {
                        msg_addition = form.elements[i].value;
                    }

            else if (form.elements[y].type == "select-one")
                            {
                                var l = form.elements[y].selectedIndex;
                                if (form.elements[y].name == fieldname && form.elements[y].options[l].value != "")
                                    {
                                        msg_addition = "";
                                        break;
                                    }
                                else if (form.elements[y].name == fieldname && form.elements[y].options[l].value == "")
                                    {

                                        msg_addition = form.elements[i].value;

                                    }
                                }
         else if (form.elements[y].name == fieldname && form.elements[y].value == "" && form.elements[y].type != "radio" && form.elements[y].type != "checkbox" && form.elements[y].type != "select-one")
                            {

                                if(form.elements[y].name == 'UserName' || form.elements[y].name == 'Password'){
                                    msg_addition = "";
                                }else{
                                    msg_addition = form.elements[i].value;
                                    break;
                                }
                            }
                else if (form.elements[y].name == fieldname && form.elements[y].value != "" && form.elements[y].type != "radio" && form.elements[y].type != "checkbox" && form.elements[y].type != "select-one")
                            {
                                msg_addition = "";
                            }
                }
            return(msg_addition);
}


function i_check(form,x,fieldname,i)
{
        for (var y = 0; y <= x; y++)
            {
                if (form.elements[y].name == fieldname) break;
            }

    var msg_addition = "";
    var decimal = "";
    inputStr = form.elements[y].value.toString();

    if (inputStr == "")
        {
        }
    else
        {
            for (var c = 0; c < inputStr.length; c++)
                {
                    var oneChar = inputStr.charAt(c);
                    if (c == 0 && oneChar == "-" || oneChar == "."  && decimal == "")
                            {
                                if (oneChar == ".");
                                    {
                                        decimal = "yes";
                                    }
                                continue;

                            }
                                if (oneChar < "0" || oneChar > "9")
                                    {
                                        msg_addition = form.elements[i].value;
                                    }
                }
        }
        return(msg_addition);
}



function e_check(form,x,fieldname,i)
{
            for (var y = 0; y <= x; y++){if (form.elements[y].name == fieldname) break;}
            var msg_addition = "";
            regexp = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/;

            if (!regexp.test(form.elements[y].value)){
                msg_addition = form.elements[i].value;
            }

            return(msg_addition);
}

function d_check(form,x,fieldname,i)
{
        for (var y = 0; y <= x; y++)
            {
                if (form.elements[y].name == fieldname) break;
            }

        var msg_addition = "";
        var sDate = form.elements[y].value;
        var int_or_not = isInteger(form.elements[y].value);
        if (int_or_not == "true")
        {
                if ((!(form.elements[y].value.length >= 6)) || (!(form.elements[y].value.length <= 10)))
                {
                        msg_addition = form.elements[i].value;
                }
                else
                {
                     var SlashlPos = form.elements[y].value.indexOf("/",0);
                        if (SlashlPos > 0 && SlashlPos <= 2)
                            {
                                if (SlashlPos == 1)
                                    {
                                        if (form.elements[y].value.charAt(0) < 1 || form.elements[y].value.charAt(0) > 9)
                                            {
                                                msg_addition = form.elements[i].value;
                                            }
                                        else
                                            {
                                                if ((form.elements[y].value.charAt(0) == 1 || form.elements[y].value.charAt(0) == 3 || form.elements[y].value.charAt(0) == 5 || form.elements[y].value.charAt(0) == 7 || form.elements[y].value.charAt(0) == 8) && ((form.elements[y].value.charAt(2) == 0 && form.elements[y].value.charAt(3) == "/") || (form.elements[y].value.charAt(3) == "/" && form.elements[y].value.length >= 7) || (form.elements[y].value.charAt(1) == "/" && form.elements[y].value.charAt(2) == "/")))
                                                    {
                                                        msg_addition = form.elements[i].value;
                                                    }
                                                else if ((form.elements[y].value.charAt(0) == 1 || form.elements[y].value.charAt(0) == 3 || form.elements[y].value.charAt(0) == 5 || form.elements[y].value.charAt(0) == 7 || form.elements[y].value.charAt(0) == 8) && ((form.elements[y].value.charAt(2) >= 3 && form.elements[y].value.charAt(3) > 1) || (form.elements[y].value.charAt(2) == 0 && form.elements[y].value.charAt(3) == 0) || (form.elements[y].value.charAt(1) == "/" && (form.elements[y].value.charAt(3) != "/" && form.elements[y].value.charAt(4) != "/" && form.elements[y].value.charAt(5) != "/" && form.elements[y].value.charAt(6) != "/"))))
                                                    {
                                                        msg_addition = form.elements[i].value + "hi";
                                                    }
                                                else if ((form.elements[y].value.charAt(0) == 1 || form.elements[y].value.charAt(0) == 3 || form.elements[y].value.charAt(0) == 5 || form.elements[y].value.charAt(0) == 7 || form.elements[y].value.charAt(0) == 8) && (((form.elements[y].value.charAt(2) > 3 && form.elements[y].value.charAt(3) != "/") || (((form.elements[y].value.charAt(1) == "/" && form.elements[y].value.charAt(4) == "/")) && ((form.elements[y].value.length == 6 || form.elements[y].value.length == 8)))) || form.elements[y].value.charAt(5) == "/"))
                                                    {
                                                        msg_addition = form.elements[i].value;
                                                    }
                                                else
                                                    {
                                                        if ((form.elements[y].value.charAt(0) == 2 && ((form.elements[y].value.charAt(2) == 0 && form.elements[y].value.charAt(3) == "/") || (form.elements[y].value.charAt(3) == "/" && form.elements[y].value.length >= 7) || (form.elements[y].value.charAt(1) == "/" && form.elements[y].value.charAt(2) == "/") || (form.elements[y].value.charAt(2) == 0 && form.elements[y].value.charAt(3) == 0) || (form.elements[y].value.charAt(1) == "/" && (form.elements[y].value.charAt(3) != "/" && form.elements[y].value.charAt(4) != "/" && form.elements[y].value.charAt(5) != "/" && form.elements[y].value.charAt(6) != "/")))))
                                                            {
                                                                msg_addition = form.elements[i].value;
                                                            }
                                                        else if (form.elements[y].value.charAt(0) == 2 && ((form.elements[y].value.charAt(2) > 2 && form.elements[y].value.charAt(3) != "/") || (((form.elements[y].value.charAt(1) == "/" && form.elements[y].value.charAt(4) == "/") && ((form.elements[y].value.length == 6 || form.elements[y].value.length == 8)))) || form.elements[y].value.charAt(5) == "/"))
                                                            {
                                                                msg_addition = form.elements[i].value;
                                                            }
                                                        else
                                                            {
                                                                if ((form.elements[y].value.charAt(0) == 4 || form.elements[y].value.charAt(0) == 6 || form.elements[y].value.charAt(0) == 9) && ((form.elements[y].value.charAt(2) == 0 && form.elements[y].value.charAt(3) == "/") || (form.elements[y].value.charAt(3) == "/" && form.elements[y].value.length >= 7) || (form.elements[y].value.charAt(1) == "/" && form.elements[y].value.charAt(2) == "/")))
                                                                    {
                                                                        msg_addition = form.elements[i].value;
                                                                    }
                                                                else if ((form.elements[y].value.charAt(0) == 4 || form.elements[y].value.charAt(0) == 6 || form.elements[y].value.charAt(0) == 9) && ((form.elements[y].value.charAt(2) >= 3 && form.elements[y].value.charAt(3) > 0) || (form.elements[y].value.charAt(2) == 0 && form.elements[y].value.charAt(3) == 0) || (form.elements[y].value.charAt(1) == "/" && (form.elements[y].value.charAt(3) != "/" && form.elements[y].value.charAt(4) != "/" && form.elements[y].value.charAt(5) != "/" && form.elements[y].value.charAt(6) != "/"))))
                                                                    {
                                                                        msg_addition = form.elements[i].value;
                                                                    }
                                                                else if ((form.elements[y].value.charAt(0) == 4 || form.elements[y].value.charAt(0) == 6 || form.elements[y].value.charAt(0) == 9) && (((form.elements[y].value.charAt(2) > 3 && form.elements[y].value.charAt(3) != "/") || ((form.elements[y].value.charAt(1) == "/" && form.elements[y].value.charAt(4) == "/") && ((form.elements[y].value.length == 6 || form.elements[y].value.length == 8)))) || form.elements[y].value.charAt(5) == "/"))
                                                                    {
                                                                        msg_addition = form.elements[i].value;
                                                                    }
                                                            }
                                                    }
                                            }
                                    }
                                else
                                    {
                                        if (form.elements[y].value.charAt(0) > 1 || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) > 2) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 0))
                                            {
                                                msg_addition = form.elements[i].value;
                                            }
                                        else
                                            {
                                                if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 1) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 3) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 5) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 7) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 8) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 0) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 2)) && ((form.elements[y].value.charAt(3) == 0 && form.elements[y].value.charAt(4) == "/") || (form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(3) == "/") || (form.elements[y].value.charAt(2) == "/" && (form.elements[y].value.charAt(4) != "/" && form.elements[y].value.charAt(5) != "/" && form.elements[y].value.charAt(6) != "/" && form.elements[y].value.charAt(7) != "/"))))
                                                    {
                                                        msg_addition = form.elements[i].value;
                                                    }
                                                else if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 1) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 3) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 5) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 7) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 8) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 0) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 2)) && ((form.elements[y].value.charAt(3) >= 3 && form.elements[y].value.charAt(4) > 1) || (form.elements[y].value.charAt(3) == 0 && form.elements[y].value.charAt(4) == 0) || form.elements[y].value.length < 7))
                                                    {
                                                        msg_addition = form.elements[i].value;
                                                    }
                                                else if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 1) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 3) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 5) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 7) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 8) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 0) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 2)) && ((form.elements[y].value.charAt(3) > 3 && form.elements[y].value.charAt(4) != "/")   || ((form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(5) == "/" && form.elements[y].value.length == 7 || form.elements[y].value.charAt(6) == "/") || (form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(4) == "/" && (form.elements[y].value.length == 6 || form.elements[y].value.length == 8)))))
                                                    {
                                                        msg_addition = form.elements[i].value;
                                                    }
                                                else
                                                    {
                                                        if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 2) && ((form.elements[y].value.charAt(3) == 0 && form.elements[y].value.charAt(4) == "/") || (form.elements[y].value.charAt(3) == 0 && form.elements[y].value.charAt(4) == 0)) || form.elements[y].value.length < 7) || (form.elements[y].value.charAt(2) == "/" && (form.elements[y].value.charAt(4) != "/" && form.elements[y].value.charAt(5) != "/" && form.elements[y].value.charAt(6) != "/" && form.elements[y].value.charAt(7) != "/")))
                                                            {
                                                                msg_addition = form.elements[i].value;
                                                            }
                                                        else if ((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 2) && ((form.elements[y].value.charAt(3) > 2 && form.elements[y].value.charAt(4) != "/") || ((form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(5) == "/" && form.elements[y].value.length == 7 || form.elements[y].value.charAt(6) == "/") || (form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(4) == "/" && (form.elements[y].value.length == 6 || form.elements[y].value.length == 8)))))
                                                            {
                                                                msg_addition = form.elements[i].value;
                                                            }
                                                        else
                                                            {
                                                                if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 4) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 6) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 9) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 1)) && ((form.elements[y].value.charAt(3) == 0 && form.elements[y].value.charAt(4) == "/") || (form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(3) == "/") || (form.elements[y].value.charAt(2) == "/" && (form.elements[y].value.charAt(4) != "/" && form.elements[y].value.charAt(5) != "/" && form.elements[y].value.charAt(6) != "/" && form.elements[y].value.charAt(7) != "/"))))
                                                                    {
                                                                        msg_addition = form.elements[i].value;
                                                                    }
                                                                else if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 4) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 6) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 9) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 1)) && ((form.elements[y].value.charAt(3) >= 3 && form.elements[y].value.charAt(4) > 0) || (form.elements[y].value.charAt(3) == 0 && form.elements[y].value.charAt(4) == 0) || form.elements[y].value.length < 7))
                                                                    {
                                                                        msg_addition = form.elements[i].value;
                                                                    }
                                                                else if (((form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 4) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 6) || (form.elements[y].value.charAt(0) == 0 && form.elements[y].value.charAt(1) == 9) || (form.elements[y].value.charAt(0) == 1 && form.elements[y].value.charAt(1) == 1)) && ((form.elements[y].value.charAt(3) > 3 && form.elements[y].value.charAt(4) != "/") || ((form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(5) == "/" && form.elements[y].value.length == 7 || form.elements[y].value.charAt(6) == "/") || (form.elements[y].value.charAt(2) == "/" && form.elements[y].value.charAt(4) == "/" && (form.elements[y].value.length == 6 || form.elements[y].value.length == 8)))))
                                                                    {
                                                                        msg_addition = form.elements[i].value;
                                                                    }
                                                            }
                                                    }
                                            }
                                    }
                            }
            else
                            {
                                msg_addition = form.elements[i].value;
                            }
                    }
            }
        else
            {
                msg_addition = form.elements[i].value;
            }
        return(msg_addition);
}

function isInteger(sDate)
{
    var new_msg = true;
    inputStr = sDate.toString();
    for (var i = 0; i < inputStr.length; i++)
        {
        var oneChar = inputStr.charAt(i);
        if ((oneChar < "0" || oneChar > "9") && oneChar != "/")
                {
                    new_msg = false;
                }
        }
    return (new_msg);
}

function doall(script_name, copyright, email)
{
    var code = 0;
    var test = script_name + copyright + email;
    for (var a = 0; a < test.length; a++)
        {
        var each_char = test.charAt(a);
        var x = asc(each_char);
        code += x;
        }
    return (code);
}

function asc(each_char)
{
    var n = 0;
        var char_str = charSetStr();
        for (i = 0; i < char_str.length; i++)
            {
                if (each_char == char_str.substring(i, i+1))
                    {
                        break;
                    }
            }
        return i + 32;
}

function charSetStr()
{
        var str;
    str = ' !"#$%&' + "'" + '()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        return str;
}


function CheckWithAllowedCardTypes(cctypes,form,x,fieldname,i)
{
        for (var y = 0; y <= x; y++){if (form.elements[y].name == fieldname) break;}
        CardNumber = form.elements[y].value;

        return CheckAllowedCCTypes(CardNumber, cctypes);
}

function CheckAllowedCCTypes(CardNumber, cctypes)
{
        error=0;
        typeVisa   = checkCreditCard(CardNumber, 'Visa');
        typeMc     = checkCreditCard(CardNumber, 'MasterCard');
        typeAmex   = checkCreditCard(CardNumber, 'AmEx');
        typeDisc   = checkCreditCard(CardNumber, 'Discover');

        typeLaser  = checkCreditCard(CardNumber, 'LaserCard');
        typeDiners = checkCreditCard(CardNumber, 'DinersClub');
        typeSwitch = checkCreditCard(CardNumber, 'Switch');

        //c_1000000_ = visa
        //c_0100000_ = mastercard
        //c_0010000_ = americanexpress
        //c_0001000_ = discover

        //c_0000100_ = lasercard
        //c_0000010_ = dinersclub
        //c_0000001_ = switch

        if (typeVisa){
            if (cctypes.substr(0,1)=="0") return clientexec.lang("Visa is not accepted at this time");
            else return '';
        }else if (typeMc){
            if (cctypes.substr(1,1)=="0") return clientexec.lang("MasterCard is not accepted at this time");
            else return '';
        }else if (typeAmex){
            if (cctypes.substr(2,1)=="0") return clientexec.lang("American Express is not accepted at this time");
            else return '';
        }else if (typeDisc){
            if (cctypes.substr(3,1)=="0") return clientexec.lang("Discover is not accepted at this time");
            else return '';
        }else if (typeLaser){
            if (cctypes.substr(4,1)=="0") return clientexec.lang("LaserCard is not accepted at this time");
            else return '';
        }else if (typeDiners){
            if (cctypes.substr(5,1)=="0") return clientexec.lang("Diners Club is not accepted at this time");
            else return '';
        }else if (typeSwitch){
            if (cctypes.substr(6,1)=="0") return clientexec.lang("Switch is not accepted at this time");
            else return '';
        }
}

/*================================================================================================*/
/*

This routine checks the credit card number. The following checks are made:

1. A number has been provided
2. The number is a right length for the card
3. The number has an appropriate prefix for the card
4. The number has a valid modulus 10 number check digit if required

If the validation fails an error is reported.

The structure of credit card formats was gleaned from a variety of sources on the web, although the
best is probably on Wikepedia ("Credit card number"):

  http://en.wikipedia.org/wiki/Credit_card_number

Parameters:
            cardnumber           number on the card
            cardname             name of card as defined in the card list below

Author:     John Gardner
Date:       1st November 2003
Updated:    26th Feb. 2005      Additional cards added by request
Updated:    27th Nov. 2006      Additional cards added from Wikipedia
Updated:    18th Jan. 2008      Additional cards added from Wikipedia
Updated:    26th Nov. 2008      Maestro cards extended
Updated:    19th Jun. 2009      Laser cards extended from Wikipedia
Updated:    11th Sep. 2010      Typos removed from Diners and Solo definitions (thanks to Noe Leon)

*/

/*
   If a credit card number is invalid, an error reason is loaded into the global ccErrorNo variable.
   This can be be used to index into the global error  string array to report the reason to the user
   if required:

   e.g. if (!checkCreditCard (number, name) alert (ccErrors(ccErrorNo);
*/

var ccErrorNo = 0;
var ccErrors = new Array ()

ccErrors [0] = "Unknown card type";
ccErrors [1] = "No card number provided";
ccErrors [2] = "Credit card number is in invalid format";
ccErrors [3] = "Credit card number is invalid";
ccErrors [4] = "Credit card number has an inappropriate number of digits";

function checkCreditCard (cardnumber, cardname) {

  // Array to hold the permitted card characteristics
  var cards = new Array();

  // Define the cards we support. You may add addtional card types as follows.

  //  Name:         As in the selection box of the form - must be same as user's
  //  Length:       List of possible valid lengths of the card number for the card
  //  prefixes:     List of possible prefixes for the card
  //  checkdigit:   Boolean to say whether there is a check digit

  cards [0] = {name: "Visa",
               length: "13,16",
               prefixes: "4",
               checkdigit: true};
  cards [1] = {name: "MasterCard",
               length: "16",
               prefixes: "51,52,53,54,55",
               checkdigit: true};
  cards [2] = {name: "DinersClub",
               length: "14,16",
               prefixes: "305,36,38,54,55",
               checkdigit: true};
  cards [3] = {name: "CarteBlanche",
               length: "14",
               prefixes: "300,301,302,303,304,305",
               checkdigit: true};
  cards [4] = {name: "AmEx",
               length: "15",
               prefixes: "34,37",
               checkdigit: true};
  cards [5] = {name: "Discover",
               length: "16",
               prefixes: "6011,622,64,65",
               checkdigit: true};
  cards [6] = {name: "JCB",
               length: "16",
               prefixes: "35",
               checkdigit: true};
  cards [7] = {name: "enRoute",
               length: "15",
               prefixes: "2014,2149",
               checkdigit: true};
  cards [8] = {name: "Solo",
               length: "16,18,19",
               prefixes: "6334,6767",
               checkdigit: true};
  cards [9] = {name: "Switch",
               length: "16,18,19",
               prefixes: "4903,4905,4911,4936,564182,633110,6333,6759",
               checkdigit: true};
  cards [10] = {name: "Maestro",
               length: "12,13,14,15,16,18,19",
               prefixes: "5018,5020,5038,6304,6759,6761",
               checkdigit: true};
  cards [11] = {name: "VisaElectron",
               length: "16",
               prefixes: "417500,4917,4913,4508,4844",
               checkdigit: true};
  cards [12] = {name: "LaserCard",
               length: "16,17,18,19",
               prefixes: "6304,6706,6771,6709",
               checkdigit: true};

  // Establish card type
  var cardType = -1;
  for (var i=0; i<cards.length; i++) {

    // See if it is this card (ignoring the case of the string)
    if (cardname.toLowerCase () == cards[i].name.toLowerCase()) {
      cardType = i;
      break;
    }
  }

  // If card type not found, report an error
  if (cardType == -1) {
     ccErrorNo = 0;
     return false;
  }

  // Ensure that the user has provided a credit card number
  if (cardnumber.length == 0)  {
     ccErrorNo = 1;
     return false;
  }

  // Now remove any spaces from the credit card number
  cardnumber = cardnumber.replace (/\s/g, "");

  // Check that the number is numeric
  var cardNo = cardnumber
  var cardexp = /^[0-9]{13,19}$/;
  if (!cardexp.exec(cardNo))  {
     ccErrorNo = 2;
     return false;
  }

  // Now check the modulus 10 check digit - if required
  if (cards[cardType].checkdigit) {
    var checksum = 0;                                  // running checksum total
    var mychar = "";                                   // next char to process
    var j = 1;                                         // takes value of 1 or 2

    // Process each digit one by one starting at the right
    var calc;
    for (i = cardNo.length - 1; i >= 0; i--) {

      // Extract the next digit and multiply by 1 or 2 on alternative digits.
      calc = Number(cardNo.charAt(i)) * j;

      // If the result is in two digits add 1 to the checksum total
      if (calc > 9) {
        checksum = checksum + 1;
        calc = calc - 10;
      }

      // Add the units element to the checksum total
      checksum = checksum + calc;

      // Switch the value of j
      if (j ==1) {j = 2} else {j = 1};
    }

    // All done - if checksum is divisible by 10, it is a valid modulus 10.
    // If not, report an error.
    if (checksum % 10 != 0)  {
     ccErrorNo = 3;
     return false;
    }
  }

  // The following are the card-specific checks we undertake.
  var LengthValid = false;
  var PrefixValid = false;
  var undefined;

  // We use these for holding the valid lengths and prefixes of a card type
  var prefix = new Array ();
  var lengths = new Array ();

  // Load an array with the valid prefixes for this card
  prefix = cards[cardType].prefixes.split(",");

  // Now see if any of them match what we have in the card number
  for (i=0; i<prefix.length; i++) {
    var exp = new RegExp ("^" + prefix[i]);
    if (exp.test (cardNo)) PrefixValid = true;
  }

  // If it isn't a valid prefix there's no point at looking at the length
  if (!PrefixValid) {
     ccErrorNo = 3;
     return false;
  }

  // See if the length is valid for this card
  lengths = cards[cardType].length.split(",");
  for (j=0; j<lengths.length; j++) {
    if (cardNo.length == lengths[j]) LengthValid = true;
  }

  // See if all is OK by seeing if the length was valid. We only check the length if all else was
  // hunky dory.
  if (!LengthValid) {
     ccErrorNo = 4;
     return false;
  };

  // The credit card is in the required format.
  return true;
}

/*================================================================================================*/
/** LazyLoad makes it easy and painless to lazily load one or more external
JavaScript or CSS files on demand either during or after the rendering of a web
page.

Supported browsers include Firefox 2+, IE6+, Safari 3+ (including Mobile
Safari), Google Chrome, and Opera 9+. Other browsers may or may not work and
are not officially supported.

Visit https://github.com/rgrove/lazyload/ for more info.

Copyright (c) 2011 Ryan Grove <ryan@wonko.com>
All rights reserved.

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the 'Software'), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

@module lazyload
@class LazyLoad
@static
@version 2.0.1 (2011-03-05)
*/

LazyLoad = (function (win, doc) {
  // -- Private Variables ------------------------------------------------------

  // User agent and feature test information.
  var env,

  // Reference to the <head> element (populated lazily).
  head,

  // Requests currently in progress, if any.
  pending = {},

  // Number of times we've polled to check whether a pending stylesheet has
  // finished loading in WebKit. If this gets too high, we're probably stalled.
  pollCount = 0,

  // Queued requests.
  queue = {css: [], js: []},

  // Reference to the browser's list of stylesheets.
  styleSheets = doc.styleSheets;

  // -- Private Methods --------------------------------------------------------

  /**
  Creates and returns an HTML element with the specified name and attributes.

  @method createNode
  @param {String} name element name
  @param {Object} attrs name/value mapping of element attributes
  @return {HTMLElement}
  @private
  */
  function createNode(name, attrs) {
    var node = doc.createElement(name), attr;

    for (attr in attrs) {
      if (attrs.hasOwnProperty(attr)) {
        node.setAttribute(attr, attrs[attr]);
      }
    }

    return node;
  }

  /**
  Called when the current pending resource of the specified type has finished
  loading. Executes the associated callback (if any) and loads the next
  resource in the queue.

  @method finish
  @param {String} type resource type ('css' or 'js')
  @private
  */
  function finish(type) {
    var p = pending[type],
        callback,
        urls;

    if (p) {
      callback = p.callback;
      urls     = p.urls;

      urls.shift();
      pollCount = 0;

      // If this is the last of the pending URLs, execute the callback and
      // start the next request in the queue (if any).
      if (!urls.length) {
        if (callback) {
          callback.call(p.context, p.obj);
        }

        pending[type] = null;

        if (queue[type].length) {
          load(type);
        }
      }
    }
  }

  /**
  Populates the <code>env</code> variable with user agent and feature test
  information.

  @method getEnv
  @private
  */
  function getEnv() {
    // No need to run again if already populated.
    if (env) { return; }

    var ua = navigator.userAgent;

    env = {
      // True if this browser supports disabling async mode on dynamically
      // created script nodes. See
      // http://wiki.whatwg.org/wiki/Dynamic_Script_Execution_Order
      async: doc.createElement('script').async === true
    };

    (env.webkit = /AppleWebKit\//.test(ua))
      || (env.ie = /MSIE/.test(ua))
      || (env.opera = /Opera/.test(ua))
      || (env.gecko = /Gecko\//.test(ua))
      || (env.unknown = true);
  }

  /**
  Loads the specified resources, or the next resource of the specified type
  in the queue if no resources are specified. If a resource of the specified
  type is already being loaded, the new request will be queued until the
  first request has been finished.

  When an array of resource URLs is specified, those URLs will be loaded in
  parallel if it is possible to do so while preserving execution order. All
  browsers support parallel loading of CSS, but only Firefox and Opera
  support parallel loading of scripts. In other browsers, scripts will be
  queued and loaded one at a time to ensure correct execution order.

  @method load
  @param {String} type resource type ('css' or 'js')
  @param {String|Array} urls (optional) URL or array of URLs to load
  @param {Function} callback (optional) callback function to execute when the
    resource is loaded
  @param {Object} obj (optional) object to pass to the callback function
  @param {Object} context (optional) if provided, the callback function will
    be executed in this object's context
  @private
  */
  function load(type, urls, callback, obj, context) {
    var _finish = function () { finish(type); },
        isCSS   = type === 'css',
        i, len, node, p, pendingUrls, url;

    getEnv();

    if (urls) {
      // If urls is a string, wrap it in an array. Otherwise assume it's an
      // array and create a copy of it so modifications won't be made to the
      // original.
      urls = typeof urls === 'string' ? [urls] : urls.concat();

      // Create a request object for each URL. If multiple URLs are specified,
      // the callback will only be executed after all URLs have been loaded.
      //
      // Sadly, Firefox and Opera are the only browsers capable of loading
      // scripts in parallel while preserving execution order. In all other
      // browsers, scripts must be loaded sequentially.
      //
      // All browsers respect CSS specificity based on the order of the link
      // elements in the DOM, regardless of the order in which the stylesheets
      // are actually downloaded.
      if (isCSS || env.async || env.gecko || env.opera) {
        // Load in parallel.
        queue[type].push({
          urls    : urls,
          callback: callback,
          obj     : obj,
          context : context
        });
      } else {
        // Load sequentially.
        for (i = 0, len = urls.length; i < len; ++i) {
          queue[type].push({
            urls    : [urls[i]],
            callback: i === len - 1 ? callback : null, // callback is only added to the last URL
            obj     : obj,
            context : context
          });
        }
      }
    }

    // If a previous load request of this type is currently in progress, we'll
    // wait our turn. Otherwise, grab the next item in the queue.
    if (pending[type] || !(p = pending[type] = queue[type].shift())) {
      return;
    }

    head || (head = doc.head || doc.getElementsByTagName('head')[0]);
    pendingUrls = p.urls;

    for (i = 0, len = pendingUrls.length; i < len; ++i) {
      url = pendingUrls[i];

      if (isCSS) {
        node = createNode('link', {
          charset: 'utf-8',
          'class': 'lazyload',
          href   : url,
          rel    : 'stylesheet',
          type   : 'text/css'
        });
      } else {
        node = createNode('script', {
          charset: 'utf-8',
          'class': 'lazyload',
          src    : url,
          'data-cfasync': 'false'
        });

        node.async = false;
      }

      if (env.ie) {
        node.onreadystatechange = function () {
          var readyState = this.readyState;

          if (readyState === 'loaded' || readyState === 'complete') {
            this.onreadystatechange = null;
            _finish();
          }
        };
      } else if (isCSS && (env.gecko || env.webkit)) {
        // Gecko and WebKit don't support the onload event on link nodes. In
        // WebKit, we can poll for changes to document.styleSheets to figure out
        // when stylesheets have loaded, but in Gecko we just have to finish
        // after a brief delay and hope for the best.
        if (env.webkit) {
          p.urls[i] = node.href; // resolve relative URLs (or polling won't work)
          poll();
        } else {
          setTimeout(_finish, 50 * len);
        }
      } else {
        node.onload = node.onerror = _finish;
      }

      head.appendChild(node);
    }
  }

  /**
  Begins polling to determine when pending stylesheets have finished loading
  in WebKit. Polling stops when all pending stylesheets have loaded.

  @method poll
  @private
  */
  function poll() {
    var css = pending.css, i;

    if (!css) {
      return;
    }

    i = styleSheets.length;

    // Look for a stylesheet matching the pending URL.
    while (i && --i) {
      if (styleSheets[i].href === css.urls[0]) {
        finish('css');
        break;
      }
    }

    pollCount += 1;

    if (css) {
      if (pollCount < 200) {
        setTimeout(poll, 50);
      } else {
        // We've been polling for 10 seconds and nothing's happened, which may
        // indicate that the stylesheet has been removed from the document
        // before it had a chance to load. Stop polling and finish the pending
        // request to prevent blocking further requests.
        finish('css');
      }
    }
  }

  return {

    /**
    Requests the specified CSS URL or URLs and executes the specified
    callback (if any) when they have finished loading. If an array of URLs is
    specified, the stylesheets will be loaded in parallel and the callback
    will be executed after all stylesheets have finished loading.

    Currently, Firefox doesn't provide any way to reliably determine when a
    stylesheet has finished loading. In Firefox, the callback will be
    executed after a brief delay. For information on a manual technique you
    can use to detect when CSS has actually finished loading in Firefox, see
    http://wonko.com/post/how-to-prevent-yui-get-race-conditions (which
    applies to LazyLoad as well, despite being originally written in in
    reference to the YUI Get utility).

    @method css
    @param {String|Array} urls CSS URL or array of CSS URLs to load
    @param {Function} callback (optional) callback function to execute when
      the specified stylesheets are loaded
    @param {Object} obj (optional) object to pass to the callback function
    @param {Object} context (optional) if provided, the callback function
      will be executed in this object's context
    @static
    */
    css: function (urls, callback, obj, context) {
      load('css', urls, callback, obj, context);
    },

    /**
    Requests the specified JavaScript URL or URLs and executes the specified
    callback (if any) when they have finished loading. If an array of URLs is
    specified and the browser supports it, the scripts will be loaded in
    parallel and the callback will be executed after all scripts have
    finished loading.

    Currently, only Firefox and Opera support parallel loading of scripts while
    preserving execution order. In other browsers, scripts will be
    queued and loaded one at a time to ensure correct execution order.

    @method js
    @param {String|Array} urls JS URL or array of JS URLs to load
    @param {Function} callback (optional) callback function to execute when
      the specified scripts are loaded
    @param {Object} obj (optional) object to pass to the callback function
    @param {Object} context (optional) if provided, the callback function
      will be executed in this object's context
    @static
    */
    js: function (urls, callback, obj, context) {
      load('js', urls, callback, obj, context);
    }

  };
})(this, this.document);

// IE Fix for createContextualFragment is undefined
// ToDo: Remove this when/if we upgrade extJS to 4.x
if (typeof Range != "undefined") {
    if (typeof Range.prototype.createContextualFragment == "undefined") {
        Range.prototype.createContextualFragment = function (html) {
            var doc = window.document;
            var container = doc.createElement("div");
            container.innerHTML = html;
            var frag = doc.createDocumentFragment(), n;
            while ((n = container.firstChild)) {
                frag.appendChild(n);
            }
            return frag;
        };
    }
}

// IE8 Fix for .filter() function handling
if (!Array.prototype.filter)
{
  Array.prototype.filter = function(fun /*, thisp */)
  {
    "use strict";

    if (this == null)
      throw new TypeError();

    var t = Object(this);
    var len = t.length >>> 0;
    if (typeof fun != "function")
      throw new TypeError();

    var res = [];
    var thisp = arguments[1];
    for (var i = 0; i < len; i++)
    {
      if (i in t)
      {
        var val = t[i]; // in case fun mutates this
        if (fun.call(thisp, val, i, t))
          res.push(val);
      }
    }

    return res;
  };
}

// IE8 fix for .indexOf() function handling
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
        "use strict";
        if (this == null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 1) {
            n = Number(arguments[1]);
            if (n != n) { // shortcut for verifying if it's NaN
                n = 0;
            } else if (n != 0 && n != Infinity && n != -Infinity) {
                n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
                return k;
            }
        }
        return -1;
    }
}

if (!Array.prototype.diff) {
    Array.prototype.diff = function(a) {
        return this.filter(function(i) { return !(a.indexOf(i) > -1); });
    }
}

String.format = function() {
  var s = arguments[0];
  for (var i = 0; i < arguments.length - 1; i++) {
    var reg = new RegExp("\\{" + i + "\\}", "gm");
    s = s.replace(reg, arguments[i + 1]);
  }

  return s;
}
