<?php
$_enabled = array(
    'MOD_XXX' => CMS::getCurrentSite()->hasModule(new Module('MOD_XXX'))
        && Utilisateur::getConnected()->checkProfil(array('PRO_XXX')),
);
if ($_enabled['MOD_XXX']) {
    $_child = $__child = array();
    $__child['LISTE'] = array('label'=>gettext('Lister'), 'url'=>SERVER_ROOT . 'externe/ext_XXXListe.php');
    $__child['ADD'] = array('label'=>gettext('Ajouter'), 'url'=>SERVER_ROOT . 'externe/ext_XXX.php');
    $_child['XXX'] = array('hideMenu'=>true, 'label'=>'XXX', 'url'=>SERVER_ROOT . 'externe/ext_XXXListe.php', 'child'=>$__child);
    $__child = array();
    $__child['LISTE'] = array('label'=>gettext('Lister'), 'url'=>SERVER_ROOT . 'externe/ext_XXXThematiqueListe.php');
    $__child['ADD'] = array('label'=>gettext('Ajouter'), 'url'=>SERVER_ROOT . 'externe/ext_XXXThematique.php');
    $_child['THEMA'] = array('label'=>'Thématiques', 'url'=>SERVER_ROOT . 'externe/ext_XXXThematiqueListe.php', 'child'=>$__child);
    $child['MOD_XXX'] = array('label'=>'XXX', 'url'=>SERVER_ROOT . 'externe/ext_XXXThematiqueListe.php', 'child'=>$_child);
}
