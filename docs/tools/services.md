# Services

DiaLog fait appel à un certain nombre de services externes.

## API Adresse

DiaLog s'interface avec l'[API Adresse](https://adresse.data.gouv.fr/api-doc/adresse) pour le géocodage des adresses.

### Liens utiles

* [ADR-005 : Choix d'un service de géocodage](../adr/005_geocoding.md)
* [Documentation de l'API Adresse](https://adresse.data.gouv.fr/api-doc/adresse)
* [Guide Etalab sur l'API Adresse](https://guides.etalab.gouv.fr/apis-geo/1-api-adresse.html)
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

DiaLog s'interface avec l'[API geoservices de l'IGN] (https://data.geopf.fr/wfs/ows) pour récupérer dynamiquement les géometries des voies et routes.

### Liens utiles

* [Documentation de l'API ] (https://geoservices.ign.fr/documentation/services/services-geoplateforme/diffusion#70070)
* [Documentation de la BDTOPO] (https://geoservices.ign.fr/sites/default/files/2023-10/DC_BDTOPO_3-3.pdf)
* [Fonctions de filtrage qui peuvent être utilisées dans le filtrage WFS] (https://docs.geoserver.org/main/en/user/filter/function_reference.html)
