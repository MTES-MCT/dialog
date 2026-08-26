if (process.env.MATOMO_ENABLED === 'true') {
    var _paq = window._paq = window._paq || [];
    _paq.push(["disableCookies"]);
    _paq.push(["alwaysUseSendBeacon"]);
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function () {
        var u = "https://stats.beta.gouv.fr/";
        _paq.push(['setTrackerUrl', u + 'matomo.php']);
        _paq.push(['setSiteId', '38']);
        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
        g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
    })();
}
