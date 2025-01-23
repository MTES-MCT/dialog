# Mailer

Nous utilisons le composant Symfony `symfony/mailer` pour gérer l'envoi d'emails de manière robuste et fiable.

## Environnement de développement

En environnement de développement, les emails sont interceptés par le container Docker `schickling/mailcatcher`. Cette configuration permet de :

- Visualiser tous les emails envoyés via une interface web
- Tester le rendu des emails dans différents clients
- Éviter l'envoi accidentel d'emails aux utilisateurs pendant le développement

### Accès à MailCatcher

Interface web : http://dialog.localhost:1080

## Environnement de production

En production, le service d'envoi d'emails est assuré par Brevo (anciennement Sendinblue), solution recommandée par la communauté beta.gouv.fr.

### Configuration

La configuration du service d'envoi d'emails se fait via la variable d'environnement `MAILER_DSN` dans les fichiers :
- `.env` pour la configuration par défaut
- `.env.local` pour la configuration spécifique à l'environnement
