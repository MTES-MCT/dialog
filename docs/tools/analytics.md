# Analytics

Ce site utilise l'instance Matomo mutualisée de BetaGouv pour l'analyse du trafic. Matomo est une alternative open source à Google Analytics.

Vous pouvez :

* [Consulter les analytics du site sur stats.beta.gouv.fr](https://stats.beta.gouv.fr/index.php?module=CoreHome&action=index&date=yesterday&period=day&idSite=38)
* [Consulter la documentation du Matomo BetaGouv](https://doc.incubateur.net/communaute/travailler-a-beta-gouv/jutilise-les-outils-de-la-communaute/matomo)

## Configuration

| Variable d'environnement | Valeurs | Valeur par défaut |
|---|---|---|
| `MATOMO_ENABLED` | Vide ou `false` pour désactiver ; toute autre valeur pour activer (par exemple `true`) | Vide |

## Notes d'implémentation

La documentation BetaGouv suggère d'utiliser un _inline script_.

Or nous avons mis en place une protection CSP, ce qui interdit tout _inline script_.

Nous incluons donc le code de suivi Matomo via Webpack Encore (voir `webpack.config.js`).

Voir : https://matomo.org/faq/general/faq_20904/
