---
title: Accordéon
description: Comment intégrer un accordéon dans une page du site ?
date: 2023-09-10
tags:
  - DSFR
  - composant
---
Chaque composant peut être inclus dans un fichier Nunjucks `.njk` ou Markdown `.md`.

## Exemple d'utilisation
Comment les GPS peuvent-ils contribuer efficacement à la lutte contre le déréglement climatique ?


L’article 122 de la Loi Climat et Résilience créé une série d’obligations nouvelles pour les “services numériques d’assistance au déplacement (les solutions d’aide à la navigation routière comme TomTom, Here, Waze…) dans le but de les faire mieux contribuer à la réduction de l’empreinte carbone des déplacements de voyageurs et de marchandises.


Commençons par le texte en question :

« Art. L. 1115-8-1.-Selon des modalités définies par décret, les services numériques d'assistance au déplacement sont tenus d'informer de façon complète les utilisateurs des impacts environnementaux de leurs déplacements. En particulier, ces services :
« 1° Indiquent, le cas échéant, la présence et les caractéristiques des mesures de restriction de circulation en vigueur dans les zones à faibles émissions mobilité prévues à l'article L. 2213-4-1 du code général des collectivités territoriales ;
« 2° Ne favorisent exclusivement ni l'utilisation du véhicule individuel, ni l'usage massif de voies secondaires non prévues pour un transit intensif ;
« 3° Proposent aux utilisateurs un classement des itinéraires suggérés en fonction de leur impact environnemental, notamment en termes d'émissions de gaz à effet de serre ;
« 4° Informent les utilisateurs des mesures de restriction de circulation visant les poids lourds prises par les autorités de police de la circulation en application de l'article L. 2213-1 du même code ou de l'article L. 411-8 du code de la route et concernant les itinéraires proposés, dans le cas des services numériques d'assistance au déplacement spécifiques aux véhicules lourds.
« Les services numériques mentionnés au premier alinéa du présent article sont ceux qui visent à faciliter les déplacements monomodaux ou multimodaux au moyen de services de transport, de véhicules, de cycles, d'engins personnels de déplacement ou à pied. »
Mathieu Fernandez, vous êtes intrapreneur au sein du Ministère de la Transition Écologique, en charge du développement de la startup d’État DiaLog. Avant de nous parler de DiaLog, pouvez-vous nous raconter pourquoi l’État a souhaité encourager les solutions de navigation à mieux favoriser les déplacements les moins carbonés ?

À la suite du mouvement des gilets jaunes, le Grand Débat National a permis d’affirmer une nouvelle fois l’urgence de freiner notre contribution au déréglement climatique. La Convention Citoyenne pour le Climat a ensuite formalisé une série de mesures répondant à cette obligation. La pandémie de covid-19 a elle démontré la fragilité de notre système face aux crises de grande ampleur. La loi Climat et Résilience vise à répondre à ces fortes tensions en agissant sur de nombreux secteurs :  la consommation, la production et le travail, les mobilités, l’aménagement ou l’alimentation.

Le secteur des mobilités est le premier secteur émetteur de gaz à effet de serre en France, et les mobilités routières y sont responsables de la grande majorité (+90%) des émissions. Ces dernières sont donc un sujet prioritaire de toute politique publique visant à freiner notre contribution au réchauffement climatique. La circulation routière est également d’une grande importance quand on parle de résilience. 9 tonnes de marchandises sur 10 transitent par la route en France, une circulation très intensive qui nécessite

Quelles sont les contraintes et obligations que l’article 122 introduit pour les solutions de navigation ? Dans quel objectif ?
Je vous renvoie aux détails de chaque terme utilisé dans l’article, mais en résumé disons qu’il demande à ce solutions de mieux informer les utilisateurs de la route sur les ZFE, de veiller à ne pas favoriser exclusivement certains véhicules et certains itinéraires inadaptés, et de proposer des itinéraires ayant un moindre impact en terme d’émission de gaz à effet de serre.
Il me semble qu’il y a également un paragraphe spécifique sur la réglementation poids lourds, c’est le créneau choisi par DiaLog pour ses premiers cas d’usage non ?
L’article 122 dispose que les solutions de navigation “ Informent les utilisateurs des mesures de restriction de circulation visant les poids lourds prises par les autorités de police de la circulation en application de l'article L. 2213-1 du même code ou de l'article L. 411-8 du code de la route et concernant les itinéraires proposés, dans le cas des services numériques d'assistance au déplacement spécifiques aux véhicules lourds.”
Dans la phase d’investigation de DiaLog, nous avons choisi de nous concentrer sur l’application de la réglementation poids lourds dans certaines villes, en prenant le cas particulier des arrêtés de travaux ayant un impact sur la circulation. Il faut garder à l’esprit qu’une agglomération comme Rennes va ouvrir et fermer plusieurs milliers de “chantiers” par an. Pouvoir communiquer efficacement vers les utilisateurs de la route, en premier lieu desquels les poids lourds, est crucial. Cela permet d’éviter des désagréments (camion bloqué, obligé de faire demi-tour) mais permet également de réduire les risques (camion empruntant une voirie ou une infrastructure inadaptée) et améliorer les conditions de travail des conducteurs. Lors de la phase d’investigation de DiaLog, nous avons constaté une grande convergence de vue des différents acteurs - services municipaux, transporteurs, chargeurs - sur l’utilité d’améliorer la connaissance des périodes, secteurs et impacts des travaux.
Concrètement, qu’est-ce que DiaLog va apporter ?
Nous avons essayé d’inclure DiaLog de la manière la plus fluide possible dans le parcours qu’emprunte la réglementation. Je m’explique : aujourd’hui la réglementation est produite par les communes ou regroupement de communes en prévision de travaux impliquant au moins une entreprise. Lorsque ces travaux ont un impact sur la circulation sur l’espace public, un arrêté est nécessaire pour en modifier temporairement ou définitivement les règles d’usage. Après avoir défini avec les services concernés les impacts qu’auront ces travaux, l’arrêté explicite ces impacts : type de modification, durée, localisation, type d’usage ou d’usagers concernés. Cet arrêté doit ensuite être publié pour avoir force exécutoire. Il va générer notamment une information temporaire ou permanente sous forme de panneaux, et éventuellement communication directe sur site et dans des médias.
Et c’est là que DiaLog a l’ambition d’aider les collectivités, en permettant aux collectivités de diffuser les arrêtés qui ont un impact sur la circulation auprès des services numériques d’assistance au déplacement afin que ceux-ci les prennent en compte dans le calcul et la présentation des itinéraires et des cartes qu’ils diffusent à leurs utilisateurs.


 Quels problèmes cela pose pour remplir cette obligation ? Détailler enjeux et ambitions de DiaLog. En quoi DiaLog va aider cela ?


CTA pour les lecteurs / auditeurs
