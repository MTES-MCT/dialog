# Déploiement

Nous utilisons [Scalingo](https://dashboard.scalingo.com/) pour gérer les déploiements de DiaLog.

## Prérequis

Pour pouvoir faire un déploiement depuis Scalingo, vous devez au préalable vous créer un compte avec une adresse `@beta.gouv.fr` et être rajouté dans l'organisation par un membre de l'équipe.

## 🛠️ Staging

Les déploiements sur l'environnement staging s'effectuent manuellement.

Pour déployer :
* Allez sur le [dashboard Scalingo](https://dashboard.scalingo.com/apps/osc-fr1/dialog-staging) ;
* Cliquez sur [`Deploy`](https://dashboard.scalingo.com/apps/osc-fr1/dialog-staging/deploy/list) puis sur [`Manual deployments`](https://dashboard.scalingo.com/apps/osc-fr1/dialog-staging/deploy/manual) ;
* Selectionnez la branche que vous voulez déployer puis cliquez sur `Trigger deployment`.

Vous pouvez ensuite vérifier que votre branche est bien déployée en vous rendant sur https://dialog.incubateur.net/.

## 🚀 Production

Le projet est automatiquement déployé lorsqu'une _pull request_ est _merge_ sur la branche `main`.

Vous pouvez vérifier que votre déploiement s'est bien déroulé en vous rendant sur le [dashboard Scalingo](https://dashboard.scalingo.com/apps/osc-fr1/dialog/) pour avoir accès au détail du _build_ et en vérifiant l'application en production sur https://dialog.beta.gouv.fr.

## Retirer un environnement

Lorsqu'un environnement décrit ci-dessus n'est plus utile, il devrait être retiré pour libérer les ressources informatiques associées.

Pour ce faire :

* S'assurer que l'environnement n'est effectivement plus utilisé et qu'il peut être supprimé définitivement.
* Débrancher le nom de domaine de l'application.
* Retirer l'application sur Scalingo.
* Retirer l'environnement de cette documentation.

Note : vous aurez peut-être besoin de contacter [@]tristanrobert si vous n'avez pas les permissions suffisantes pour faire ces opérations.
