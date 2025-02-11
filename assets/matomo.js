var _paq = window._paq = window._paq || [];
/* tracker methods like "setCustomDimension" should be called before "trackPageView" */
_paq.push(["setDoNotTrack", true]);
_paq.push(["disableCookies"]);
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);

// Active le Content Tracking
// On utilise trackAllContentImpressions plutôt que trackVisibleContentImpressions
// car ce dernier déclenche un scan périodique de tout le DOM, ce qui
// est coûteux pour le terminal.
// Le tracking des interactions permet tout de même d'étudier le ratio interactions / impressions.
// https://developer.matomo.org/guides/content-tracking
_paq.push(['trackAllContentImpressions']);

(function () {
    var u = "https://stats.beta.gouv.fr/";
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', '38']);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
})();
