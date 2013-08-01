brow = navigator.appName;
vers = parseInt(navigator.appVersion);
n4   = (document.layers) ? true:false;
moz  = (brow == "Netscape" && vers >= 5) ? true:false;

/**
 * Gets the value of the specified cookie.
 *
 * name  Name of the desired cookie.
 *
 * Returns a string containing value of specified cookie,
 *   or null if cookie does not exist.
 */
function GetCookie(name) {
    var dc = document.cookie;
    var prefix = name + '=';
    var begin = dc.indexOf('; ' + prefix);
    if (begin == -1) {
        begin = dc.indexOf(prefix);
        if (begin != 0)
        	{ return null; }
    }
    else
    	{ begin += 2; }
    var end = document.cookie.indexOf(";", begin);
    if (end == -1)
    	{ end = dc.length; }
    return unescape(dc.substring(begin + prefix.length, end));
}
function LoadName() {
	var name = GetCookie("spieler");
	if (null != name)
		{ gComboInput.value = name; }
}

function UpdateComboBoxList(combo, values) {
	if (gComboInput.value.toLowerCase() == gFirstLetters)
		{ return; }

	ListClear();

	gFirstLetters = gComboInput.value.toLowerCase();
	var searchLen = gFirstLetters.length;
	if (0 == searchLen)
		{ return; }

	var lcValue = "";
	for (var i = 0; i < values.length; i++) {
		lcValue = values[i].toLowerCase();
		if (lcValue.substr(0, searchLen) != gFirstLetters)
			{ continue; }
		ListAddItem(values[i]);
	}
	gListItemCnt = gListElement.childNodes.length;
}

function ListClear() {
	while (gListElement.hasChildNodes())
		{ gListElement.removeChild(gListElement.firstChild); }
	gListItemCnt = 0;
	gListIdx = -1;
}
function ListAddItem(label) {
	var link = document.createElement('a');
	var div  = document.createElement('div');
	div.appendChild(document.createTextNode(label));
	div.id          = gListItemCnt;
	div.onclick     = ListItemOnClickHandler;
	div.onmouseover = ListItemOnMouseOverHandler;
	link.appendChild(div);
	gListElement.appendChild(link);
	++gListItemCnt;
}

function ListLowlight(idx) {
	var item = gListElement.childNodes[idx];
	item.style.color      = '';
	item.style.background = '';
	item = item.firstChild;
	item.style.color      = '';
	item.style.background = '';
}
function ListHighlight(idx) {
	var item = gListElement.childNodes[idx];
	item.style.color      = '#fff';
	item.style.background = 'navy';
	item = item.firstChild;
	item.style.color      = '#fff';
	item.style.background = 'navy';
}

function ListScrollTo(newIdx) {
	if (newIdx < 0 || newIdx >= gListItemCnt)
		{ return; }
	if (gListIdx != -1) {
		ListLowlight(gListIdx);
		gListIdx = -1;
	}
	ListHighlight(newIdx);
	gListIdx = newIdx;
}
function ListScrollUp()
	{ ListScrollTo(gListIdx-1); }
function ListScrollDown()
	{ ListScrollTo(gListIdx+1); }

function ListItemOnClickHandler(evt) {
	var obj = (!evt) ? window.event.srcElement : evt.target;
	ListAcceptChoice(obj);
}
function ListItemOnMouseOverHandler(evt) {
	var obj = (!evt) ? window.event.srcElement : evt.target;
	ListScrollTo(parseInt(obj.id));
}
function ListAcceptChoice(obj) {
	gComboInput.value = obj.innerHTML;
	gComboInput.onkeyup();
	// IE Bug: move cursor to the end
	if (gComboInput.createTextRange) {
		var tr = gComboInput.createTextRange();
		tr.collapse(false);
		tr.select();
	}
	gComboInput.focus();
	// combo box gets redrawn (with the new prefix)
	// optionally: hide combo box by clearing
	ListClear();
}
function ListKeyDownHandler (evt) {
	evt = (evt) ? evt : ((event) ? event : null);
	if (n4) {
		if (evt.modifiers && Event.ALT_MASK || evt.modifiers && Event.CTRL_MASK)
			{ return true; }
	}
	if (moz) {
		if (evt.altKey || evt.ctrlKey)
			{ return true; }
	}
	switch(evt.keyCode) {
		case 38:	// up
			ListScrollUp();
			break;
		case 40:	// down
			ListScrollDown();
			break;
		default:
			return true;
	}
	evt.cancelBubble = true;
	evt.returnValue  = false;
	return false;
}
function ListKeyPressHandler (evt) {
	evt = (evt) ? evt : ((event) ? event : null);
	if (!evt)
		{ return true; }
	if (n4) {
		if (evt.modifiers && Event.ALT_MASK || evt.modifiers && Event.CTRL_MASK)
			{ return true; }
	}
	if (moz) {
		if (evt.altKey || evt.ctrlKey)
			{ return true; }
	}
	switch(evt.keyCode) {
		case 13:	// enter
			if (-1 != gListIdx)
				{ ListAcceptChoice(gListElement.childNodes[gListIdx].firstChild); }
			break;
		default:
			return true;
	}
	evt.cancelBubble = true;
	evt.returnValue  = false;
	return false;
}
function InstallNixgesagtHandlers() {
	var nixChildren = document.getElementById('nixgesagt').childNodes;
	for (var i=0, span=null; i<nixChildren.length; i++) {
		span = nixChildren[i];
		if (1 != span.nodeType)
			{ continue; }
		span.onclick = ListItemOnClickHandler;
	}
}

// init
$(function(){
	LoadName();
	InstallNixgesagtHandlers();
	gComboInput.onkeydown  = ListKeyDownHandler;
	gComboInput.onkeypress = ListKeyPressHandler;
	gComboInput.focus();
});