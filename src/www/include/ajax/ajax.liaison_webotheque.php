<?php
require '../inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_webotheque.php';

if (!empty($_GET['classname'])) {

    $className = preg_replace('/[^a-zA-Z1-9_]/', '', $_GET['classname']);

    if (file_exists(CLASS_DIR . 'class.ext_db_' . $className . '.php')) {
        require_once CLASS_DIR . 'class.ext_db_' . $className . '.php';
    } elseif (file_exists(CLASS_DIR . 'class.db_' . $className . '.php')) {
        require_once CLASS_DIR . 'class.db_' . $className . '.php';
    } else {
        die("Aucun fichier trouvé pour la classe " . $className);
    }
    $className = ucfirst($className);
    $oObjet    = new $className($_GET['idtf']);

    if (!($oObjet instanceof Ajax)) {
        die('La classe "'. $className . '" doit étendre la classe "Ajax"');
    }
    if ($oObjet->exist()) {
        $oObjet->checkAuthorized();
    }

    $aTemp = explode(':', $_GET['LIA_CODE']);
    $divID = $_GET['LIA_CODE'];

    $LIA_CODE = $aTemp[0];
    $LIA_TYPE = strval($aTemp[1]);

    //ajout pour champs texte associés
    $LIA_TEXT  = null;
    $withMax = $withMin = $hasText = $textIsNotNull = $error = false;
    $labelText = "Texte";

    $arTmp = $_GET['classStr'];
    $arTmp = explode('@', $arTmp);
    foreach ($arTmp as $val) {
        if (preg_match('/min([0-9]+)/', $val, $matches)) { // Avec un minimum d'éléments dans la sélection
            $withMin = $matches[1];
        } elseif (preg_match('/max([0-9]+)/', $val, $matches)) { // Avec un maximum d'éléments dans la sélection
            $withMax = $matches[1];
        } elseif ($val == 'hasText') { // Propose en champ texte
            $hasText = true;
        } elseif (strpos($val, 'hasText:') === 0) { // Propose en champ texte avec un libellé personnalisé
            $labelText = substr($val, 8);
            $labelText = str_replace('_', ' ', $labelText);
            $hasText = true;
        } elseif ($val == 'textIsNotNull') { // Champs texte obligatoire
            $textIsNotNull = true;
        }
    }

    $aRow = $oObjet->getLiaisonWebotheque($LIA_CODE, $LIA_TYPE);
    if (is_numeric($_GET['insert'])) {
        if (!$withMax || ($withMax > count($aRow))) {
            $oObjet->saveLiaisonWebotheque($_GET['insert'], $_GET['onlyOne'] ? $LIA_CODE : '', $LIA_TYPE, $LIA_TEXT);
            $aRow = $oObjet->getLiaisonWebotheque($LIA_CODE, $LIA_TYPE);
        } else {
            $error = sprintf(gettext('selection liaisons maximum atteint'), $withMax);
        }
    } elseif (is_numeric($_GET['delete'])) {
        if (!$withMin || ($withMin < count($aRow))) {
            $oObjet->deleteLiaisonWebotheque($_GET['delete'], $LIA_CODE, $_GET['onlyOne'], $LIA_TYPE);
            $aRow = $oObjet->getLiaisonWebotheque($LIA_CODE, $LIA_TYPE);
        } else {
            $error = sprintf(gettext('selection liaisons minimum atteint'), $withMin);
        }
    } elseif (is_numeric($_GET['up'])) {
        $oObjet->upLiaisonWebotheque($_GET['up']);
        $aRow = $oObjet->getLiaisonWebotheque($LIA_CODE, $LIA_TYPE);
    } elseif (is_numeric($_GET['down'])) {
        $oObjet->downLiaisonWebotheque($_GET['down']);
        $aRow = $oObjet->getLiaisonWebotheque($LIA_CODE, $LIA_TYPE);
    }

    if ($error) {
        echo '<p class="ajaxLiaison_notice">'.secureInput($error).'</p>';
    }

    if (count($aRow) > 0) {
        if ($LIA_CODE=='WBT_IMAGE') { ?>
            <ul class="ajaxListe">
            <?php $cpt = 1;
            foreach ($aRow as $row) {
                $oWebo = new Webo_IMAGE($row['ID_WEBOTHEQUE']);
                $oWebo->setFields($row);?>
                <li id="<?php echo $LIA_CODE?>_<?php echo $row['ID_WEBOTHEQUE']?>" class="ajaxImage ajaxItem"><!-- .ajaxItem, pour controle des formulaires min et max -->
                    <a href="<?php echo $oWebo->getSRC()?>" class="lightbox">
                        <img src="<?php echo $oWebo->getThumbSRC()?>" alt="idtf: <?php echo $oWebo->getID()?>" title="idtf: <?php echo $oWebo->getID()?>"><br>
                    </a>
                    <?php if ($cpt != 1) { ?>
                    <a href="#" onclick="ajaxLiaison.getAjax('<?php echo $divID ?>', 'webotheque', 'up', <?php echo $row['ID_LIAISON_WEBOTHEQUE']?>); return false;"><img src="<?php echo SERVER_ROOT;?>images/pagination/triLeft.gif" alt="<?php echo gettext('Monter')?>"></a>
                    <?php } else { ?>
                    <img src="<?php echo SERVER_ROOT;?>images/pagination/empty.gif" alt="">
                    <?php } ?>
                    <?php
                    // Suppression possible ?
                    if (!$withMin || (count($aRow)>$withMin)) {
                    ?>
                    <a href="#" onclick="ajaxLiaison.getAjax('<?php echo $divID ?>', 'webotheque', 'delete', <?php echo $row['ID_WEBOTHEQUE']?>); return false;"><img src="<?php echo SERVER_ROOT;?>images/pagination/delete.gif" alt="<?php echo gettext('DELETE')?>"></a>
                    <?php
                    }
                    ?>
                    <?php if ($cpt != count($aRow)) { ?>
                    <a href="#" onclick="ajaxLiaison.getAjax('<?php echo $divID ?>', 'webotheque', 'down', <?php echo $row['ID_LIAISON_WEBOTHEQUE']?>); return false;"><img src="<?php echo SERVER_ROOT;?>images/pagination/triRight.gif" alt="<?php echo gettext('Descendre')?>"></a>
                    <?php } else { ?>
                    <img src="<?php echo SERVER_ROOT;?>images/pagination/empty.gif" alt="">
                    <?php } ?>
                    <label><?php echo secureInput($row['WEB_LIBELLE'])?></label>
                    <?php if ($hasText) { ?>
                    <input type="text" id="<?php echo $divID?>_<?php echo $row['ID_WEBOTHEQUE']?>_text" value="<?php echo secureInput($row['LIA_TEXT'])?>" size="40" onchange="ajaxLiaison.saveText(<?php echo $row['ID_LIAISON_WEBOTHEQUE']?>, 'WEBOTHEQUE', this.value);return false;" placeholder="<?php echo secureInput($labelText)?>"<?php if($textIsNotNull) echo ' required';?>>
                    <?php } ?>
                </li>
                <?php
                    $cpt++;
                } ?>
            </ul>
        <?php } else { ?>
            <ul class="ajaxListe">
            <?php $cpt = 1;
            foreach ($aRow as $row) { ?>
                <li id="<?php echo $LIA_CODE?>_<?php echo $row['ID_WEBOTHEQUE']?>" class="ajaxItem"><!-- Pour controle des formulaires min et max -->
                    <?php
                    // Suppression possible ?
                    if (!$withMin || (count($aRow)>$withMin)) {
                    ?>
                    <a href="#" onclick="ajaxLiaison.getAjax('<?php echo $divID ?>', 'webotheque', 'delete', <?php echo $row['ID_WEBOTHEQUE']?>); return false;"><img src="<?php echo SERVER_ROOT;?>images/pagination/delete.gif" alt="<?php echo gettext('DELETE')?>"></a>
                    <?php
                    }
                    ?>
                    <?php if ($cpt != 1) { ?>
                    <a href="#" onclick="ajaxLiaison.getAjax('<?php echo $divID ?>', 'webotheque', 'up', <?php echo $row['ID_LIAISON_WEBOTHEQUE']?>); return false;"><img src="<?php echo SERVER_ROOT;?>images/pagination/triDesc.gif" alt="<?php echo gettext('Monter')?>"></a>
                    <?php } else { ?>
                    <img src="<?php echo SERVER_ROOT;?>images/pagination/empty.gif" alt="">
                    <?php } ?>
                    <?php if ($cpt != count($aRow)) { ?>
                    <a href="#" onclick="ajaxLiaison.getAjax('<?php echo $divID ?>', 'webotheque', 'down', <?php echo $row['ID_LIAISON_WEBOTHEQUE']?>); return false;"><img src="<?php echo SERVER_ROOT;?>images/pagination/triAsc.gif" alt="<?php echo gettext('Descendre')?>"></a>
                    <?php } else { ?>
                    <img src="<?php echo SERVER_ROOT;?>images/pagination/empty.gif" alt="">
                    <?php } ?>
                    <label title="idtf: <?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></label>
                    <?php if ($hasText) { ?>
                    <input type="text" id="<?php echo $divID?>_<?php echo $row['ID_WEBOTHEQUE']?>_text" value="<?php echo secureInput($row['LIA_TEXT'])?>" size="40" onchange="ajaxLiaison.saveText(<?php echo $row['ID_LIAISON_WEBOTHEQUE']?>, 'WEBOTHEQUE', this.value);return false;" placeholder="<?php echo secureInput($labelText)?>"<?php if($textIsNotNull) echo ' required';?>>
                    <?php } ?>
                </li>
                <?php
                    $cpt++;
                } ?>
            </ul>
        <?php
        }
    }
} else {
    die('Vous devez faire référence au fichier appelant dans "ajax.liaison_webotheque.php"');
}
