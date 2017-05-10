<?php $row = $oPage->getFields(); ?>
<script src="../include/js/onglet.js" type="text/javascript"></script>
<div id="corps">
    <div id="bo_contenu">
        <h2><?php echo ($row['PAG_TITRE_MENU'] != '') ? secureInput($row['PAG_TITRE_MENU']) : '&nbsp;'?></h2>
        <form method="get" action="" id="formCreation" class="creation" onsubmit="retun false;">
            <table>
                <tbody>
                    <tr>
                        <td colspan="2">
                            <?php $tempIsParent = false;
                            $oPageTemp = new Page($oPage->getID(), 'OFF_');

                            foreach ($oPageTemp->getParents() as $oPageTempBis) {
                                echo ($oPageTempBis->checkAuthorized(false) && !$oPageTempBis->isLocked())
                                    ? '&gt;<a href="cms_page.php?idtf=' . $oPageTempBis->getID() . '">' . secureInput($oPageTempBis->getField('PAG_TITRE_MENU')) . '</a>'
                                    : secureInput($oPageTempBis->getField('PAG_TITRE_MENU'));
                            }

                            if ($tempIsParent) {
                                echo ($oPageTemp->checkAuthorized(false) && !$oPageTemp->isLocked())
                                    ? '&gt;<a href="cms_page.php?idtf=' . $oPageTemp->getID() . '">' . secureInput($oPageTemp->getField('PAG_TITRE_MENU')) . '</a>'
                                    : secureInput($oPageTemp->getField('PAG_TITRE_MENU'));
                            } ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                        <h2><?php echo ($row['PAG_TITRE_MENU'] != '') ? secureInput($row['PAG_TITRE_MENU']).' (n° '.$row['ID_PAGE'].')' : '&nbsp;'?></h2>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <fieldset>
                                <legend><?php echo gettext('Zone_informations')?></legend>
                                <table>
                                    <tr>
                                        <td style="width: 33%;">
                                        <?php if ($isActualData) {
                                            $sql = "select count(GROUPE.ID_GROUPE) from GROUPE inner join GROUPE_OFF_PAGE using (ID_GROUPE)
                                            where ID_PAGE=" . $oPage->getID() . " order by GRP_LIBELLE";
                                        } else {
                                            $sql = "select count(REVISION_GROUPE.ID_GROUPE) from REVISION_GROUPE
                                            where ID_REVISION=" . $revIdselected;
                                        }

                                        if ($dbh->query($sql)->fetch(PDO::FETCH_COLUMN) > 0) { ?>
                                            <?php echo gettext('Acces')?> : <a href="#infos_onglet_droits" data-rel="tab"><?php echo gettext('restreint')?></a>
                                        <?php } else { ?>
                                            <?php echo gettext('Acces')?> : <a href="#infos_onglet_droits" data-rel="tab"><?php echo gettext('public')?></a>
                                        <?php } ?>
                                        </td>
                                        <td style="width: 33%;">
                                        <?php if ($row['PGS_CODE'] != '') {
                                            $libPgs = $dbh->query("select PGS_LIBELLE from DD_PAGESPECIALE where PGS_CODE = ".$dbh->quote($row['PGS_CODE']))->fetch(PDO::FETCH_COLUMN); ?>
                                            <?php echo gettext('page_speciale')?> :<a href="#infos_onglet_comportement" data-rel="tab"><?php echo secureInput(extraireLibelle($libPgs))?></a>
                                        <?php } else {
                                            echo '&nbsp;';
                                        } ?>
                                        </td>
                                        <td style="width: 33%;">
                                        <?php if ($oPage->getInternalRedirection()) { ?>
                                            <?php echo gettext('Redirection')?> :<a href="#infos_onglet_comportement" data-rel="tab"><?php echo gettext('interne')?></a>
                                        <?php } elseif ($oPage->getExternalRedirection()) { ?>
                                            <?php echo gettext('Redirection')?> :<a href="#infos_onglet_comportement" data-rel="tab"><?php echo gettext('externe')?></a>
                                        <?php } else {
                                            echo '&nbsp;';
                                        } ?>
                                        </td>
                                    </tr>
                                </table>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <fieldset>
                                <legend><?php echo gettext('Titres')?></legend>
                                <table class="bloc_proprietes">
                                    <tbody>
                                        <tr>
                                            <th><label><?php echo gettext('TitrePageLong')?></label></th>
                                            <td><span><?php echo secureInput($row['PAG_TITRE'])?></span></td>
                                        </tr>
                                        <tr>
                                            <th><label><?php echo gettext('TitreMenu')?></label></th>
                                            <td><span><?php echo secureInput($row['PAG_TITRE_MENU'])?></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- onglets -->
                    <tr class="tr_page_onglets">
                        <td class="td_page_onglets" colspan="2">
                            <div class="page_onglets" id="page_onglets">
                                <div class="clear"></div>
                                <fieldset class="tab" id="infos_onglet_contenu">
                                    <legend><?php echo  gettext('OngletContenuEtMiseEnPage')?></legend>
                                    <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                                    <table>
                                        <tr>
                                            <td style="width: 50%;">
                                                <input disabled="disabled" type="checkbox" name="PAG_VISIBLE_MENU" id="PAG_VISIBLE_MENU" value="1"<?php if ($row['PAG_VISIBLE_MENU'] == '1' || $row['PAG_VISIBLE_MENU'] == '' ) echo ' checked'?>><label for="PAG_VISIBLE_MENU"><?php echo gettext('FaireApparaitrePageMenu')?></label><br/>
                                                <input disabled="disabled" type="checkbox" name="PAG_MASQUERGAUCHE" id="PAG_MASQUERGAUCHE" value="1"<?php if ($row['PAG_MASQUERGAUCHE']) echo ' checked'?>><label for="PAG_MASQUERGAUCHE"><?php echo gettext('MasquerColonneGauche')?></label><br/>
                                                <input disabled="disabled" type="checkbox" name="PAG_MASQUERDROITE" id="PAG_MASQUERDROITE" value="1"<?php if ($row['PAG_MASQUERDROITE']) echo ' checked'?>><label for="PAG_MASQUERDROITE"><?php echo gettext('MasquerColonneDroite')?></label>
                                                <br>
                                            </td>

                                            <td style="width: 50%;">
                                                <table>
                                                    <?php if (!empty($row['PSS_CODE'])) {
                                                        $sql = "select PSS_LIBELLE from DD_PAGESTYLE where GBS_CODE=" . $dbh->quote(CMS::getCurrentSite()->getField('GBS_CODE')) . " and PSS_CODE = " . $dbh->quote($row['PSS_CODE']) . " order by PSS_LIBELLE";
                                                        $selectedStyleDfaut = $dbh->query($sql)->fetchColumn();
                                                    } else {
                                                        $selectedStyleDfaut = gettext('FeuilleStyleDefaut');
                                                    } ?>
                                                    <tr>
                                                        <th><label><?php echo gettext('Feuille de style')?></label></th>
                                                        <td>
                                                            <span><?php echo (empty($selectedStyleDfaut))? secureInput($selectedStyleDfaut) : secureInput(extraireLibelle($selectedStyleDfaut));?></span>
                                                        </td>
                                                    </tr>
                                                    <?php if (intval($row['ID_STYLEDYNAMIQUE']) > 0) {
                                                        $sql = "select STY_LIBELLE from STYLEDYNAMIQUE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " and ID_STYLEDYNAMIQUE = " . $row['ID_STYLEDYNAMIQUE'] . " order by STY_LIBELLE";
                                                        $selectedStyle = $dbh->query($sql)->fetchColumn();
                                                    } else {
                                                        $selectedStyle = '';
                                                    } ?>
                                                    <tr>
                                                        <th><label><?php echo gettext('Feuille de style perso')?></label></th>
                                                        <td>
                                                            <span><?php echo secureInput($selectedStyle)?></span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table class="bloc_proprietes" style="width: 50%;border: none;">
                                                    <tr>
                                                        <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE'))) { ?>
                                                        <th><label><?php echo gettext('Image accroche')?></label></th>
                                                        <td>
                                                        <?php if (intval($row['ID_WEBOTHEQUE_IMAGE']) > 0) {
                                                        require_once CLASS_DIR . 'class.db_webotheque.php';
                                                            $oWebImage = new Webo_IMAGE($row['ID_WEBOTHEQUE_IMAGE']);
                                                            if ($oWebImage->exist()) { ?>
                                                                <img src="<?php echo $oWebImage->getThumbSRC()?>">
                                                            <?php }
                                                        } ?>
                                                        </td>
                                                        <?php } else { ?>
                                                            <td colspan="2"></td>
                                                        <?php } ?>
                                                    </tr>
                                                    <tr>
                                                        <th><label><?php echo gettext("Texte d'accroche")?></label></th>
                                                        <td><span><?php echo secureInput($row['PAG_ACCROCHE'])?></span></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php } ?>
                                </fieldset>

                                <fieldset class="tab" id="infos_onglet_comportement">
                                    <legend><?php echo  gettext('OngletComportement')?></legend>
                                    <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="width: 50%;">
                                                <table class="bloc_proprietes" style="border: none;">
                                                    <tr>
                                                        <th><label style="font-weight:bold;"><?php echo gettext('Page speciale')?></label></th>
                                                    </tr>
                                                    <?php if (!empty($row['PGS_CODE'])) {
                                                        $sql = "select PGS_LIBELLE from DD_PAGESPECIALE where PGS_CODE=" . $dbh->quote($row['PGS_CODE']);
                                                        $selectedPGS = $dbh->query($sql)->fetchColumn();
                                                    } else {
                                                        $selectedPGS = '';
                                                    } ?>
                                                    <tr>
                                                        <th><label><?php echo gettext('Page speciale')?></label></th>
                                                        <td>
                                                            <span><?php echo (empty($selectedPGS))? secureInput($selectedPGS) : secureInput(extraireLibelle($selectedPGS));?></span>
                                                        </td>
                                                    </tr>

                                                    <!-- Ajout pour le module commentaire -->
                                                    <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE'))) { ?>
                                                    <tr>
                                                        <th><label for="PAG_COMMENTAIREACTIF"><?php echo gettext('com_autoriser_depot_commentaire') ?></label></th>
                                                        <td>
                                                            <input disabled="disabled" type="checkbox" id="PAG_COMMENTAIREACTIF" name="PAG_COMMENTAIREACTIF" value="1"<?php if(!empty($row['PAG_COMMENTAIREACTIF'])) echo ' checked'?>>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>

                                                    <!-- Ajout HTTPS -->
                                                    <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS')) { ?>
                                                    <tr>
                                                        <th><label for="PAG_HTTPS"><?php echo gettext('Acces securise (HTTPS)') ?></label></th>
                                                        <td>
                                                            <input disabled="disabled" type="checkbox" id="PAG_HTTPS" name="PAG_HTTPS" value="1"<?php if(!empty($row['PAG_HTTPS'])) echo ' checked'?>>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </table>
                                            </td>
                                            <td style="width: 50%;">
                                                <table class="bloc_proprietes" style="border: none;">
                                                    <tr><th colspan="2" id="redirigerLaPage"><?php echo gettext('Redirection')?></th></tr>
                                                    <tr><td colspan="2"><span class="note"><?php echo gettext('Rediriger automatiquement l internaute vers une autre page')?></span></td></tr>
                                                    <tr>
                                                        <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE'))) { ?>
                                                        <th><label><?php echo gettext('Redirection externe')?></label></th>
                                                            <td>
                                                            <?php if (intval($row['ID_WEBOTHEQUE_LIENEXTERNE']) > 0) {
                                                                require_once CLASS_DIR . 'class.db_webotheque.php';
                                                                $oWebLienExterne = new Webo_LIENEXTERNE($row['ID_WEBOTHEQUE_LIENEXTERNE']);
                                                                if ($oWebLienExterne->exist()) { ?>
                                                                    <a href="<?php echo $oWebLienExterne->getField('WEB_CHEMIN')?>"><?php echo secureInput($oWebLienExterne->getField('WEB_LIBELLE'))?></a>
                                                                <?php }
                                                            } ?>
                                                            </td>
                                                        <?php } ?>
                                                    </tr>
                                                    <tr>
                                                        <th><label><?php echo gettext('Redirection interne')?></label></th>
                                                        <td>
                                                        <?php if (intval($row['ID_PAGE_REDIRECT']) > 0) {
                                                            require_once CLASS_DIR . 'class.db_page.php';
                                                            $oPageRedirect = new Page($row['ID_PAGE_REDIRECT']);
                                                            if ($oPageRedirect->exist()) { ?>
                                                                <a href="/cms/cms_page.php?idtf=<?php echo $oPageRedirect->getID()?>"><?php echo secureInput($oPageRedirect->getField('PAG_TITRE_MENU'))?></a>
                                                            <?php }
                                                        } ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><label><?php echo gettext('InfoBulleMenu')?></label></th>
                                                        <td><span><?php echo secureInput($row['PAG_TITLE'])?></span></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php } ?>
                                </fieldset>

                                <fieldset class="tab" id="infos_onglet_tags">
                                    <legend><?php echo  gettext('OngletTagsMoteurRecherche')?></legend>
                                    <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="width: 50%;">
                                                <table>
                                                    <?php for ($i=1; $i<6; $i++) {?>
                                                    <tr>
                                                        <th><label><?php echo gettext('Mot cle') . ' ' . $i?></label></th>
                                                        <td>
                                                            <span><?php echo secureInput($row['PAG_MOTCLE' . $i])?></span>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </table>
                                            </td>
                                            <td style="width: 50%;">
                                                <input disabled="disabled" type="checkbox" name="PAG_EXCLURECHERCHE" id="PAG_EXCLURECHERCHE" value="1"<?php if ($row['PAG_EXCLURECHERCHE']) echo ' checked'?>><label for="PAG_EXCLURECHERCHE"><?php echo gettext('ExcluireMoteurRecherche')?></label>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php } ?>
                                </fieldset>

                                <fieldset class="tab" id="infos_onglet_droits">
                                    <legend><?php echo  gettext('OngletDroitsAccess')?></legend>
                                    <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                                    <table style="width: 100%;">
                                        <tr>
                                            <td colspan="3"><span class="note"><?php echo gettext('restreindre acces groupe affecte')?></span></td>
                                        </tr>
                                        <tr>
                                            <td style="width: 60%;">
                                                <table class="selection">
                                                    <tr>
                                                        <td><?php echo gettext('Affecte(s)')?></td>
                                                        <td>&nbsp;</td>
                                                        <td><?php echo gettext('Disponible(s)')?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <select name="ID_GROUPE[]" id="ID_GROUPE" size="6" multiple disabled="disabled">
                                                                <?php if ($isActualData) {
                                                                    $sql = "select GROUPE.* from GROUPE inner join GROUPE_OFF_PAGE using (ID_GROUPE)
                                                                            where ID_PAGE=" . $oPage->getID() . " order by GRP_LIBELLE";
                                                                } else {
                                                                    $sql = "select GROUPE.* from GROUPE inner join REVISION_GROUPE using (ID_GROUPE)
                                                                            inner join REVISION on (REVISION_GROUPE.ID_REVISION = REVISION.ID_REVISION)
                                                                            where REVISION.ID_REVISION = " . $revIdselected . " and ID_PAGE=" . $oPage->getID() . " order by GRP_LIBELLE";
                                                                }
                                                                $notIn = 'not in (-1';

                                                                foreach ($dbh->query($sql) as $rowTemp) {
                                                                    $notIn .= ',' . $rowTemp['ID_GROUPE'];?>
                                                                    <option value="<?php echo $rowTemp['ID_GROUPE']?>"><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></option>
                                                                <?php }

                                                                $notIn .= ')'; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input readonly="readonly" type="button" name="Button" value="&lt;&lt;">
                                                            <input readonly="readonly" type="button" name="Button2" value="&gt;&gt;">
                                                        </td>
                                                        <td>
                                                            <select name="ID_GROUPE_ALL[]" id="ID_GROUPE_ALL" size="6" multiple disabled="disabled">
                                                                <?php $sql = "select distinct(GROUPE.ID_GROUPE), GRP_LIBELLE from GROUPE
                                                                              left join GROUPE_SITE using(ID_GROUPE)
                                                                              where GROUPE.ID_GROUPE $notIn and (GROUPE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " or GROUPE_SITE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . ")
                                                                              order by GRP_LIBELLE";

                                                                foreach ($dbh->query($sql) as $rowTemp) {?>
                                                                    <option value="<?php echo $rowTemp['ID_GROUPE']?>"><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td style="width: 40%;">
                                            </td>
                                        </tr>
                                    </table>
                                    <?php } ?>
                                </fieldset>

                                <fieldset class="tab" id="infos_onglet_referencement">
                                    <legend><?php echo  gettext('OngletReferencement')?></legend>
                                    <table>
                                        <tr>
                                            <th style="width: 20%;"><label><?php echo gettext('Title')?></label></th>
                                            <td style="width: 60%;">
                                                <span><?php echo secureInput($row['PAG_TITRE_REFERENCEMENT'])?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="width: 20%;"><label><?php echo gettext('Metadescription')?></label></th>
                                            <td style="width: 60%;">
                                                <span><?php echo secureInput($row['PAG_METADESCRIPTION'])?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label><?php echo gettext('URL principale')?></label></th>
                                            <td><span><?php echo secureInput($row['PAG_URLREWRITING'])?></span></td>
                                        </tr>
                                        <tr>
                                            <th><label><?php echo gettext('Frequence')?></label></th>
                                            <td>
                                                <span><?php echo secureInput($row['PAG_GOOGLEFREQUENCE'])?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label><?php echo gettext('Priorite')?></label></th>
                                            <td>
                                                <span><?php echo secureInput($row['PAG_GOOGLEPRIORITE'])?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
