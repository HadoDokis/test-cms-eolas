<?php
/**
 * Met à disposition un module CMS :
 * * nom du module
 * * description
 * * auteurs
 * * numéro de version : {majeur}.{mineure sur éventuellement 2 entités "X.Y"}{dev + compteur de phase de dev}pl{Patch Level : compteur patch du module de référence}#{compteur modifications projet} ==> Cf. PHP version_compare
 * * dépendances avec d'autres modules éventuels (et versions compatibles} : array('{id module}' => {'version' | array('versions')}) => EX : array("core" => "5.5"), "actualite" => array("1.0", "1.1"))
 *
 * L'identifiant unique du module correspond au nom du répertoire dans lequel est placé ce fichier "_define.php".
 */
$this->registerModule(
    "Core / Noyau",
    "Noyau du CMS",
    "CMS Team <cms-eolas@eolas.fr>",
    "7.0.3",
    array('admin'=> array('4.2', '5.0'))
);
