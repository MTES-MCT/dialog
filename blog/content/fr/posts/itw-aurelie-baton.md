---
title: Le design chez DiaLog - Entretien avec Aurélie Baton
description: Derrière le service numérique, il y a une équipe. Jetez un oeil sous le capot de DiaLog avec Aurélie, designer sur DiaLog.
date: "2025-03-25"
callout:
    title: Devenez utilisateur DiaLog
    description: "Vous êtes sur le blog de DiaLog, solution de numérisation de la réglementation routière propulsée par Beta.gouv du Ministère des transports. Vous représentez une collectivité locale intéressée par DiaLog ? Prenez rendez-vous avec l’équipe directement ici."
    link:
        title: Contacter l'équipe
        url: mailto:dialog@beta.gouv.fr
tags:
  - Interview
  - Design
---

:::callout
Derrière le service numérique, il y a une équipe. Jetez un oeil dans les coulisses de DiaLog avec Aurélie, designer sur DiaLog.
:::

<div class="contenu-article">

{% image "./img/PhotoAB.png", "Aurélie Baton", [300, 300], "(max-width: 300px) 80vw, 300px", "image-article image-article-profil" %}

**Quels sont les enjeux de design pour DiaLog par rapport à d’autres sujets sur lesquels tu as déjà travaillé ?**

Pour DiaLog l’enjeu principal est de faire en sorte que les agents publics adoptent notre solution sans ajouter de charge ni de complexité. Ils utilisent déjà souvent des solutions pour numériser leurs documents administratifs. Notre objectif est de leur éviter la double saisie. Pour cela nous étudions leur parcours utilisateur pour nous adapter aux outils qu’ils ont l’habitude d’utiliser. Nous proposons par exemple l’export de l’arrêté complet dans DiaLog sous un format de document. 

Il y a également un enjeu de faciliter la prise en main de DiaLog. Le problème des outils métiers dans l’administration ou même en entreprise est qu’ils peuvent devenir très complexes. Ces outils permettent souvent de faire beaucoup de choses, mais la complexité apparaît trop dans l’interface. Pour DiaLog, on ne peut pas se le permettre sinon nous n’auront pas l’adoption de la part des agents. 

L’enjeu est donc de décomplexifier tout en permettant une flexibilité : on commence par un formulaire assez simple lors de la création d’un arrêté avec quelques champs à remplir, puis sur la phase suivante on sélectionne le type de restrictions de circulation. Nous avons mis différents niveaux de progression, tous les champs ne sont pas directement visibles dans le formulaire. Par exemple, le cas général par défaut s’applique à tous les véhicules, mais si cela s’applique à une catégorie spécifique, on clique et les choix apparaissent. 

Etape 1 : sélection par défaut de tous les véhicules

<figure>
{% image "./img/aurelie-itw-pic1.png", "L'option 'Tous les véhicules' est sélectionnée par défaut dans l’interface de DiaLog", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}
<figcaption>L'option "Tous les véhicules" est sélectionnée par défaut dans l’interface de DiaLog</figcaption>
</figure>

Etape 2 : sélection de certains véhicules uniquement

<figure>
{% image "./img/aurelie-itw-pic2.png", 'Option "Certains véhicules" affichée au clic uniquement', [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}
<figcaption>Option “Certains véhicules” affichée au clic uniquement</figcaption>
</figure>

Nous appliquons le même principe pour les exceptions. DiaLog permet d’indiquer des exceptions aux restrictions de circulation, par exemple : interdit à tous les véhicules sauf vélos et véhicules de secours, etc.. Mais nous n’affichons pas toutes les exceptions par défaut, il s’agit encore d’un affichage progressif.

Etape 1 : bouton pour définir une exception (pas obligatoire)

<figure>
{% image "./img/aurelie-itw-pic3.png", 'Option pour définir une exception, le détail est "caché" par défaut', [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}
<figcaption>Option pour définir une exception, le détail est "caché" par défaut</figcaption>
</figure>

Etape 2 : sélection possible des exceptions après avoir cliqué sur le bouton
 
 <figure>
{% image "./img/aurelie-itw-pic4.png", 'Après avoir cliqué sur "Définir une exception"', [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}
<figcaption>Après avoir cliqué sur "Définir une exception"</figcaption>
</figure>

Cette manière progressive d’afficher les champs et options au fur et à mesure permet de rendre l’interface moins complexe et plus facile de prise en main.

**Tu n’avais pas d’expérience du secteur de la voirie et de la réglementation locale. Comment as-tu travaillé pour ce projet ?**

Au début durant la phase d’investigation Mathieu et Stéphane ont récolté beaucoup d’informations sur les usages, les acteurs, les problématiques terrain... Ensuite nous avons fait une phase de recherche avec des entretiens utilisateurs auprès d’agents de communes, de métropoles, de transporteurs. Les parcours utilisateurs ont été cartographiés à partir des parcours existants depuis la demande de travaux, en passant par les étapes jusqu’à la signature et la diffusion, l’abrogation de l’arrêté... Puis nous avons testé les premières maquettes auprès de quelques utilisateurs et utilisé les retours de ces tests pour mettre à jour les maquettes.

Au quotidien nous étudions les arrêtés publiés pour trouver des récurrences ou au contraire des exceptions. Nous avons également des retours via des sondages auprès des utilisateurs que nous faisons régulièrement. Dans l’interface de DiaLog il y a également un bouton “Donnez votre avis” qui nous permet de recueillir des retours.

**Comment t’organises-tu au quotidien pour travailler sur ce projet ?**

Toutes les 2 semaines on a des réunions de planning avec l’équipe. On définit les priorités en fonction de l’avancement, des retours utilisateurs et des nouvelles demandes. J’en discute avec Mathieu le chef de projet et l’équipe de développement pour créer des stories. À partir de chaque story je fais des maquettes avec des propositions de fonctionnalités ou d’interfaces. Je les montre à l’équipe pour lever des interrogations. J’ai besoin aussi de discuter des implications techniques pour ne pas proposer des choix trop compliqués à mettre en œuvre pour l’équipe de développement.

En moyenne je travaille environ 5 jours par mois sur ce projet.

**Comment intègres-tu les impératifs d’accessibilité et d’écoconception des solutions numériques d’État dans ton travail ?**

Les développeurs de l’équipe sont déjà formés à l’écoconception : on peut amener la question sans que tout le monde ne prenne peur. On a pu mettre en place la démarche [RGESN](https://ecoresponsable.numerique.gouv.fr/publications/referentiel-general-ecoconception/), avec une revue tous les 6 mois. Même si nous pensons à l’écoconception dans notre travail au quotidien, cette évaluation permet de nous challenger et examiner des éléments sur lesquels nous n’avons pas forcément la main. Globalement cela ne représente pas une tâche trop lourde en raison de nos pratiques bien en place. Une des bonnes pratiques du référentiel par exemple est de fluidifier le parcours utilisateur et de limiter les fonctionnalités à l’essentiel : on le fait dès la conception. Il y a d’autres bonnes pratiques comme pour les mécanismes d’auto-complétion des champs de formulaire, on ne les lance qu’au 3ème caractère, etc.

**Qu’est-ce qui te déplaît ou au contraire te plaît le plus dans ce projet ?**

J’apprécie particulièrement le mode agile : les nouveautés sortent rapidement, on a des retours rapidement. On ne fonce pas tête baissée dans une solution pour s’apercevoir un an après que ça ne marche pas. 

C’est intéressant de travailler dans le cadre de la communauté Beta.gouv avec les partages d’expériences, on peut demander à d’autres équipes comment ils règlent certains problèmes : pour le système de design de l’Etat (DSFR), l’accessibilité, ou d’autres sujets.

Ce que j’apprécie énormément aussi dans ce projet, c’est qu’on est même allé au-delà de l’écoconception en faisant plusieurs ateliers de design systémiques avec l’équipe, c’est super intéressant.

Sans oublier toute l’équipe DiaLog qui rend le travail plus simple et agréable au quotidien !

**Parlons de toi maintenant. Comment es-tu devenue designer ?**

A la base, j’ai une formation de traductrice et de rédactrice technique ! J’ai intégré IBM où je rédigeais la documentation des logiciels que l’on développait. J’aimais bien travailler avec les développeurs. Le design commençait à se développer dans les entreprises et j’ai pu intégrer une équipe de design à sa création au sein de l’entreprise. J’étais surtout UX designer pour des applications métier.

**Tu es également très impliquée dans une association : Designers Éthiques**

Je les ai rejoint en 2019, à un moment où je me posais beaucoup de questions sur mon travail et où j’ai choisi de devenir designer indépendante. J’ai rejoint l’association en même temps qu’Anne Faubry, avec qui nous avons écrit [un guide sur l’éco-conception de services numériques](https://designersethiques.org/fr/thematiques/ecoconception/guide-d-ecoconception).

[Designers Ethiques](https://designersethiques.org/fr) organise des évènements (Ethics By Design et la Journée de l’écoconception), nous proposons des formations pour les designers, et nous faisons de la recherche sur différentes thématiques : l’écoconception, l’accessibilité et l’inclusion, le design systémique, les enjeux de design de l’attention, etc. Autant de sujets qui touchent à la responsabilité des designers dans la conception de services numériques.

</div>
