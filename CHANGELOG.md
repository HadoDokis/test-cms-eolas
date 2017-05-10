# Changements du CMS 7.0.3

## Nouvelles fonctionnalités

* [21993](https://redmine.eolas.lan/issues/21993) : `WebothequeImage` : ajout d'une méthode statique pour generer `<img>` :
 * Les modules peuvent faire appel à la méthode `Webo_IMAGE::getIMGHTML()` pour générer le code `HTML` des images uploadées (même hors wébothèque).
* [21433](https://redmine.eolas.lan/issues/21433) : BO : avoir une vue d'ensemble des modules activés sur tous les sites ^(Impact sur la doc utilisateur)
* [21427](https://redmine.eolas.lan/issues/21427) : Template de page ^(Impact sur la doc utilisateur)
 * Sur le même modèle que le "`template de page fille`", un template a été ajouté dans le noyau pour lister un ensemble de page pioché dans l'arborescence
* [21176](https://redmine.eolas.lan/issues/21176) : Envoi de mail : pouvoir préciser un email par site pour l'expédition ^(Impact sur la doc utilisateur)
* [21063](https://redmine.eolas.lan/issues/21063) : Gestion des méta "`open graph`" ^(Impact sur la doc utilisateur)
* [20909](https://redmine.eolas.lan/issues/20909) : Ancrage du menu BO ^(Impact sur la doc utilisateur)
* [20681](https://redmine.eolas.lan/issues/20681) : Administration des sitemap des modules ^(Impact sur la doc utilisateur)
* [20412](https://redmine.eolas.lan/issues/20412) : Nouvelles méthodes dans la classe pagination

## Modifications

* [21996](https://redmine.eolas.lan/issues/21996) : `UrlBuilder` : appell à `getUrlParser` pour les classes héritantes
* [21992](https://redmine.eolas.lan/issues/21992) : Mise à jour des `CSS` d'initialisation
* [21991](https://redmine.eolas.lan/issues/21991) : Video externe : les rendre responsive ^(Impact sur la doc utilisateur)
* [21920](https://redmine.eolas.lan/issues/21920) : Extensions webotheque
* [21431](https://redmine.eolas.lan/issues/21431) : `Editor::parsing` : optimisation pour ASPMAil
* [21430](https://redmine.eolas.lan/issues/21430) : Verbosité des logs du crons
* [21404](https://redmine.eolas.lan/issues/21404) : Suppression des pages spéciales devenues inutiles ^(Impact sur la doc utilisateur)
* [21064](https://redmine.eolas.lan/issues/21064) : Suppression des références `Xiti`
* [21058](https://redmine.eolas.lan/issues/21058) : Module Recherche référencement ^(Impact sur la doc utilisateur)
* [21057](https://redmine.eolas.lan/issues/21057) : `TPL_CODE` dans `DD_LIAISON`
* [21046](https://redmine.eolas.lan/issues/21046) : Prise en compte du `required` pour une case à cocher (si unique)
* [20763](https://redmine.eolas.lan/issues/20763) : Mise à jour tutorial V7
* [20611](https://redmine.eolas.lan/issues/20611) : Bulle d'aide

## Corrections de bugs

* [21995](https://redmine.eolas.lan/issues/21995) : `Autosave` : bug sur version brouillon selon l'OS
* [21965](https://redmine.eolas.lan/issues/21965) : Liaisons modules / webothèque
* [21954](https://redmine.eolas.lan/issues/21954) : CRON CMS : pb de locale pour les chiffres
* [21903](https://redmine.eolas.lan/issues/21903) : Paragraphe Rédactionnel > Bloc "`En savoir Plus`"
* [21794](https://redmine.eolas.lan/issues/21794) : Largeur légende d'image
* [21163](https://redmine.eolas.lan/issues/21163) : `Page::clearCache()` sans site courant
* [21047](https://redmine.eolas.lan/issues/21047) : Envoi des mails via `SMTP`
* [20908](https://redmine.eolas.lan/issues/20908) : Popup de choix d'une catégorie de webotheque
* [20722](https://redmine.eolas.lan/issues/20722) : Présence erronée d'un événement JS `onclick` sur la suppression des fichiers Webm (music)
* [20707](https://redmine.eolas.lan/issues/20707) : Intégration d'un style au sein de l'éditeur non souhaité
* [20705](https://redmine.eolas.lan/issues/20705) : Lecture des vidéos `.webm` ou `.mp4` mal gérée
* [20623](https://redmine.eolas.lan/issues/20623) : Suppression du cache des pages lors des modifications sur les liaisons ajax
* [20622](https://redmine.eolas.lan/issues/20622) : Suppression des liaisons externes non souhaitées
* [20387](https://redmine.eolas.lan/issues/20387) : Insertion lien (interne / externe) depuis tiny sous chrome
