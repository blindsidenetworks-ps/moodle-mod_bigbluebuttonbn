**Features :**
* Gestion de plusieurs serveurs BBB via l'interface admin de Moodle
* Possibilité d'action sur chaque serveur
	* Désactivation : Plus de nouvelles réunions ne sont placées sur le serveur concerné mais les réunions en cours continuent et il est toujours possible de les rejoindre.
	* Activation : Accepte de nouvelles réunions
	* Marqué comme crashé : Plus de nouvelles réunions ne sont placées sur le serveur concerné, les réunions en cours sur ce serveur sont interrompues. Une notification Moodle est envoyée aux participants. Les nouvelles réunions sont placées sur d'autres serveurs, les utilisateurs rejoindront le bon serveur s'ils utilisent à nouveau le lien.
* Accès à des statistiques d'usage des serveurs
* Gestion des enregistrements répartis sur plusieurs serveurs
* Récupération d'un tracking plus complet dans Moodle de l'activité dans les vidéo-conférence


**Test :**
* Gestion de plusieurs serveurs BBB via l'interface admin de Moodle
  + Lien : /mod/bigbluebuttonbn/servers.php
   + Pré-Conditions
        + être admin & connecté
        + Posséder des identifiants de serveurs BBB : url et secret
	
    + Step-By-Step
		+ Vérifier la présence dans l'interface : 'Site Administration > Plugins > Activity modules > BigBlueButton Servers'
		+ Ajout
			+ Vérifier ajout graphiquement et dans la DB : mdl_bigbluebuttonbn_servers
		+ Edition
			+ Vérifier edit graphiquement et dans la DB : mdl_bigbluebuttonbn_servers
		+ Suppression
			+ Vérifier del graphiquement et dans la DB : mdl_bigbluebuttonbn_servers
		+ Ajout d'un serveur qui existe déjà
			+ Même URL - même nom
			+ URL différente - même nom
			+ Même URL - nom différent
			+ Le serveur ne devrait pas s'ajouter
		+ Ajout d'un serveur préalablement supprimé
			+ Le serveur devrait s'ajouter
		+ Edition d'un serveur de façon à copier un autre serveur :
			+ Même URL - même nom
				+ Le serveur ne devrait pas s'ajouter
			+ URL différente - même nom
				+ Le serveur devrait s'ajouter
			+ Même URL - nom différent
				+ Le serveur ne devrait pas s'ajouter
	+ Post-Conditions :
		+ Aucune erreur n'est rencontrée lors du Step-by-Step
				
* Possibilité d'action sur chaque serveur
  + Lien : /mod/bigbluebuttonbn/servers.php
   + Pré-Conditions
        + être admin & connecté
	* Désactivation :
	    + Set-By-Step
			+ Paramétrer 3 serveurs
			+ L'un d'eux doit avoir un Weight beaucoup plus élevé (500 par rapport à 100 par exemple) => ceci nous assure qu'il devrait être choisi par l'algo qui sélectionne le serveur à la création de meeting
			+ Lancer un meeting sur un cours A pour vérifier le bon fonctionnement de cet algo
				+ L'URL doit être préfixée par celle du serveur attendu
			+ Désactiver le serveur
			+ Rejoindre le meeting du cours A avec un second utilisateur
			+ End le meeting => le meeting est terminé et le serveur désactivé
			+ Relancer un meeting dans le cours A : l'URL ne peut pas correspondre au serveur précédent
		+ Post-Conditions :
			+ Un serveur désactivé conserve ses meetings, il est toujorus possible de les rejoindre mais n'héberge pas de nouveaux meetings.
	* Activation :
       + Step-by-Step
			+ A la suite du test de désactivation :
			+ End le meeting
			+ Activer le serveur
			+ Relancer le meeting et vérifier l'URL. On doit être revenu sur le serveur au plus grand Weight.
		+ Post-Conditions :
			+ Un serveur activé accepte de nouveaux meetings.
	* Marqué comme crashé : 
		+ Step-by-Step
			+ TEST A : serveur unique
				+ Désactiver 2 des 3 serveurs pour forcer la création sur un seul d'entre eux.
				+ Lancer des meetings dans des cours A, B et C avec 3 utilisateurs différents.
					+ vérifier les URL : elles doivent avoir le même préfixe
				+ Réactiver 1 seul serveur
					! si aucun serveur n'est disponible lors de la génération d'une page liée à une activité BBB, une erreur est lancée. Hors la fin du meeting attendue par le crash redirigera sur cette page.
				+ Marquer le serveur hébergeant comme crashé
					+ Chacun des meetings doit s'être interrompu : les utilisateurs sont confrontés à un pop-up dans le meeting avant de se faire éjecter.
					+ Les utilisateurs sont redirigés sur la page de leurs cours respectifs
					+ Les utilisateurs ont bien reçu une notification les informant de ce qu'il vient de se produire
					+ Ils relancent le meeting, celui-ci est hébergé sur le seul serveur disponible
			
			+ TEST B : multi serveurs
				+ Activer l'entièreté des serveurs
				+ Lancer des meetings dans des cours A, B et C avec 3 utilisateurs différents.	
				+  vérifier les URL : elles doivent avoir différents préfixes		
				+  Réactiver 1 seul serveur	*ATTENTION si aucun serveur n'est disponible lors de la génération d'une page liée à une activité BBB, une erreur est lancée. Hors la fin du meeting attendue par le crash redirigera sur cette page.*
				+ Marquer un des serveurs hébergeant comme crashé (on vérifie quels serveurs ont été selectionnés via l'URL, nous en désirons au moins 2 différents)
					+ Seul les meetings du serveur crashé sont interrompus
					+ Les utilisateurs concernés sont redirigés sur la page de leurs cours respectifs
					+ Les utilisateurs concernés ont bien reçu une notification les informant de ce qu'il vient de se produire
					+ Ils relancent le meeting, celui-ci est hébergé sur l'un des autres serveurs.

		+ Post-Conditions :
		     + Un serveur crashé tue tous ses meetings et n'en héberge plus de nouveaux
	
* Accès à des statistiques d'usage des serveurs
	+ Lien : /mod/bigbluebuttonbn/servers.php
   + Pré-Conditions
        + être admin & connecté
    + Step-by-Step
		+ Sur une plateforme vierge.
		+ Vérifier la présence dans l'interface : 'Site Administration > Plugins > Activity modules > BigBlueButton Servers > Recent Activities'
			+ Le graphique n'affiche rien
			+ Les boutons permettant de voir les graphs pour : 24h, 1w, 1m, 3m, 1y sont disponibles
				+ aucun de ces graphiques n'affiche rien
		+ On lance des meetings depuis plusieurs cours, en s'y connectant avec plusieurs utilisateurs.
			+ On prend note du serveur concerné et du nombre d'utilisateurs connectés
			+ On recommence cette procédure jusqu'à avoir un minimum de statistiques concernant chaque serveur.
		+ On run le cron permettant d'aller récupérer les stats : sudo -u www-data php admin/cli/scheduled_task.php --execute="\mod_bigbluebuttonbn\task\interrogateservers"
		+ On vérifie dans l'interface la présence des stats et leur correspondance avec les notes prises
		+ On reproduit la procédure des 3 points précédents afin de vérifier que les statistiques sont bien mises à jour.
			+ Les notes prises lors de cette reproduction doivent être séparées des premières
			+ Lorsque l'on vérifiera les stats dans l'interface on attend :
				+ Dans Recent Statistics les stats de la seconde prise de note
				+ Dans les autres graphs la somme des stats
		+ Pour chaque graphique on devra trouver 6 colonnes, de 3 couleurs différentes. Une couleur par serveur, une col pour le nombre de meetings et une col pour le nombre de participants.
	+ Post-conditions :
		+ Les statistiques sont bien enregistrées dans la table
		+ Les statistiques sont bien accessible graphiquement
		+ Graphiquement, les statistiques affichées correspondent à la réalité
		
* Gestion des enregistrements répartis sur plusieurs serveurs	
	+ Lien : /mod/bigbluebuttonbn/servers.php
   + Pré-Conditions
        + être admin ou teacher & connecté
    + Step-by-Step
		+ Sur la page d'une activité BBB dans un cours.
		+ Nous trouvons en dessous du boutton 'Join Session' un espace 'Recordings' vide
		+ On effectue un premier enregistrement
			+ On lance un meeting et on lance un record
			+ On note l'URL du serveur lié à ce meeting
			+ On interromp le record et on end le meeting.
			+ On attend au moins 3 minutes : temps minimum pour que BBB gère l'enregistrement
			+ On lance le cron allant récupérer cet enregistrement : sudo -u www-data php admin/cli/scheduled_task.php --execute="\mod_bigbluebuttonbn\task\get_recordings"
			+ On vérifie sur la page du cours la bonne présence de cet enregistrement.
		+ On effecture un second enregistrement
			+ même procédure
	+ Post-Conditions :
		+ Le cron rempli bien mdl_bigbluebuttonbn_recordings
		+ Il n'y fait pas de doublons
		+ Graphiquement, sur la page de l'activité BBB les recordings concernés sont bien listés (on peut le savoir facilement grâce à leur nom = celui de l'activité)