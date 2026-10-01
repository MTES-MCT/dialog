# Services

DiaLog fait appel à un certain nombre de services externes.

## API Géocodage IGN (ex-API Adresse)

DiaLog s'interface avec l'[API Géocodage de l'IGN](https://geoservices.ign.fr/documentation/services/services-geoplateforme/geocodage) pour le géocodage des adresses.

> **Note** : L'API Adresse a été [transférée à l'IGN](https://adresse.data.gouv.fr/blog/lapi-adresse-de-la-base-adresse-nationale-est-transferee-a-lign) en 2025. L'API est désormais hébergée sur `https://data.geopf.fr/geocodage`.

### Liens utiles

* [ADR-005 : Choix d'un service de géocodage](../adr/005_geocoding.md)
* [Documentation de l'API Géocodage IGN](https://geoservices.ign.fr/documentation/services/services-geoplateforme/geocodage)
* [Guide Etalab sur l'API Adresse](https://guides.etalab.gouv.fr/apis-geo/1-api-adresse.html) (historique)
* [Documentation générale autour de l'adresse et de la Base Adresse Nationale (BAN)](https://doc.adresse.data.gouv.fr/)

### Astuces

**Comment vérifier le géocodage d'une adresse ?**

Utilisez la commande utilitaire `app:geocode`.

La valeur affichée est un `POINT(X Y)` selon le format WKT, dans la projection standard EPSG:4326 (X = longitude et Y = latitude).

Exemple :

```bash
make console CMD="app:geocode '3 Rue des Tournesols 82000 Montauban'"
```

```console
POINT(1.386715 44.049081)
```

## API Geopf

DiaLog s'interface avec l'[API WFS de l'IGN](https://geoservices.ign.fr/documentation/services/services-geoplateforme/diffusion#70070) pour récupérer dynamiquement les géometries des voies et routes.

### Liens utiles

* [Documentation de l'API ] (https://geoservices.ign.fr/documentation/services/services-geoplateforme/diffusion#70070)
* [Documentation de la BDTOPO] (https://geoservices.ign.fr/sites/default/files/2023-10/DC_BDTOPO_3-3.pdf)
* [Fonctions de filtrage qui peuvent être utilisées dans le filtrage WFS] (https://docs.geoserver.org/main/en/user/filter/function_reference.html)

### Contours des collectivités (ADMIN EXPRESS COG)

Le filtre par collectivité de l'API publique et de la carte (`inseeCode`, `epciCode`, `departmentCode`, `regionCode`) s'appuie sur les contours officiels des collectivités, récupérés via ce même service WFS dans le référentiel [ADMIN EXPRESS COG](https://geoservices.ign.fr/adminexpress) (couches `ADMINEXPRESS-COG-CARTO.LATEST:commune`, `epci`, `departement` et `region`). Une restriction concerne une collectivité lorsque son emprise intersecte ce contour.

* Un contour est téléchargé la première fois qu'une collectivité est demandée, puis conservé dans la table `administrative_boundary` : les requêtes suivantes n'appellent plus le service.
* L'URL du service est celle de la variable d'environnement `APP_IGN_GEOCODER_BASE_URL`.
* Les contours déjà en base ne sont pas rafraîchis automatiquement. Pour suivre les évolutions annuelles du COG (fusions de communes, périmètres des EPCI), lancer cette commande à la main une fois le nouveau millésime publié :

```bash
make console CMD="app:administrative-boundaries:refresh"
```

## Signalements adresse vers l'IGN (Espace collaboratif / GCMS)

Les signalements d'adresses non reconnues sont envoyés à l'[Espace collaboratif IGN](https://espacecollaboratif.ign.fr) (API GCMS). DiaLog enregistre l'identifiant et le statut, reçoit les mises à jour de statut via un webhook et notifie l'équipe support par email.

**Documentation dédiée** : [Signalements adresse IGN (configuration, webhook, backoffice)](./ign-signalements.md).
