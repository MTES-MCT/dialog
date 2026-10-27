---
title: "Comment localiser un arrêté : la collaboration entre DiaLog et la Base Adresse Nationale"
description: "Pour qu'une route fermée apparaisse au bon endroit dans un GPS, il faut d'abord localiser cette route. Derrière chaque arrêté publié par DiaLog se trouvent deux référentiels publics de l’IGN, la BD TOPO et la Base Adresse Nationale."
date: "2026-10-21"
callout:
    title: Devenez utilisateur DiaLog
    description: "Vous êtes sur le blog de DiaLog, solution de numérisation de la réglementation routière propulsée par Beta.gouv du Ministère des transports. Vous représentez une collectivité locale intéressée par DiaLog ? Prenez rendez-vous avec l’équipe directement ici."
    link:
        title: Contacter l'équipe
        url: mailto:dialog@beta.gouv.fr
tags:
  - Géodata
  - Adresse
  - IGN
---

:::callout
Pour qu'une route fermée apparaisse au bon endroit dans un GPS, il faut d'abord localiser cette route. Derrière chaque arrêté publié par DiaLog se trouvent deux référentiels publics de l’IGN, la BD TOPO et la Base Adresse Nationale. Nous avons échangé avec l'équipe de la BAN pour mieux comprendre son fonctionnement.
:::

<div class="contenu-article">

**Localiser un arrêté, un exercice géographique**

Quand une collectivité locale ferme une rue pour travaux ou limite le tonnage sur un pont, l'arrêté qui instaure cette mesure décrit le plus souvent le lieu avec des mots : un nom de rue, un numéro, une route avec deux points de repère. Pour devenir une donnée utilisable par les services de navigation, ce texte doit être traduit en géométrie, c'est-à-dire en tronçons de route précisément situés. DiaLog s'appuie pour cela sur les données de l'[IGN - Institut national de l'information géographique et forestière](https://www.ign.fr/) - service national de référence.

L’API de DiaLog utilise [BD TOPO](https://geoservices.ign.fr/bdtopo), la description vectorielle du territoire produite par l'IGN, limitée aux voies nommées, tronçons de route et points de repère kilométriques. Tous les calculs de localisation l'interrogent directement. La base est chargée à chaque mise à jour de l’IGN.

Elle intervient dès que DiaLog doit calculer lui-même la géométrie d'une restriction. Lorsqu'une agente ou un agent saisit une adresse dans le formulaire en ligne, l'auto-complétion puise dans ces données. Lorsqu'un département indique une route et ses points de repère, DiaLog retrouve ces points dans la BD TOPO, puis tous les tronçons concernés. Si la voie est inconnue, la saisie ne peut pas aller plus loin. Un signalement à l’IGN est possible directement depuis le site. DiaLog propose aussi de saisir manuellement le tracé de la restriction sur une carte. La géométrie est alors conservée telle quelle, sans comparaison avec la BD TOPO. C'est la solution de repli quand une adresse n'a pas de correspondance, et c'est aussi la forme sous laquelle arrivent beaucoup de données ouvertes déjà géolocalisées par les collectivités. Dans tous les cas, DiaLog vérifie que l'emprise reste sur le territoire de la collectivité qui publie l'arrêté, à partir des contours communaux de [geo.api.gouv.fr](https://geo.api.gouv.fr/).

{% image "./img/signalement-adresse-dialog.png", "Si la voie est inconnue, la saisie ne peut pas aller plus loin. Un signalement à l’IGN est possible directement depuis DiaLog.", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

<div class="legende-article">Si la voie est inconnue, la saisie ne peut pas aller plus loin. Un signalement à l’IGN est possible directement depuis DiaLog.</div>

**L'adresse, une affaire de communes**

La qualité de ces référentiels conditionne donc celle de DiaLog : une adresse absente de la BAN et l'arrêté ne peut pas être localisé de manière vérifiée.

Comment l'adresse est-elle produite en France ?

La loi 3DS a consacré le rôle des communes : elles seules nomment les voies, numérotent les parcelles et bâtiments. Chacune doit tenir une Base Adresse Locale (BAL) qui recense ses adresses. Deux équipes publiques les accompagnent, chacune à un bout de la chaîne.

En amont, la start-up d'État Bases Adresses Locales, portée par l'[Incubateur des territoires](https://incubateur.anct.gouv.fr/) de l'ANCT, outille et accompagne les communes. Elle a développé [Mes Adresses](https://mes-adresses.data.gouv.fr/), un outil de saisie utilisable sans compétence technique ni SIG, choisi par la grande majorité des communes qui ont publié leur BAL, ainsi que Mes Signalements, qui permet aux particuliers et aux organismes de proposer des corrections. Elle anime aussi des formations hebdomadaires et un réseau de partenaires locaux. Les collectivités mieux équipées, métropoles, agglomérations ou plateformes régionales, publient leurs BAL avec leurs propres outils.

En aval, l'IGN gère la [Base Adresse Nationale](https://adresse.data.gouv.fr/), elle-même née en start-up d'État avant d'être intégrée à l'Institut. Son équipe assemble les BAL, complète provisoirement les territoires qui n'en ont pas encore à partir de sources historiques, ajoute les informations qui ne relèvent pas des communes, comme le code postal, puis diffuse le tout et anime la communauté des réutilisateurs : gestionnaires de réseaux, opérateurs de fibre, administrations, assureurs, éditeurs de cartographie.

**Bon à savoir :** ni l'ANCT ni l'IGN ne modifient une adresse. Une erreur dans un nom de rue reste en place jusqu'à ce que la commune la corrige.

**Rappel : le circuit de la donnée chez DiaLog**

Cette règle fait écho à celle de DiaLog. DiaLog publie l'arrêté tel qu'il a été signé parce qu'il a une valeur juridique, et ne corrige jamais par exemple une date erronée. Des mécanismes de filtrage sont à l’étude pour garantir une haute qualité de données aux réutilisateurs.

Comme la BAN, DiaLog dépend de milliers de producteurs aux moyens très inégaux : la personne qui saisit un arrêté peut être une ingénieure des routes dans une métropole ou le secrétaire de mairie d'un village de soixante habitants. Les canaux d’édition de la donnée sont semblables également, avec un formulaire simple pour les petites communes, des connexions avec les logiciels des éditeurs pour les collectivités équipées et le moissonnage de données sur les portails de données ouvertes des collectivités.

{% image "./img/circuit-donnees-dialog.png", "Circuit des données de restriction dans DiaLog : les arrêtés arrivent par trois canaux (formulaire en ligne, connexion éditeur, moissonnage), rejoignent la base DiaLog puis repartent vers les services de navigation aux formats Datex II et CIFS", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

DiaLog suit avec beaucoup d’attention l’évolution des outils proposés par l’IGN à ses utilisateurs et partenaires, afin d’optimiser la mise à jour et la circulation des données.

**Prochaine étape : rejoindre la Suite territoriale ?**

L'[ANCT - Agence Nationale de la Cohésion des Territoires](https://agence-cohesion-territoires.gouv.fr/) - réunit déjà dans la [Suite territoriale](https://suiteterritoriale.anct.gouv.fr/) un ensemble de services numériques pour les collectivités : une identité professionnelle commune avec ProConnect, une messagerie, un espace de stockage et des applications mutualisées, parmi lesquelles figure Mes Adresses. L'Incubateur des Territoires l'opère notamment pour les communes de moins de 3 500 habitants, celles que DiaLog a le plus de mal à atteindre.

DiaLog souhaite construire avec l'ANCT une collaboration pour prendre place dans la Suite. L'agent qui saisit un arrêté de circulation est souvent le mieux placé pour remarquer qu'une rue manque dans la BAL de sa commune. Dans un même environnement numérique, connecté avec le même identifiant, il pourrait déclarer une fermeture de voie et compléter ses adresses sans changer d'outil ni ressaisir les mêmes informations. Les collectivités bénéficieraient ainsi du principe « dites-le-nous une fois » : une donnée renseignée une seule fois alimenterait à la fois la base nationale des réglementations de circulation et la Base Adresse Nationale, avec moins de saisies et donc moins d'erreurs.

À suivre !

**Stéphane Schultz - coach produit DiaLog**
</div>
