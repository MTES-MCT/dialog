# Interface d'administration

Une interface d'administration ("l'admin") est disponible sur `/admin`. 

L'implémentation utilise [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle), un générateur d'admin pour Symfony.

## Accès à l'admin

L'admin n'est accessible qu'à un seul compte : le compte administrateur, déterminé par la variable d'environnement `ADMIN_EMAIL`.

En local, mettez l'email de votre compte dans `ADMIN_EMAIL` dans `.env.local` pour pouvoir accéder à l'admin.
