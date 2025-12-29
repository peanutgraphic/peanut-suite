/**
 * Peanut Suite Visitor Tracking
 *
 * Lightweight tracking script for visitor analytics and attribution.
 *
 * Usage:
 *   <script src="https://yoursite.com/wp-json/peanut/v1/track/snippet.js" async></script>
 *   <script>
 *     window.peanutConfig = { siteId: 'your-site-id' };
 *   </script>
 */
(function(window, document) {
    'use strict';

    // Configuration
    var config = window.peanutConfig || {};
    var endpoint = config.endpoint || (window.peanutData ? window.peanutData.apiUrl : '/wp-json/peanut/v1');
    var siteId = config.siteId || '';
    var debug = config.debug || false;

    // Storage keys
    var VISITOR_KEY = 'peanut_vid';
    var SESSION_KEY = 'peanut_sid';
    var COOKIE_DAYS = 365;
    var SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes

    /**
     * Generate a unique ID
     */
    function generateId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Get or create visitor ID (persistent)
     */
    function getVisitorId() {
        var vid = getCookie(VISITOR_KEY) || localStorage.getItem(VISITOR_KEY);
        if (!vid) {
            vid = generateId();
            setCookie(VISITOR_KEY, vid, COOKIE_DAYS);
            try {
                localStorage.setItem(VISITOR_KEY, vid);
            } catch (e) {}
        }
        return vid;
    }

    /**
     * Get or create session ID (temporary)
     */
    function getSessionId() {
        var sid = sessionStorage.getItem(SESSION_KEY);
        var lastActivity = sessionStorage.getItem(SESSION_KEY + '_time');
        var now = Date.now();

        // Check if session expired
        if (sid && lastActivity && (now - parseInt(lastActivity, 10)) > SESSION_TIMEOUT) {
            sid = null;
        }

        if (!sid) {
            sid = generateId();
        }

        sessionStorage.setItem(SESSION_KEY, sid);
        sessionStorage.setItem(SESSION_KEY + '_time', now.toString());

        return sid;
    }

    /**
     * Cookie helpers
     */
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1);
            if (c.indexOf(nameEQ) === 0) {
                return decodeURIComponent(c.substring(nameEQ.length));
            }
        }
        return null;
    }

    /**
     * Parse URL parameters
     */
    function getUrlParams() {
        var params = {};
        var search = window.location.search.substring(1);
        if (!search) return params;

        var pairs = search.split('&');
        for (var i = 0; i < pairs.length; i++) {
            var pair = pairs[i].split('=');
            params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
        }
        return params;
    }

    /**
     * Get UTM parameters
     */
    function getUtmParams() {
        var params = getUrlParams();
        return {
            utm_source: params.utm_source || null,
            utm_medium: params.utm_medium || null,
            utm_campaign: params.utm_campaign || null,
            utm_term: params.utm_term || null,
            utm_content: params.utm_content || null
        };
    }

    /**
     * Get referrer info
     */
    function getReferrer() {
        var ref = document.referrer;
        if (!ref) return null;

        try {
            var url = new URL(ref);
            // Exclude same-site referrer
            if (url.hostname === window.location.hostname) {
                return null;
            }
            return ref;
        } catch (e) {
            return ref;
        }
    }

    /**
     * Log debug messages
     */
    function log() {
        if (debug && console && console.log) {
            console.log.apply(console, ['[Peanut]'].concat(Array.prototype.slice.call(arguments)));
        }
    }

    /**
     * Send tracking request
     */
    function send(eventType, data, callback) {
        var visitorId = getVisitorId();
        var sessionId = getSessionId();
        var utm = getUtmParams();

        var payload = {
            visitor_id: visitorId,
            session_id: sessionId,
            event_type: eventType,
            page_url: window.location.href,
            page_title: document.title,
            referrer: getReferrer(),
            utm_source: utm.utm_source,
            utm_medium: utm.utm_medium,
            utm_campaign: utm.utm_campaign,
            utm_term: utm.utm_term,
            utm_content: utm.utm_content,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            timestamp: new Date().toISOString()
        };

        // Merge custom data
        if (data && typeof data === 'object') {
            payload.custom_data = data;
        }

        // Add site ID if configured
        if (siteId) {
            payload.site_id = siteId;
        }

        log('Sending', eventType, payload);

        // Use Beacon API if available (works even on page unload)
        if (navigator.sendBeacon && eventType !== 'identify') {
            var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            navigator.sendBeacon(endpoint + '/track', blob);
            if (callback) callback(true);
            return;
        }

        // Fallback to XHR
        var xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint + '/track', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                log('Response', xhr.status, xhr.responseText);
                if (callback) {
                    callback(xhr.status >= 200 && xhr.status < 300);
                }
            }
        };
        xhr.send(JSON.stringify(payload));
    }

    /**
     * Track pageview
     */
    function trackPageview(data) {
        send('pageview', data);
    }

    /**
     * Track custom event
     */
    function trackEvent(eventName, data) {
        send(eventName, data);
    }

    /**
     * Identify visitor
     */
    function identify(email, traits, callback) {
        var visitorId = getVisitorId();

        var payload = {
            visitor_id: visitorId,
            email: email,
            traits: traits || {}
        };

        log('Identifying', payload);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint + '/track/identify', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                log('Identify response', xhr.status);
                if (callback) {
                    callback(xhr.status >= 200 && xhr.status < 300);
                }
            }
        };
        xhr.send(JSON.stringify(payload));
    }

    /**
     * Get current visitor ID (for external use)
     */
    function getVisitor() {
        return {
            visitor_id: getVisitorId(),
            session_id: getSessionId()
        };
    }

    // Public API
    var peanut = {
        track: trackEvent,
        pageview: trackPageview,
        identify: identify,
        getVisitor: getVisitor,
        _send: send
    };

    // Expose globally
    window.peanut = peanut;

    // Auto-track pageview on load (unless disabled)
    if (config.autoTrack !== false) {
        if (document.readyState === 'complete') {
            trackPageview();
        } else {
            window.addEventListener('load', function() {
                trackPageview();
            });
        }
    }

    // Track history changes (SPA support)
    if (config.trackHistory !== false) {
        var originalPushState = history.pushState;
        var originalReplaceState = history.replaceState;

        history.pushState = function() {
            originalPushState.apply(this, arguments);
            trackPageview();
        };

        history.replaceState = function() {
            originalReplaceState.apply(this, arguments);
            trackPageview();
        };

        window.addEventListener('popstate', function() {
            trackPageview();
        });
    }

    log('Initialized', { visitor: getVisitorId(), session: getSessionId() });

})(window, document);
