---
title: Pas besoin de GPS sous la terre
description: Vous êtes-vous déjà demandé comment vos applications de navigation préférées pouvaient continuer à fonctionner sous un tunnel ou dans le métro ?
date: "2024-11-22"
callout:
    title: Devenez utilisateur DiaLog
    description: "Vous êtes sur le blog de DiaLog, solution de numérisation de la réglementation routière propulsée par Beta.gouv du Ministère des transports. Vous représentez une collectivité locale intéressée par DiaLog ? Prenez rendez-vous avec l’équipe directement ici."
    link:
        title: Contacter l'équipe
        url: mailto:dialog@beta.gouv.fr
tags:
  - Tech
  - GPS
---
    
:::callout
Vous êtes-vous déjà demandé comment vos applications de navigation préférées pouvaient continuer à fonctionner sous un tunnel ou dans le métro ?
:::

<div class="contenu-article">

{% image "./img/smartphone-gps.jpg", "GPS smartphone © Maël Balland, Unsplash", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

Vous séchez ? Ne vous inquiétez pas, l’équipe de [DiaLog, solution de numérisation de la réglementation routière](https://www.dialog.beta.gouv.fr/), est là pour vous expliquer les dessous des apps de navigation. Commençons par celui sans qui rien ne serait possible : le smartphone.

On s’est habitué à le voir partout : dans nos mains, sur les tables et même dans nos lits. Un utilisateur moyen le toucherait chaque jour plus de 2000 fois. Cette brique de 15 cm de diagonale cache encore pourtant quelques secrets. Nous savons déjà que le smartphone sert à appeler (parfois), envoyer et recevoir de messages, jouer et consulter des contenus. Mais l’essentiel est invisible pour nos yeux : ce sont les capteurs embarqués, de 10 à 20 selon les modèles, qui connectent littéralement l’appareil avec l’extérieur et avec nous-même. 

**Combien de capteurs embarqués dans votre téléphone pouvez-vous citer ?**

Vous connaissez évidemment  les caméras, le micro, l’écran tactile, le GPS, le capteur d’empreinte digitale et… ? Saviez-vous par exemple que la plupart des smartphones embarquent aussi un baromètre (pression atmosphérique), un magnétomètre (comme une boussole), un gyroscope, un accéléromètre et un capteur de mouvements ? Sans oublier pour les appareils derniers cris un LiDAR, un capteur Time of Flight pour la distance des objets visés et un détecteur infra-rouge pour la reconnaissance faciale. 

Même s’il reste discret, votre smartphone sait et “sent” énormément de choses.

À celles et ceux qui envisagent après avoir lu ces lignes de retrouver le vieux 3310 dans le tiroir des parents, sachez juste que la plupart des données collectées le sont dans un “circuit fermé”. Elles ne “sortent” pas du téléphone et ont pour but principal d’améliorer l’expérience utilisateur. Si par exemple votre écran tactile ne s’éteignait pas automatiquement quand vous le collez à votre oreille, vos conversations commenceraient sans doute par un juron. Idem pour la connectivité au Wifi et au GPS qui est améliorée grâce aux données captées par les autres sensors. Lorsque ces données sortent de votre téléphone elles ne peuvent en principe pas être transmises à n’importe qui et dans n’importe quelles conditions.

L’une des principales raisons du succès incroyable de l’internet mobile vient de la simplicité pour des tiers à créer des services qui fonctionnent sur un smartphone via les applications mobiles. Ce qui distingue d’ailleurs les applications des “sites mobiles” est la possibilité d’émuler directement un certain nombre de fonctionnalités de l’appareil. Quand vous ouvrez Google Maps, le GPS de votre appareil s’active automatiquement. Quand vous ouvrez Snap, c’est la caméra pointée sur votre visage,...En réalité l’application accède à tout un “bouquet” de services internes - capteurs bien sûr mais aussi fonctionnalités de paiement, d’authentification, de mémoire,.... Cet accès est contraint “nativement”. Chaque application passe sous le contrôle impitoyable des ingénieurs de Cupertino ou Mountain View avant d’être autorisée. Les accès aux fonctionnalités les plus sensibles peuvent être refusés s’ils ne contribuent pas à l’amélioration de l’expérience utilisateur ou représentent une intrusion trop profonde dans votre vie privée.

C’est dans ce contexte que s’inscrit  **la “quête” des applications de mobilité pour garantir une continuité de service lors des trajets souterrains de leurs utilisateurs**. Le GPS d’un appareil nécessite d’être connecté à trois satellites pour permettre une géolocalisation précise. Pas de GPS et votre véhicule apparaîtra comme “gelé” dans vos applis de navigation routière préférées. Et ne comptez pas sur elles pour vous dire s’il fallait sortir de préférence à St-Germain-en-Laye ou Poissy sous les tunnels de la Défense. Même problème dans le métro. Être prévenu par exemple de l’arrivée imminente dans la station à laquelle vous devez descendre permet d’éviter un torticolis ou tout simplement ne pas perdre un temps précieux. Difficile également de vous fournir un temps d’arrivée estimé optimum, ce qui est l’une des raisons principales du succès des applications comme Google Maps, Citymapper ou Transit.

Dans un récent blogpost, l’équipe de Transit justement nous explique comment elle a cherché…et trouvé la solution à ce problème d’ingénierie. On est jamais mieux servi que par soi-même : leurs 4 ingénieurs ont recueillis les “signatures vibratoires” de leurs trajets en métro grâce au capteur de vibration et l’accéléromètre de leur smartphone. Une fois les données nettoyées, l’équipe identifie les “fréquences” des différentes phases d’un trajet - à quai, accélération, tunnel, décélération,...- et en déduit les étapes successives du voyageur. Le modèle créé permet ensuite de simuler les différentes stations où devraient être le voyageur. Ces simulations sont comparées avec les relevés manuels des membres de l’équipe qui ont littéralement faits des centaines de trajets annotés à Montréal et New York. Un dernier modèle mathématique, appelé “The Mixer”, évalue la prédiction du type de mouvement, la dernière localisation connue, le moment depuis lequel elle a été mise à jour, ainsi que l’horaire du train. “Résultat : une prédiction correcte de la localisation dans 90 % des cas. Ce modèle offre un suivi en souterrain qui permet de montrer la position sur la carte, de mettre à jour l’heure d’arrivée et d’avertir quand il est temps de descendre, le tout hors ligne et sans envoi de données aux serveurs de Transit”. 

Lire [l’article sur le blog de Transit.](https://blog.transitapp.com/go-underground/)

À signaler que dès 2016 l’équipe de Snips (aujourd’hui dissoute) avait réalisé un travail comparable en utilisant le baromètre d’un smartphone. Grâce au bien connu Effet Venturi, la pression baisse quand le métro accélère et revient à son niveau normal quand le métro ralentit. Il suffisait alors de compter les stations et déterminer ensuite dans quel sens la rame roulait en chronométrant précisément les temps interstations. Après deux stations (ou plus si plusieurs stations successives ont le même temps inter-station) on sait à 90% dans quel sens on se dirige.

Lire [l’article de Snips.](https://medium.com/snips-ai/underground-location-tracking-3ea56803dddc)

{% image "./img/smartphone-accelerometre.png", "Mesures de l'accéléromètre d'un smartphone durant un trajet en métro", [300, 800], "(max-width: 800px) 80vw, 800px", "image-article" %}

Et Waze dans tout ça ? L’entreprise a posé des balises, appelées beacon, dans certains tunnels pour permettre aux smartphones de “connaître” leur position plus précisément. Puis comme pour Transit et Snips, le modèle en déduit l’avancée du véhicule dans le tunnel.

Lire l’article de Waze sur [les balises.](https://support.google.com/waze/partners/answer/9416071)
 
Des capteurs, des ingénieur·e·s, et vous serez à l’heure !

J’espère que vous avez apprécié cette incursion derrière le rideau de vos outils du quotidien.
Nous sommes DiaLog, la solution de numérisation de la réglementation routière. Vous représentez une collectivité locale ou un acteur directement intéressé par DiaLog ? Prenez rendez-vous avec l’équipe [directement ici.](https://cal.com/team/dialog/prise-de-contact-30-mn?layout=mobile&date=2024-11-21&month=2024-11)

**Stéphane Schultz - coach produit DiaLog**
</div>
