# Interface d'administration

Une interface d'administration ("l'admin") est disponible sur `/admin`.

L'implémentation utilise [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle), un générateur d'admin pour Symfony.

## Accès à l'admin

L'admin n'est accessible qu'aux comptes administrateurs déterminés par la variable d'environnement `ADMIN_EMAILS`.

En local, mettez l'email de votre compte dans `ADMIN_EMAILS` dans `.env.local` pour pouvoir accéder à l'admin.
