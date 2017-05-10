<?php
require '../include/inc.bo_init.php';

Utilisateur::checkConnected();
require_once (CLASS_DIR . 'class.db_page.php');
require_once (CLASS_DIR . 'class.db_revision.php');
require_once (CLASS_DIR . 'class.db_webotheque.php');

$oRevision    = new Revision($_GET['rev']);
$oPage        = $oRevision->getPage();
$oPageRef    = $oPage->getPageRef();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
</head>
<body id="popup">
<?php include('../include/inc.bo_bandeau_hautPopup.php')?>
<div id="bo_contenuPopup">
    <h2><?php echo gettext('Revision_du') . ' ' .date('d/m/Y H:i', $oRevision->getField('REV_DATECREATION'))?></h2>
    <table class="liste revision">
        <thead>
            <tr>
                <th><?php echo gettext('Propriete')?></th>
                <th><?php echo gettext('Revision')?></th>
                <th><?php echo gettext('Page courante')?></th>
                <th><?php echo gettext('Remarques')?></th>
            </tr>
        </thead>
        <tbody>
            <?php ?>
            <tr>
                <th><?php echo gettext('Titre')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_TITRE'))?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_TITRE'))?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Titre menu')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_TITRE_MENU')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_TITRE_MENU')) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Titre survol')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_TITLE')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_TITLE')) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Page speciale')?></th>
                <td><?php echo secureInput(extraireLibelle($dbh->query("select PGS_LIBELLE from DD_PAGESPECIALE where PGS_CODE=". $dbh->quote($oPage->getField('PGS_CODE')))->fetchColumn()))?></td>
                <td><?php echo secureInput(extraireLibelle($dbh->query("select PGS_LIBELLE from DD_PAGESPECIALE where PGS_CODE=". $dbh->quote($oPageRef->getField('PGS_CODE')))->fetchColumn()))?></td>
                <td class="aligncenter">
                    <?php
                    $noticeLevel = $oPage->getLevelNotice('PGS_CODE');
                    ?>
                    <p class="noticeLevel_<?php echo $noticeLevel ?>">
                    <?php if ($noticeLevel == 1) {
                        echo gettext('La page actuelle est une page speciale');
                    } elseif ($noticeLevel == 2) {
                        $sql = "select * from OFF_PAGE where PGS_CODE=".$dbh->quote($oPage->getField('PGS_CODE'));
                        if ($row = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
                            echo secureInput(gettext('Propriete affectee a') . ' ' . $row['PAG_TITRE'] .' ('.$row['ID_PAGE'].').');
                        }
                    }?>
                    </p>
                </td>
            </tr>
            <?php
            $PSS_LIBELLE = $dbh->query("select PSS_LIBELLE from DD_PAGESTYLE where PSS_CODE=".$dbh->quote($oPageRef->getField('PSS_CODE')))->fetchColumn();
            if ($PSS_LIBELLE != '') {
            ?>
            <tr>
                <th><?php echo gettext('Feuille de style')?></th>
                <td></td>
                <td><?php echo secureInput(extraireLibelle($PSS_LIBELLE))?></td>
                <td class="aligncenter"><p class="noticeLevel_0"><?php echo gettext('Propriete heritee de la page actuelle') ?></td>
            </tr>
            <?php } ?>
            <tr>
                <th><?php echo gettext('Feuille de style')?></th>
                <td></td>
                <td><?php echo secureInput(extraireLibelle($PSS_LIBELLE))?></td>
                <td class="aligncenter"><p class="noticeLevel_0"><?php echo gettext('Propriete heritee de la page actuelle') ?></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Visible menu')?></th>
                <td><?php echo ($oPage->getField('PAG_VISIBLE_MENU')) ? gettext('Oui') : gettext('Non')?></td>
                <td><?php echo ($oPageRef->getField('PAG_VISIBLE_MENU')) ? gettext('Oui') : gettext('Non')?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Masquer colonne gauche')?></th>
                <td><?php echo ($oPage->getField('PAG_MASQUERGAUCHE')) ? gettext('Oui') : gettext('Non')?></td>
                <td><?php echo ($oPageRef->getField('PAG_MASQUERGAUCHE')) ? gettext('Oui') : gettext('Non')?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Masquer colonne droite')?></th>
                <td><?php echo ($oPage->getField('PAG_MASQUERDROITE')) ? gettext('Oui') : gettext('Non')?></td>
                <td><?php echo ($oPageRef->getField('PAG_MASQUERDROITE')) ? gettext('Oui') : gettext('Non')?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Exclue recherche')?></th>
                <td><?php echo ($oPage->getField('PAG_EXCLURECHERCHE')) ? gettext('Oui') : gettext('Non')?></td>
                <td><?php echo ($oPageRef->getField('PAG_EXCLURECHERCHE')) ? gettext('Oui') : gettext('Non')?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Texte d\'accroche')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_ACCROCHE')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_ACCROCHE')) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php
            if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE'))) {
            ?>
                <tr>
                    <th><?php echo gettext('Image accroche')?></th>
                    <td>
                        <?php
                        if ($oPage->getField('ID_WEBOTHEQUE_IMAGE')>0) {
                            $oWeboRev = new Webo_IMAGE($oPage->getField('ID_WEBOTHEQUE_IMAGE'));
                            if ($oWeboRev->exist()) {
                                echo secureInput($oWeboRev->getField('WEB_LIBELLE') . ' ('.$oWeboRev->getID().')');
                                ?>
                                <a href="<?php echo UPLOAD_IMAGE.$oWeboRev->getField('WEB_CHEMIN')?>" onclick="window.open(this.href); return false;">[ voir ]</a>
                            <?php }
                        }?>
                    </td>
                    <td>
                        <?php
                        if ($oPageRef->getField('ID_WEBOTHEQUE_IMAGE')>0) {
                            $oWeboRef = new Webo_IMAGE($oPageRef->getField('ID_WEBOTHEQUE_IMAGE'));
                            if ($oWeboRef->exist()) {
                                echo secureInput($oWeboRef->getField('WEB_LIBELLE') . ' ('.$oWeboRef->getID().')');
                                ?>
                                <a href="<?php echo UPLOAD_IMAGE.$oWeboRef->getField('WEB_CHEMIN')?>" onclick="window.open(this.href); return false;">[ voir ]</a>
                            <?php }
                        }?>
                    </td>
                    <td class="aligncenter"></td>
                </tr><?php
            }

            for ($i=1; $i<6; $i++) {
            ?>
            <tr>
                <th><?php echo gettext('Mot cle')?> <?php echo $i?></th>
                <td><?php echo secureInput($oPage->getField('PAG_MOTCLE' . $i)) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_MOTCLE' . $i)) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php }

            if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE'))) {
                ?>
                <tr>
                    <th><?php echo gettext('Redirection externe')?></th>
                    <td></td>
                    <td>
                        <?php
                        $noticeLevel = null;
                        if ($oPageRef->getField('ID_WEBOTHEQUE_LIENEXTERNE') > 0) {
                            $noticeLevel = 0;
                            $oWeboExt    = new Webo_LIENEXTERNE($oPageRef->getField('ID_WEBOTHEQUE_LIENEXTERNE'));
                            echo secureInput($oWeboExt->getField('WEB_LIBELLE') . ' ('.$oWeboExt->getID().')');
                            ?>
                            <a href="<?php echo $oWeboExt->getField('WEB_CHEMIN')?>" onclick="window.open(this.href); return false;">[ voir ]</a>
                        <?php } ?>
                    </td>
                    <td class="aligncenter">
                        <?php if ($noticeLevel) { ?>
                            <p class="noticeLevel_<?php echo $noticeLevel ?>"><?php echo gettext('Cette propriete sera integree aux proprietes de votre revision') ?></p>
                        <?php } ?>
                    </td>
                </tr><?php
            }

            ?>
            <tr>
                <th><?php echo gettext('Redirection interne')?></th>
                <td></td>
                <td>
                    <?php
                    $noticeLevel = null;
                    if ($oPageRef->getField('ID_PAGE_REDIRECT') > 0) {
                        $noticeLevel = 0;
                        $oPageRedirect    = new Page($oPageRef->getField('ID_PAGE_REDIRECT'), 'OFF_');
                        echo secureInput($oPageRedirect->getField('PAG_TITRE_MENU') . ' ('.$oPageRedirect->getID().')');
                        ?>
                        <a <?php echo $oPageRedirect->getAnchor() ?> onclick="window.open(this.href); return false;">[ voir ]</a>
                    <?php } ?>
                </td>
                <td class="aligncenter">
                    <?php if ($noticeLevel) { ?>
                        <p class="noticeLevel_<?php echo $noticeLevel ?>"><?php echo gettext('Cette propriete sera integree aux proprietes de votre revision') ?></p>
                    <?php } ?>
                </td>
            </tr>
            <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_SECURITE'))) {?>
            <?php ?>
            <tr>
                <th><?php echo gettext('Acces restreint')?></th>
                <td>
                    <ul>
                    <?php $sql = "select GROUPE.* from GROUPE inner join REVISION_GROUPE using (ID_GROUPE) where ID_REVISION=" . $oRevision->getID() . " order by GRP_LIBELLE";
                    foreach ($dbh->query($sql) as $rowTemp) { ?>
                        <li><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></li>
                    <?php } ?>
                    </ul>
                </td>
                <td>
                    <ul>
                    <?php $sql = "select GROUPE.* from GROUPE inner join GROUPE_OFF_PAGE  using (ID_GROUPE) where ID_PAGE=" . $oPage->getID() . " order by GRP_LIBELLE";
                    foreach ($dbh->query($sql) as $rowTemp) { ?>
                        <li><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></li>
                    <?php } ?>
                    </ul>
                </td>
                <td class="aligncenter"></td>
            </tr>
            <?php } ?>
            <?php ?>
            <tr>
                <th><?php echo gettext('Title')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_TITRE_REFERENCEMENT')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_TITRE_REFERENCEMENT')) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Metadescription')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_METADESCRIPTION')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_METADESCRIPTION')) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('URL')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_URLREWRITING')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_URLREWRITING')) ?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Frequence')?></th>
                <td><?php echo secureInput($oPage->getField('PAG_GOOGLEFREQUENCE'))?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_GOOGLEFREQUENCE'))?></td>
                <td class="aligncenter"></td>
            </tr>
            <?php ?>
            <tr>
                <th><?php echo gettext('Priorite')?></th>
                <td><?php echo secureInput($oPageRef->getField('PAG_GOOGLEPRIORITE')) ?></td>
                <td><?php echo secureInput($oPageRef->getField('PAG_GOOGLEPRIORITE')) ?></td>
                <td class="aligncenter"></td>
            </tr>
        </tbody>
    </table>
</div>
<?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
