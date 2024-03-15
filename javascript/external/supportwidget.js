// IE6+SSL fix courtesy of http://www.tribalogic.net/

;(function(window) {
    var document = window.document,
        urlWithScheme = /^([a-zA-Z]+:)?\/\//,
        settings = {
            url:            null, // required
            tabId:          "ask-us",
            tabText:        "Support", // most browsers will use the tabID image rather than this text
            tabColor:       "#000000",

            // the remaining settings are optional and listed here so users of the library know what they can configure:
            tabImageURL:    null,       // optional; overrides URL generated from tabID
            tabPosition:    'Left',     // or 'Right'
            forceSuggestions:    false,       // force search before the ticket form
            hide_tab:       false,      // if true, don't display the tab after initialization
            request_subject:      null,  // pre-populate the ticket submission form subject
            request_description:  null,  //  "     "      "     "      "        "   description
            requester_name:       null,  //  "     "      "     "      "        "   user name
            requester_email:      null,   //  "     "      "     "      "        "   user email
            ticketTypeId: 0
        },
        // references to elements on the page:
        tab,
        overlay,
        container,
        closeButton,
        iframe,
        scrim;

    function attempt(fn) {
        try {
            return fn();
        } catch(e) {
            if (window.console && window.console.log && window.console.log.apply) {
                window.console.log("Feedback Error: ", e);
            }
        }
    }

    function bindEvent(element, eventName, callback) {
        if (element && element.addEventListener) {
            element.addEventListener(eventName, callback, false);
        } else if (element && element.attachEvent) {
            element.attachEvent('on' + eventName, callback);
        }
    }

    function prependSchemeIfNecessary(url) {
        if (url && !(urlWithScheme.test(url))) {
            return document.location.protocol + '//' + url;
        } else {
            return url;
        }
    }

    // must be called after the DOM is loaded
    function createElements() {
        tab = document.createElement('div');
        tab.setAttribute('id', 'feedback_tab');
        tab.setAttribute('href', '#');
        tab.style.display = 'none';
        document.body.appendChild(tab);

        overlay = document.createElement('div');
        overlay.setAttribute('id', 'feedback_overlay');
        overlay.style.display = 'none';
        overlay.innerHTML = '<div id="feedback_container">' +
            '  <div class="header" id="feedback_close"></div>' +
            '  <iframe id="feedback_body" frameborder="0" scrolling="auto" allowTransparency="true"></iframe>' +
            '</div>' +
            '<div id="feedback_scrim">&nbsp;</div>';
        document.body.appendChild(overlay);

        container   = document.getElementById('feedback_container');
        closeButton = document.getElementById('feedback_close');
        iframe      = document.getElementById('feedback_body');
        scrim       = document.getElementById('feedback_scrim');

        bindEvent(tab,          'click', function() { window.CESupportWidget.show(); });
        bindEvent(closeButton,  'click', function() { window.CESupportWidget.hide(); });
        bindEvent(scrim,        'click', function() { window.CESupportWidget.hide(); });
    }

    function configure(options) {
        var prop;
        for (prop in options) {
            if (options.hasOwnProperty(prop)) {
                if (prop === 'url') {
                    settings[prop] = prependSchemeIfNecessary(options[prop]);
                } else {
                    settings[prop] = options[prop];
                }
            }
        }
    }

    function tabImageURL() {
        if (settings.tabImageURL) {
            return settings.tabImageURL;
        } else {
            var url = settings.url + '/javascript/external/images/tab_' + settings.tabId;
            if (settings.tabPosition === 'right') {
                url += '_right';
            }
            url += '.png';
            return url;
        }
    }

    function updateTabImage() {
        var url = tabImageURL();
        var arVersion = window.navigator && window.navigator.appVersion.split("MSIE");
        var version = parseFloat(arVersion[1]);
        if ((version >= 5.5) && (version < 7) && (document.body.filters)) {
            tab.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + url + "', sizingMethod='crop')";
        } else {
            tab.style.backgroundImage = 'url(' + url + ')';
        }
    }

    function updateTab() {
        if (settings.hide_tab) {
            tab.style.display = 'none';
        } else {
            updateTabImage();
            tab.setAttribute('title', settings.tabText);
            tab.setAttribute('class', 'FeedbackTab' + settings.tabPosition);
            tab.setAttribute('className', 'FeedbackTab' + settings.tabPosition);
            tab.innerHTML = settings.tabText;
            tab.style.backgroundColor = settings.tabColor;
            tab.style.borderColor = settings.tabColor;
            tab.style.display = 'block';
        }
    }

    function cancelEvent(e) {
        var event = e || window.event || {};
        event.cancelBubble = true;
        event.returnValue = false;
        event.stopPropagation && event.stopPropagation();
        event.preventDefault && event.preventDefault();
        return false;
    }

    function getDocHeight(){
        return Math.max(
            Math.max(document.body.scrollHeight, document.documentElement.scrollHeight),
            Math.max(document.body.offsetHeight, document.documentElement.offsetHeight),
            Math.max(document.body.clientHeight, document.documentElement.clientHeight)
        );
    }

    function getScrollOffsets(){
        return {
            left: window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
            top:  window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
        };
    }

    // get the URL for the "loading" page to be used as iframe src while
    // loading the Dropbox.
    function loadingURL() {
        return settings.url + '/javascript/external/loading.html';
    }

    function dropboxURL() {
        var url = settings.url + "/index.php?fuse=support&view=supportwidgetform&controller=index";
        if (settings.request_subject)     { url += '&subject='      + settings.request_subject; }
        if (settings.request_description) { url += '&description='  + settings.request_description; }
        if (settings.requester_name)      { url += '&name='         + settings.requester_name; }
        if (settings.requester_email)     { url += '&email='        + settings.requester_email; }
        if (settings.forceSuggestions)    { url += '&forceSuggestions='  + settings.forceSuggestions; }
        if (settings.ticketTypeId)  { url += '&ticketTypeId='  + settings.ticketTypeId; }
        return url;
    }

    function initialize(options) {
        if (!tab) { createElements(); }
        configure(options);
        updateTab();
        iframe.src = loadingURL();
        window.addEventListener('message', function(e) {
            if (e.data === 'hideSupportWidget') {
                hide();
            }
        }, false);
    }

    function show(evt) {
        iframe.src = dropboxURL();
        overlay.style.height = scrim.style.height = getDocHeight() + 'px';
        container.style.top = getScrollOffsets().top + 50 + 'px';
        overlay.style.display = "block";
        return cancelEvent(evt);
    }

    function hide(evt) {
        overlay.style.display = 'none';
        iframe.src = loadingURL();
        return cancelEvent(evt);
    }

    var CESupportWidget = {

        /*
                PUBLIC API

                Methods in the public API can be used as callbacks or as direct calls. As such,
                they will always reference "CESupportWidget" instead of "this." Each one is wrapped
                in a try/catch block to ensure that including CESupportWidget doesn't break the page.
        */

        /*
         *  Build and render the CESupportWidget tab and build the frame for the CESupportWidget overlay,
         *  but do not display it.
         *  @see settings for options
         *  @param {Object} options
         */
        init: function(options) {
            attempt(function() { return initialize(options); });
        },

        /*
         * Alias for #init.
         */
        update: function(options) {
            attempt(function() { return initialize(options); });
        },

        /*
         *  Render the CESupportWidget. Alias for #show.
         *  @see #show
         */
        render: function(evt) {
            attempt(function() { return show(evt); });
        },

        /*
         *  Show the CESupportWidget. Aliased as #render.
         *  @params {Event} event the DOM event that caused the show; optional
         *  @return {false} false always, in case users want to bind it to an
         *                  onclick or other event and want to prevent default behavior.
         */
        show: function(evt) {
            attempt(function() { return show(evt); });
        },

        /*
         *  Hide the CESupportWidget.
            *  @params {Event} event the DOM event that caused the show; optional
            *  @return {false} false always, in case users want to bind it to an
            *                  onclick or other event and want to prevent default behavior.
            */
        hide: function (evt){
            attempt(function() { return hide(evt); });
        }
    };

    bindEvent(window, 'load', function() {
        if (window.supportwidget_params) {
            CESupportWidget.init(window.supportwidget_params);
        }
    });

    window.CESupportWidget = CESupportWidget;

}(this.window || this));