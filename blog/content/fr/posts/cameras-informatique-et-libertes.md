---
title: Caméras, Informatique et Libertés
description: Entretien avec Régis Chatellier, responsable d’études prospectives à la CNIL - Commission Nationale de l'Informatique et des Libertés.

date: "2025-10-28"
callout:
    title: Devenez utilisateur DiaLog
    description: "Vous êtes sur le blog de DiaLog, solution de numérisation de la réglementation routière propulsée par Beta.gouv du Ministère des transports. Vous représentez une collectivité locale intéressée par DiaLog ? Prenez rendez-vous avec l’équipe directement ici."
    link:
        title: Contacter l'équipe
        url: mailto:dialog@beta.gouv.fr
tags:
  - caméras
  - souveraineté
  - streetview
  - cnil
---

:::callout
Entretien avec Régis Chatellier, responsable d’études prospectives à la CNIL - Commission Nationale de l'Informatique et des Libertés.
:::

<div class="contenu-article">

{% image "./img/illustration-pawel-czerwinsk.jpg", "photo Pawel Czerwinski via Unsplash", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

**Qui capte les données aujourd’hui dans l’espace public ?**

Depuis les boucles dans le sol associées aux systèmes de feu jusqu’aux caméras de vidéosurveillance, cela fait plus de 50 ans que la ville capte des données. Ce qui est nouveau est l’usage d’objets connectés, géolocalisés et mouvants comme le smartphone. Beaucoup d’acteurs publics et privés aujourd’hui captent de la donnée dans l’espace public via des caméras : collecte des déchets, transporteurs, boutiques et de plus en plus les particuliers eux-mêmes. Les cadres juridiques applicables sont différents.

**Est-ce que les enjeux sont différents selon les acteurs et les lieux de captation ?**

Le [Réglement général sur la protection des données](https://www.cnil.fr/fr/reglement-europeen-protection-donnees) s’applique de manière générale à la captation et au traitement des données personnelles, hors usages domestiques et sécurité publique. Cette dernière fait l’objet de textes spécifiques qui définissent précisément les cas d’usage autorisés (voir plus loin).

Le RGPD dispose que la collecte des données doit s’appuyer sur une base légale  (intérêt public, contrat, intérêt légitime, consentement, etc.). Ces données doivent être associées à un usage, une finalité, et être minimisées. L’usage d’une balance connectée par exemple ne doit pas nécessiter de créer un compte, pas plus que l’achat d’un billet de train ne doit imposer à l’acheteur de renseigner son genre. Comme il n’y a pas de tarifs liés à Monsieur ou Madame, la collecte de ces données ne se justifie pas. Idem si j’ai besoin de savoir si une personne a 18 ans, je n’ai pas besoin de collecter son adresse, ni même sa date de naissance. Les données ne doivent pas non plus être collectées et traitées pour d’autres finalités que ce qui a été annoncé initialement.

Le cas d’applications comme Waze, qui capte en temps réel les données de géolocalisation de leur utilisateur, est intéressant de ce point de vue. La captation de ces données repose sur le contrat.  dès lors que le service rendu par Waze nécessite de connaître la position exacte de l’utilisateur pour fonctionner. Le fonctionnement de Waze en dépend, c'est pourquoi il ne s’agit pas d’un consentement. On doit pouvoir refuser un consentement de manière libre, sans renoncer au service lui-même, par exemple si nous  refusons la publicité ciblée, ou le partage de données à des tiers. Ici, la donnée de géolocalisation conditionne l’accès au service, elle est donc associée au contrat.

Le RPGD “va dire” aux personnes et aux entreprises : pas de problème à collecter de la donnée , à condition de respecter le cadre et de ne pas en collecter plus que nécessaire. Le RGPD répond à une conception anglo-saxonne du droit : le droit est “embarqué” par l’utilisateur des données, le responsable de traitement, qui doit être en capacité de démontrer qu’il respecte ces droits dans les conditions requises par le RGPD. Il n'y a plus de déclaration préalable comme avant 2018, de même, les demandes d’autorisations se limitent à quelques cas spécifiques en santé.

**Comment s’assurer que les données diffusées ne soient pas des données personnelles ?**

La captation de données dans l’espace public et plus encore leur diffusion ne doit pas conduire à permettre d’identifier les personnes. Pour Google Street View, par exemple, la CNIL a très tôt demandé à ce qu’on anonymise tout ce qui permet d’identifier directement les personnes : plaques d’immatriculation, visages,...
Dans le cas du programme [Waze for Cities](https://www.waze.com/fr/wazeforcities/) par lequel les collectivités récupèrent des données d’usage de la voirie collectées par l’application, la donnée est anonymisée par agrégation pour qu’on ne puisse pas isoler des déplacements individuels. La ville a accès à des données de flux mais ne peut pas, ne doit pas,  savoir qui circule. L’anonymisation des données est un champ de recherche en soi. Il existe plusieurs manières de le faire comme la suppression de certaines données (si par exemple vous êtes le seul utilisateur d’une voirie), l’introduction de “bruit” dans la donnée,.... C’est un processus dynamique : il doit toujours être réalisé à l’état de l’art car les techniques d’identification progressent.

**Quelles sont les données les plus sensibles ?**

La donnée dès qu’elle est géolocalisée est extrêmement sensible. Par exemple , si vous avez accès à la trace GPS d’une personne dans ses déplacements quotidiens, vous pourrez aisément remonter à celle-ci. Car il est rare que deux personnes ayant le même lieu de résidence aient également le même lieu de travail. [Nous l’avons démontré dans un projet mené par le LINC en 2022-2023](https://linc.cnil.fr/geotrouvetous-projet-de-reidentification-par-geolocalisation). De même, [un chercheur a démontré dès 2015, à partir de l'étude des données de cartes bancaires](https://linc.cnil.fr/nouvelles-frontieres-des-donnees-personnelles), produites sur trois mois par 1,1 million de personnes, que seuls quatre points "spatio-temporels" (coordonnées géographiques, date et heure) suffisent pour retrouver l'identité de 90% des individus.

**Les règles sont-elles les mêmes pour la vidéo dans l’espace public ?**

Dans l’espace privé, pour la protection de bâtiments ou de certains lieux fréquentés par le public, il est possible d’installer  une caméra pour protéger ce bâtiment, mais [celle-ci ne peut être utilisée pour surveiller les personnes, par exemple  les salariés](https://www.cnil.fr/fr/la-videosurveillance-videoprotection-au-travail).

[Dans l’espace public, les usages de la vidéo sont encadrés par le Code de la Sécurité Intérieure](https://www.cnil.fr/fr/cameras-dans-lespace-public). Ce qui n’est pas expressément autorisé est interdit. Les images collectées sont accessibles uniquement aux personnes habilitées. Elles sont déclarées dans un cadre très normé qui prévoit tous les cas d’usage. Par exemple, elles peuvent dans certains cas servir à la [vidéoverbalisation](https://www.cnil.fr/fr/videoverbalisation) pour sanctionner des infractions au code de la route et des dépôts d’ordures sauvages par exemple. Ces caméras ne peuvent en aucun cas capter le son.

Dans le cadre de la loi pour les Jeux Olympiques de Paris 2024, les caméras augmentées, ou algorithmiques, sont autorisées sous forme d’expérimentation, pour certains cas d’usage. Le traitement des images collectées  doit permettre d’identifier non pas des personnes mais des évènements, comme des bagages abandonnés ou des mouvements de foule, dans un contexte restreint ([voir lien](https://www.cnil.fr/fr/jop-2024-les-questions-reponses-de-la-cnil#:~:text=La%20loi%20relative%20aux%20Jeux,la%20billetterie%20pour%20les%20spectateurs.)). La reconnaissance faciale (identification d’une personne précise à partir d’une image captée dans l’espace public) est interdite en revanche.

**Au-delà de la vidéosurveillance, quelles sont les nouvelles formes de captation dans l’espace public ?**

D’autres types de caméras sont en effet utilisées par la Police ou la Gendarmerie, par exemple les caméras-piétons. Le cadre juridique précise que celles-ci doivent être visibles, que les personnes doivent savoir quand elles enregistrent. Elles ne doivent pas tourner en permanence. Elles  sont soumises à déclaration pour pouvoir tracer leur usage. Il existe des droits associés aux personnes : si l’on ne peut pas s’opposer à ce qu’on soit filmé, on doit en être informé et avoir la possibilité d’exercer ses droits, comme le droit d’accès. La durée de conservation de ces données est par défaut d' un mois.

Sont apparues également les “systèmes LAPI”, des caméras de [Lecture Automatisée de Plaques d’Immatriculation](https://www.cnil.fr/fr/les-dispositifs-de-lecture-automatisee-de-plaque-dimmatriculation-lapi) : leur usage est encadré par le Code de la Sécurité Intérieure : uniquement à des fins de constatation d’infraction et de respect du code de la route. Des questions nouvelles se posent avec les usages de caméras à lecture de plaque dans d’autres contextes : parkings barriérés, nouveaux péages  automatiques.  Avec des questions qui se posent en termes d’information des personnes, notamment.

Pour les caméras embarquées dans les voitures ou “dashcams”, [la CNIL a lancé une consultation](https://www.cnil.fr/fr/club-conformite-vehicules-connectes-programme-de-travail-2025-cnil) auprès des parties prenantes afin de répondre  aux nouvelles questions juridiques posées par ces dispositifs.
Pour tout ce qui est l’enregistrement de l’habitacle dans des véhicules personnels, il s’agit d’un usage domestique. Dans d’autres cas, il s’agit de la relation contractuelle avec le loueur ou le constructeur du véhicule : ceux-ci doivent annoncer et expliquer ce qu’ils font des données collectées. Idem lorsque des caméras embarquées filment autour de la voiture. La CNIL reçoit également des appels et des plaintes à propos de personnes qui installent des caméras chez elles, [qui filment vers l’extérieur l’espace public et chez les voisins](https://www.cnil.fr/fr/la-videosurveillance-videoprotection-chez-soi).

Enfin, la question des images aériennes de grande précision. Dans certains cas, ces images sont utilisées pour identifier des éléments, par exemple dans le cadre de l’expérimentation menée par la DGFIP pour la détection automatisée de piscines non déclarées. Un usage qui avait nécessité un encadrement juridique spécifique.

**Comment intervient la CNIL sur ces questions ?**

Le sujet des données étant sensible, on nous demande fréquemment à propos d’un projet , du secteur privé comme du public : “qu’en pense la CNIL ?” La CNIL ne pense rien avant de connaître le projet, ses contours, la manière dont il fonctionne. Il s’agit de connaître quel et le montage en termes juridiques, si cela s’inscrit dans un cadre applicable, et technologique : quelle architecture des systèmes, quels stockage pour les données, quels moyens pour les sécuriser, quel est leur cycle de vie, etc. et surtout, comment à chaque phase, les droits des personnes sont respectés, ou non. Ceci vaut pour les caméras, mais aussi pour tous les systèmes que nous rencontrons, y compris lorsqu’ils sont basés sur de l’IA.
La CNIL agit dans l’accompagnement en échangeant avec des porteurs de projets ou parties prenantes, par la production de recommandations spécifiques à certains secteurs.

Les administrations viennent aussi nous voir en amont, avant que le projet de loi  ne soit examiné pour étudier avec nous quels sont les enjeux spécifiques. Par exemple, pour la Loi JO, la CNIL a pointé le fait que l’expérimentation prévue ne pouvait consister en une mise en pré-production, mais que celle-ci devait faire l’objet d’une vraie évaluation, avec la production d’un rapport d'expérimentation, porté par des experts indépendants.

La CNIL est une autorité administrative indépendante, son budget et ses effectifs sont votés au parlement, ses missions inscrites dans la loi, mais ensuite elle agit de manière autonome sans autorité de tutelle et en indépendance de fonctionnement. La CNIL ne  produit pas de droit “dur” : elle interprète le droit et la loi voté en France ou en Europe. Elle peut produire des recommandations (droit “souple”), qui correspondent à l’application de la loi. La CNIL agit sur toute la chaîne, de la production d’avis sur des textes de loi, jusqu’à la réception d’appels de toute personne ayant des questions sur ses droits. Elle peut également intervenir dans le débat public comme elle l’a fait en 2019, pour alerter la société et les élus sur la nécessité de débattre de manière éclairée sur les risques et les lignes rouges à tracer sur l’usage de certaines technologies, [à l’image de la reconnaissance faciale.](https://www.cnil.fr/fr/reconnaissance-faciale-pour-un-debat-la-hauteur-des-enjeux)

**RESSOURCES UTILES**

- Limites numériques : [ballade qui révèle les équipements et l’infrastructure](https://limitesnumeriques.fr/sensibiliser/animation-numerique-responsable/balade-infra)
- Autres ressources de [Limites Numériques](https://gauthierroussilhe.com/ressources)
- [Mesurer l’anonymat de nos données sur le web grâce à un logiciel UCLouvain révolutionnaire](https://www.uclouvain.be/fr/presse/news/mesurer-l-anonymat-de-nos-donnees-sur-le-web-grace-a-un-logiciel-uclouvain-revolutionnaire)
- L’article scientifique dans [Nature](https://www.nature.com/articles/s41467-024-55296-6)
- [Des premières caméras à l’expérimentation des algorithmes : un panorama du développement territorial, technologique et de l’encadrement juridique de la vidéosurveillance](https://droit.cairn.info/revue-francaise-d-administration-publique-2024-1-page-223?lang=fr)
- [Rapport du comité d’évaluation de la vidéosurveillance algorithmique](https://www.interieur.gouv.fr/actualites/actualites-du-ministere/experimentation-en-temps-reel-de-cameras-augmentees)
- [Air2024 - colloque sur les usages de la (vidéo)surveillance dans la sphère privée - vidéo](https://www.cnil.fr/fr/rediffusion-air2024-retrouvez-levenement-en-video)

**Propos recueillis par Stéphane Schultz, coach produit DiaLog.**
</div>
