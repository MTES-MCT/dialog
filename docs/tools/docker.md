# Docker

## Docker Compose

### Problèmes de permissions

Il se peut que vous rencontriez des problèmes de permissions en modifiant les fichiers autogénérés par les recettes Symfony.

Sous Linux, forcez la propriété de ces fichiers par votre utilisateur de session avec :

```
$ sudo chown -R USERNAME:USERNAME .
```

(Remplacez `USERNAME` par votre nom d'utilisateur, _c.f._ `$ whoami`.)
