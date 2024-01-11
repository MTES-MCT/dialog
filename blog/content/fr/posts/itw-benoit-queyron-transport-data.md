---
title: Ouvrir les données de transport - Interview de Benoît Queyron
description: Intrapreneur de transport.data.gouv, Benoît nous a présenté le PAN et a partagé avec nous sa vision de l'open data et du service public numérique. 
date: "2024-01-11"
callout:
    title: Devenez utilisateur DiaLog
    description: "Vous travaillez pour une collectivité et souhaitez expérimenter DiaLog ? Vous souhaitez pouvoir utiliser les données DiaLog pour vos besoins opérationnels ou dans un service numérique tiers ? Envoyez-nous un mail et nous vous recontacterons au plus vite."
    link:
        title: Contacter l'équipe
        url: mailto:dialog@beta.gouv.fr
tags:
  - Interview
  - Open data
  - Réglementation
---
    
:::callout
[Transport.data.gouv](https://transport.data.gouv.fr/) est également connu sous le nom de PAN, pour "Point d'Accès National" (aux données de transport). Benoît Queyron, chef de projet services numériques de mobilité à la DGITM, est depuis 2021 à la tête de l'équipe qui vous permet d'avoir accès aux horaires de transport, aux localisations des aires de covoiturage ou de bornes de rechargement dans vos applications smartphone préférées. Pour en arriver là, il aura fallu définir des priorités, connaître sa cible et savoir bien s'entourer : il nous partage la recette de ce succès dans notre premier billet de blog de 2024 !
:::

<div class="contenu-article">

**D’où vient l’idée de lancer transport.data ?**

Transport.data.gouv.fr est le Point d’Accès National (PAN) aux données de transport en France, l’existence de ce service relève d’une obligation juridique européenne. Ce type d’infrastructure numérique existe dans tous les états d’Europe à des stades de développement différents, mais pour tous les Etats la logique est la même : centraliser l’accès à la donnée de transport, proposer les données de l’offre de transport tous modes et toutes infrastructures. 

On ne s’étend donc pas sur le champ de la demande, la fréquentation ou la consommation électrique des bornes de recharge : Le PAN se concentre sur les horaires, les tarifs, les perturbations, les infrastructures comme les aménagements cyclables, stationnement vélo, aires de covoiturage, etc.

{% image "./img/Abribus-Angouleme.jpg", "Abribus place de la gare à Angoulême © Arnaud Bouissou / Terra", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

<div class="legende-article">Abribus pace de la gare à Angoulême © Arnaud Bouissou / Terra</div>

**Quelles sont vos priorités en matière de modes de déplacement ?**

Nous ne sommes pas encore exhaustifs et avons une stratégie de priorisation. Par exemple nous avons 98% des réseaux de transport français pour les horaires statiques, pour le temps réel et les perturbations nous en sommes à la moitié. 100% des aires de covoiturage sont disponibles. Pour le stationnement nous avons essentiellement la donnée sur les parc-relais.

Transport.data reste concentré sur les données de mobilités durables. Le stationnement des voitures particulières ou le transport aérien ne sont pas notre priorité.

**Quelles sont les cibles visées par votre service ?**

Nous voulions faciliter l’utilisation des données de déplacement par des calculateurs d’itinéraires. D’autres usages sont possibles, mais la cible prioritaire ce sont les solutions de navigation et celles et ceux qui les fabriquent : Maps, Hove, Cityway, Bing, Moovit, Transit, …

Depuis 2017 nous avons poursuivi un objectif simple : mettre les horaires de bus de toutes les autorités organisatrices des mobilités (les villes et métropoles responsables de l’organisation des transports) sur le PAN, sans regarder les licences ou la qualité des données. Et aussi constituer un “CRM” de l’ensemble des acteurs du transport, car nulle part n’existait un simple annuaire permettant de joindre tous les acteurs de la donnée locale de transport. Maintenant c’est fait.

Nous avons développé des solutions et des process pragmatiques pour récupérer les données auprès des producteurs : 
-   soit l’AO nous transmet directement son jeu de données par transfert ou via son API (interface de programmation applicative)
-   soit elle a déjà son propre portail open data et dans ce cas nous allons “moissonner” leurs données sans qu’ils aient à intervenir.

Par la suite nous avons commencé à standardiser nos process pour passer à l’échelle. De méthodes manuelles nous sommes passés à des outils plus automatisés entre les mains des producteurs de données.

Pour les données statiques nous mettons à disposition un fichier de données classique, tandis que pour les données temps réel nous proposons un service de proxy aux collectivités : on met en cache leurs données et on redistribue ainsi à 40, 80, 100 utilisateurs. Cette méthode rassure les collectivités en particulier pour les données temps réel. 

Cela représente pour nous un coût modique en terme de serveurs. Cf. les statistiques détaillées du PAN : https://transport.data.gouv.fr/stats

**En quoi transport.data est différente des autres startups d’État ?**

Nous appliquons les mêmes méthodes que toutes les startups d’État. J’ai beaucoup appris des enseignements de beta.gouv (l’organisation en charge des startups d’État) notamment sur la conduite d’un projet numérique. Ce ne sont pas des choses que l’on apprend à l’École des Travaux Publics !
Le projet transport.data suit donc de près les axes communs à l’ensemble des services numériques développés dans la communauté beta :  
Chaque décision est justifiée par le besoin utilisateur, nous passons beaucoup de temps à étudier et comprendre ce besoin pour ne dépenser que le minimum d’énergie à valider ce que nous pressentons comme correct. Autre aspect important : nous fonctionnons en équipe de manière transverse et pluridisciplinaire, cela permet à chacun de bénéficier de points de vue différents sur la problématique à traiter.

Sinon, tous les produits de beta.gouv sont différents. Transport.data a la particularité de mettre à disposition de la matière brute, formatée à destination d’une cible “calculateur d’itinéraires”. Là où d’autres produits mettront l’accent sur la mise à disposition d’un ensemble de services, nous préférons rester sur des formats simples en garantissant cependant la disponibilité et la qualité des données.

De ce fait, le principal aspect serviciel de transport.data est dans l’accompagnement des collectivités territoriales dans l’ouverture des données et l’industrialisation de leur mise en qualité. Pour cela nous avons créé des outils (voir menu “outils”) pour vérifier et valider ses données.

Aujourd’hui la startup est dans sa phase de “run” : sa phase de fonctionnement. L’accélération est achevée, on est dans la phase d’industrialisation. Il y a toujours des investissements et du développement, mais dans l’ensemble le produit est mûr.


**Qui sont les utilisateurs de transport.data ?**

Techniquement on parle plutôt de « réutilisateurs » des données puisqu’il s’agit d’entreprises qui vont télécharger les données du PAN pour les utiliser dans leurs applications d’aide à la navigation par exemple.

L’une des spécificités est que chez transport.data nous considérons que l’open data ne doit pas imposer aux utilisateurs de s’authentifier et encore moins d’indiquer l’usage qu’il va faire des données qu’il télécharge. Si tu habites à Strasbourg et veux un GTFS à Avignon tu peux le télécharger sans créer de compte. C’est ça, pour nous, l’« open » dans open data. La contrepartie, c’est que nous ne connaissons pas toujours ces réutilisateurs…

On ne les connaît pas tous mais on en connaît beaucoup : nous discutons avec 40 réguliers du PAN au moins une fois par semaine. Google Maps est le plus gros réutilisateur, il absorbe quasiment tous les jeux de données et les expose. C’est l’esprit et la lettre de l’article 122 de loi Climat et Résilience : permettre aux calculateurs d’itinéraires d’accéder aux données de transport public, et par là même permettre à des réseaux de taille et moyens à modestes d’être “exposés” sur des solutions grand public comme Google ou Moovit.

Ces réutilisateurs « massifs » sont donc essentiels pour nous. Nous avons d’ailleurs (c’est en projet) récemment lancé des ateliers pour développer un espace utilisateur sur le PAN, soumis à authentification. L’accès aux données restera ouvert à tous sans authentification mais l’espace utilisateurs offrira des services utiles pour un profil de gros utilisateurs identifiés : des notifications de mise à jour, des suppressions, des rapports de validation.

Le principe à retenir, c’est que la Loi d’Orientation sur les Mobilités (article 25) cible d’une part les autorités organisatrices en disant : “ouvrez vos données” et d’autre part les réutilisateurs comme les solutions de navigation en disant : “utilisez le PAN”. 

Aujourd’hui par exemple 120 réseaux de transport public sont exposés sur Google Maps.

{% image "./img/Traffic-Aubrais.jpg", "Affichage du trafic dans le poste d'aiguillage de la gare des Aubrais  © Arnaud Bouissou / Terra", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

<div class="legende-article">Affichage du trafic dans le poste d'aiguillage de la gare des Aubrais © Arnaud Bouissou / Terra</div>


**Quels sont les liens entre DiaLog et transport.data ?**

Tout part d’un amendement au Parlement devenu ensuite [l’article 122](https://www.legifrance.gouv.fr/jorf/article_jo/JORFARTI000043957195) sur la réglementation de circulation des Poids Lourds. En élaborant le décret d’application pour son dernier alinéa s’est posée la question du moyen d’action pour le mettre en œuvre. Cela demandait un métier semblable à ce que faisait déjà le PAN : récolter de la donnée 

> Rappel article L.122 al.4 : les services numériques d'assistance au déplacement sont tenus d'informer de façon complète les utilisateurs (...)  des mesures de restriction de circulation visant les poids lourds prises par les autorités de police de la circulation en application de l'article L. 2213-1 du même code ou de l'article L. 411-8 du code de la route et concernant les itinéraires proposés, dans le cas des services numériques d'assistance au déplacement spécifiques aux véhicules lourds”.

Au sein de l’équipe transport.data nous nous sommes demandés si nous pouvions à 6 personnes créer une solution susceptible de récolter la donnée concernée par l’article L.122 alinea 4. et l’intégrer dans nos process.

L’objectif était bien de disposer d’une brique supplémentaire sur le PAN mais pas de créer une nouvelle équipe au sein de transport.data. Ce qui a conduit à la création de la startup d’État DiaLog. Les premiers jeux de données DiaLog sont d’ailleurs référencés depuis la semaine dernière dans les données routières du PAN. 

Plus tard nous aurons ainsi une base de données par rue qui fournira les horaires de transport théoriques et en temps réel ainsi que la réglementation routière applicable, le tout mis à disposition via le point d’accès national !

</div>
