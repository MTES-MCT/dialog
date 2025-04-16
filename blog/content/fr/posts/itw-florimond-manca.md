---
title: Sous le capot de DiaLog - Entretien avec Florimond Manca
description: Derrière le service numérique, il y a une équipe. Jetez un oeil sous le capot de DiaLog avec Florimond, développeur sur DiaLog.
date: "2025-02-25"
callout:
    title: Devenez utilisateur DiaLog
    description: "Vous êtes sur le blog de DiaLog, solution de numérisation de la réglementation routière propulsée par Beta.gouv du Ministère des transports. Vous représentez une collectivité locale intéressée par DiaLog ? Prenez rendez-vous avec l’équipe directement ici."
    link:
        title: Contacter l'équipe
        url: mailto:dialog@beta.gouv.fr
tags:
  - Interview
  - Tech
---
    
:::callout
Derrière le service numérique, il y a une équipe. Jetez un oeil dans les coulisses de DiaLog avec Florimond, développeur sur DiaLog.
:::

<div class="contenu-article">

DiaLog est une solution numérique qui permet de numériser la réglementation routière et la partager avec des réutilisateurs.

À l’origine de DiaLog il y a un intrapreneur, [Mathieu Fernandez-Vandewalle](https://www.linkedin.com/in/mathieu-fernandez-vandewalle-4a1928142/), rapidement accompagné d’une équipe de prestataires indépendants constituée d’une designer, de trois développeurs et d’un chargé de déploiement. DiaLog est également un projet de [Beta Gouv - incubateur des startups d’État](https://beta.gouv.fr/). Il répond à des critères techniques, éthiques et de conception particuliers.

Prenons le temps d’échanger avec [Florimond Manca](https://www.linkedin.com/in/florimondmanca/) pour comprendre ce qui se cache derrière ce produit. Florimond est membre de la Scop [Fairness](https://www.linkedin.com/company/fairness-coop/posts/?feedView=all), en charge du développement informatique de DiaLog.

{% image "./img/florimond-profile.jpg", "Florimond Manca", [300, 300], "(max-width: 300px) 80vw, 300px", "image-article image-article-profil" %}

**Une startup d’État est créée pour concevoir des produits qui règlent un problème. Comment passe-t-on du problème au produit ?**

Au début de la construction du produit, l’équipe a passé un temps significatif à étudier ce qui s’appelle en ingénierie logicielle le *domaine métier*, c’est-à-dire les aspects du produit qui viennent directement du monde réel : ce qu’est un arrêté, qui le prend, pour quoi faire, à qui ça s’adresse… De ce domaine métier nous avons conçu un modèle de données, c’est-à-dire une représentation abstraite des entités en présence (arrêté, restriction, utilisateurs…) et de leurs relations. Par exemple, comment traduire une circulation alternée, une intersection, ou une période de validité en donnée ? Tout cela paraît évident dans le langage courant quand il s’agit d’imprimer des panneaux de signalisation et briefer des agents municipaux. Mais lorsqu’il s’agit de transmettre ces informations sous une forme compréhensible par des machines, c’est plus complexe. La terminologie, l’architecture des données, les champs... Il faut normer les choses tout en étant capable de déduire le sens des termes utilisés lorsque ça ne l’est pas. C’est aussi tout l’intérêt de DiaLog : éviter la double saisie et permettre aux collectivités de ne quasiment rien changer dans leurs habitudes de travail.

Ensuite il faut également tenir compte des spécificités des réutilisateurs : nous avons défini une manière de présenter par exemple les périodes de validité d’un arrêté, Waze en a une autre, à nous de faire la correspondance pour que cela marche. L’essentiel est que cela soit le plus indolore possible pour les producteurs de données.

En somme, on peut voir DiaLog comme un passe-plat entre bases de données (rires).

**Comment avez-vous abordé le projet DiaLog chez Fairness ?**

D’un point de vue technique, l’architecture est celle d’une application Web métier en PHP / Symfony. Nous y avons appliqué les méthodes d’architecture et de qualité pratiquées chez Fairness, à savoir l’architecture hexagonale couplée aux principes du Domain driven design (DDD), en plus des pratiques qui font office d’état de l’art (tests automatisés, CI/CD, etc). L’équipe se partage aussi bien des tâches côté frontend (interfaces utilisateur) que backend (base de données, logique métier…).

Côté frontend, nous avons fait [le choix particulier](https://github.com/MTES-MCT/dialog/blob/main/docs/adr/003_technical_stack.md) d’une approche dite HTML-first. Cette approche s'inspire des fondamentaux du Web : le rendu des pages Web est effectué côté serveur et JavaScript n’est utilisé que pour ajouter un peu d’interactivité en s’appuyant le plus possible sur les capacités natives des navigateurs modernes. Cela tend à s’opposer à l’approche SPA (Single Page Application), en vogue depuis une quinzaine d’années, qui est associée à des frameworks JavaScript imposants. Ceci nous a semblé judicieux à plusieurs titres : relative simplicité des interfaces de DiaLog ne justifiant pas d’outils conçus pour des interfaces hautement interactives ; réduction de la charge sur les ordinateurs et appareils utilisés par les agents conformément à nos standards d’écoconception ; enfin, réduction de la quantité de code à maintenir pour garantir la pérennité technique de DiaLog.

Un projet comme celui de DiaLog s’insère aussi dans l’écosystème de [la communauté beta.gouv.fr](https://doc.incubateur.net/communaute). L’infrastructure et certains outils sont mutualisés : hébergement, environnement de test, détection de problèmes en production (monitoring), DNS,... (lien vers le GitHub de beta.gouv). Cela nous permet de nous concentrer sur le produit. 

L’intégralité du code que nous produisons pour DiaLog est ouvert, conformément à la politique de l’État en la matière. [Le code est accessible sur la plateforme GitHub](https://github.com/MTES-MCT/dialog) et peut être réutilisé.

**Justement on parle de méthodes agiles pour les projets beta gouv, de quoi s’agit-il concrètement ?**

Les méthodes agiles s’opposent au classique “cycle en V” dans l’industrie. Dans l’approche “cycle en V”, des groupes de travail et experts définissent un cahier des charges et une feuille de route détaillée longtemps à l’avance. Le budget est prédéfini. Une consultation désigne un prestataire qui a la responsabilité d’exécuter le projet dans les délais, budget et spécifications requises parfois plusieurs années auparavant, sans que le produit n’ait été mis à aucun moment entre les mains des utilisateurs. Cette méthode peut se comprendre lorsque l’on fabrique des biens matériels car l’erreur a un coût très élevé. En développement informatique, cela a moins de sens.

L’approche agile essaie au contraire de raccourcir le plus possible la boucle de feedback entre ce qu’on produit et les retours utilisateurs. On construit rapidement un produit simplifié, qui est mis le plus tôt possible entre les mains des premiers utilisateurs. Puis on le modifie en fonction de ce que l’on apprend des besoins et des usages, selon une approche d’amélioration continue. Le code de l’application DiaLog est ainsi mis à jour plusieurs fois par jour.

Il faut prendre garde à la tension entre “aller vite” et “durer” que ces approches peuvent engendrer. Nous faisons régulièrement de courtes réunions pour nous mettre d’accord sur ce qui est le plus intéressant pour l’utilisateur. On cherche à faire bien du premier coup sans développer des prototypes inutiles. Nos produits doivent être économiques, avoir moins besoin de maintenance. Le logiciel doit être durable, et pour cela être plus facile à reprendre par d’autres personnes. Cela passe par la documentation et une architecture de code qui permette de facilement de s’y retrouver.

En termes de méthode de travail, nous faisons chaque jour un daily, c’est-à-dire une petite réunion de 15 minutes pour partager ce qui a été fait, ce qui bloque et ce sur quoi nous allons travailler ce jour-là. Tous les 15 jours également un sprint planning de deux heures permet de passer en revue tous les chantiers en cours du projet. Des décisions sont prises, tout est consigné.

**Intéressons-nous maintenant au produit DiaLog. Il y a deux produits : une interface de saisie manuelle pour les collectivités qui ne disposent pas de leur propre solution de gestion de la documentation, et un produit “intégration” qui permet d’intégrer les données des collectivités directement depuis les outils qu’elles utilisent. Comment fait-on pour créer un produit qui réalise ces tâches d’intégration traditionnellement faites “à la main” ?**

Notre objectif est de proposer un système de flux dans lequel DiaLog récupérerait d’un côté les données auprès du producteur de données pour les mettre à disposition auprès des réutilisateurs au(x) format(s) attendu(s), dans l’idée d’une plateforme qui jouerait l’intermédiaire unique. Sur le papier c’est simple. Dans la réalité c’est beaucoup de “plomberie” (rires). Pour DiaLog, les producteurs de données sont des collectivités qui éditent un arrêté de travaux par exemple qui coupe la circulation dans une rue. Nos réutilisateurs sont par exemple les GPS routiers qui vont utiliser cette donnée pour modifier les itinéraires proposés et ne pas envoyer les voitures ou camions dans cette rue. Le lien entre les deux est fait par un [modèle de données interne](https://github.com/MTES-MCT/dialog/blob/main/docs/adr/002_mcd.md) conçu en s’inspirant des standards européens.

Notre travail est d’automatiser ce transfert de données pour qu’il soit le plus indolore possible pour la collectivité et le plus efficace possible pour les réutilisateurs. En matière de formats de données, nous en avons retenu deux pour l’instant : [DATEX II (European standard for traffic and travel information)](https://trafic-routier.data.cerema.fr/la-norme-europeenne-datex-ii-a58.html) qui est la norme de référence européenne (voir : [Directive STI](https://trafic-routier.data.cerema.fr/la-directive-sti-a113.html)), et [CIFS (closure and incident feed specification)](https://developers.google.com/waze/data-feed/cifs-specification?hl=fr), un format créé et utilisé par Waze. Cela permet aux collectivités d’être assurées que leurs données seront facilement réutilisables par différents types d’acteurs publics et privés, aujourd’hui et demain.

Nous devons faire avec les différents types de solutions déjà utilisées par les collectivités locales pour saisir et stocker leur réglementation. Des contacts ont été établis avec la plupart des éditeurs sur le marché : Sogelink, Kadri, Berger-Levrault… afin d’examiner avec eux les meilleures manières d’exporter les données des collectivités depuis leurs solutions. Cela passe généralement par des APIs (interfaces de programmation) conçues justement pour pouvoir faire des requêtes “de machine à machine” dans les bases de données des collectivités. Par exemple, depuis août 2024 DiaLog utilise l’API fournie par l’éditeur Sogelink pour récupérer les données d’arrêtés de circulation auprès de la Métropole Européenne de Lille (MEL).

La mise en place d’un tel dispositif nécessite une étape d’expérimentation avec l’API fournie par le producteur de données : vérifier la validité des identifiants, l’allure des données récupérables, et leur compatibilité avec le modèle de données interne à DiaLog. Quand tout fonctionne bien cette connexion prend quelques minutes. Nous proposons alors un rapport d’intégration à la collectivité qui lui permet de connaître, parmis tous les arrêtés dans la base, ceux qui sont compatibles avec les GPS pour permettre leur export. Une fois la connexion établie de l’autre côté avec les GPS, le flux est presque continu. Waze par exemple vient interroger la base de DiaLog toutes les 5 minutes.

**DiaLog n’est pas la seule solution à traiter de la donnée géographique. Comment t’assures-tu de sa compatibilité avec l’ensemble de l’écosystème numérique public ?**

Lorsque l’on crée un produit technique comme DiaLog, heureusement on ne part pas de zéro. Nous nous appuyons sur un certain nombre de référentiels et de services existants. Par exemple, la [BD TOPO de l’IGN](https://geoservices.ign.fr/bdtopo) nous est indispensable puisqu’elle fournit les linéaires de routes, rues, chemins de l’ensemble du territoire. Pour connaître les adresses des bâtiments ou propriétés il y a la [Base Adresse Nationale (BAN)](https://adresse.data.gouv.fr/). DiaLog n’existerait pas sans ces solutions. Ces sources de données géographiques s’insèrent dans l’architecture technique de DiaLog d’une manière totalement transparente pour l’utilisateur. Par exemple, lorsqu’un arrêté définit une interdiction de circuler entre les numéros 70 et 76 rue Ange Blaize à Rennes, l’emplacement géographique de ces deux adresses est calculé à l’aide de la BAN, puis le tronçon de linéaire concerné est extrait de la BD TOPO.

Au niveau plus technique, nous utilisons des solutions standard et connues dans le métier, notamment la base de données [PostgreSQL](https://www.postgresql.org/) et sa surcouche [PostGIS](https://postgis.net/) qui fournit les outils pour traiter les données géographiques.

Finalement, notre travail en tant que développeur ou développeuse consiste beaucoup à assembler différentes “briques” pour répondre au besoin de la manière la plus fiable et efficiente possible.

**Parlons pour finir de toi. Comment es-tu arrivé à ce métier ?**

Lors de mon cursus  en école d’ingénieur généraliste, j’ai pu effectuer divers stages longs qui m’ont convaincu de me spécialiser en ingénierie logicielle. Au gré des expériences, cela m’a amené au développement web, au monde de l’open source et au développement d’une expertise dans le langage Python. DiaLog est mon premier projet en PHP / Symfony mais j’ai rapidement su prendre mes marques !

Je n’ai pas de spécialisation particulière en géo-intelligence. Ce qui m’a intéressé avec DiaLog, c’est le lien avec le changement climatique, les infrastructures publiques et le fait de travailler pour le Ministère de la Transition Écologique. J’aime travailler sur des solutions qui règlent les problèmes quotidiens pour les gens. Contrairement à certaines de mes missions précédentes j’arrive à expliquer en quoi consiste DiaLog et en quoi cela pourrait changer leur quotidien (rires).

C’est aussi notre engagement chez Fairness : promouvoir un numérique au service de l’intérêt général, qui ne cherche pas à extraire ou rendre captif mais à servir l’humain. Nous portons haut les engagements d’éco-conception et d’accessibilité de nos solutions. Cela colle avec les valeurs de BetaGouv.

Nous sommes DiaLog, la solution de numérisation de la réglementation routière. Vous représentez une collectivité locale ou un acteur directement intéressé par DiaLog ? Prenez rendez-vous avec l’équipe [directement ici.](https://cal.com/team/dialog/prise-de-contact-30-mn?layout=mobile&date=2024-11-21&month=2024-11)

</div>
