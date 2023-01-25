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

Exemple :

```bash
make console CMD="app:geocode '3 Rue des Tournesols 82000 Montauban'"
```

```console
POINT(44.049081 1.386715)
```
