<?php
//$retour est définie dans la class paragraphe, il faut la compléter
$oModule = new Module('MOD_ENSAVOIRPLUS');

if ($this->isInherited() || $this->getField('TPL_CODE') == 'TPL_PARTAGE') {
    $modeParagraphe = $this->getMode();
    if($this->getField('TPL_CODE') == 'TPL_PARTAGE') $modeParagraphe = 'ON_';

    $sqlDocsAssocies = "select WEBOTHEQUE.*, LIA_TEXT from LIAISON_WEBOTHEQUE INNER JOIN WEBOTHEQUE ON (LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE = WEBOTHEQUE.ID_WEBOTHEQUE) WHERE LIA_CODE = '".$modeParagraphe."PARAGRAPHE' and ID_LIAISON = ".$this->getField('PAR_TPL_IDENTIFIANT')." and WBT_CODE = 'WBT_DOCUMENT' and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aDocsAssocies   = $this->dbh->query($sqlDocsAssocies)->fetchAll(PDO::FETCH_ASSOC);
    $sqlLiensExternesAssocies = "select WEBOTHEQUE.*, LIA_TEXT from LIAISON_WEBOTHEQUE INNER JOIN WEBOTHEQUE ON (LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE = WEBOTHEQUE.ID_WEBOTHEQUE) WHERE LIA_CODE = '".$modeParagraphe."PARAGRAPHE' and ID_LIAISON = ".$this->getField('PAR_TPL_IDENTIFIANT')." and WBT_CODE = 'WBT_LIENEXTERNE' and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aLiensExternesAssocies   = $this->dbh->query($sqlLiensExternesAssocies)->fetchAll(PDO::FETCH_ASSOC);
    $sqlPagesAssocies = "select ".$this->getMode()."PAGE.*, LIA_TEXT from LIAISON_PAGE INNER JOIN ".$this->getMode()."PAGE ON (LIAISON_PAGE.ID_PAGE = ".$this->getMode()."PAGE.ID_PAGE) WHERE LIA_CODE = '".$modeParagraphe."PARAGRAPHE' and ID_LIAISON = ".$this->getField('PAR_TPL_IDENTIFIANT')." and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aPagesAssocies   = $this->dbh->query($sqlPagesAssocies)->fetchAll(PDO::FETCH_ASSOC);
} elseif ($this instanceof Paragraphe_Revision) {
    $sqlDocsAssocies = "select WEBOTHEQUE.*, LIA_TEXT from LIAISON_WEBOTHEQUE INNER JOIN WEBOTHEQUE ON (LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE = WEBOTHEQUE.ID_WEBOTHEQUE) WHERE LIA_CODE = 'REVISION_PARAGRAPHE' and ID_LIAISON = ".$this->getField('ID_REVISIONPARAGRAPHE')." and WBT_CODE = 'WBT_DOCUMENT' and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aDocsAssocies   = $this->dbh->query($sqlDocsAssocies)->fetchAll(PDO::FETCH_ASSOC);
    $sqlLiensExternesAssocies = "select WEBOTHEQUE.*, LIA_TEXT from LIAISON_WEBOTHEQUE INNER JOIN WEBOTHEQUE ON (LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE = WEBOTHEQUE.ID_WEBOTHEQUE) WHERE LIA_CODE = 'REVISION_PARAGRAPHE' and ID_LIAISON = ".$this->getField('ID_REVISIONPARAGRAPHE')." and WBT_CODE = 'WBT_LIENEXTERNE' and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aLiensExternesAssocies   = $this->dbh->query($sqlLiensExternesAssocies)->fetchAll(PDO::FETCH_ASSOC);
    $sqlPagesAssocies = "select ".$this->getMode()."PAGE.*, LIA_TEXT from LIAISON_PAGE INNER JOIN ".$this->getMode()."PAGE ON (LIAISON_PAGE.ID_PAGE = ".$this->getMode()."PAGE.ID_PAGE) WHERE LIA_CODE = 'REVISION_PARAGRAPHE' and ID_LIAISON = ".$this->getField('ID_REVISIONPARAGRAPHE')." and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aPagesAssocies   = $this->dbh->query($sqlPagesAssocies)->fetchAll(PDO::FETCH_ASSOC);

} else {
    $sqlDocsAssocies = "select WEBOTHEQUE.*, LIA_TEXT from LIAISON_WEBOTHEQUE INNER JOIN WEBOTHEQUE ON (LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE = WEBOTHEQUE.ID_WEBOTHEQUE) WHERE LIA_CODE = '".$this->getMode()."PARAGRAPHE' and ID_LIAISON = ".$this->getID()." and WBT_CODE = 'WBT_DOCUMENT' and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aDocsAssocies   = $this->dbh->query($sqlDocsAssocies)->fetchAll(PDO::FETCH_ASSOC);
    $sqlLiensExternesAssocies = "select WEBOTHEQUE.*, LIA_TEXT from LIAISON_WEBOTHEQUE INNER JOIN WEBOTHEQUE ON (LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE = WEBOTHEQUE.ID_WEBOTHEQUE) WHERE LIA_CODE = '".$this->getMode()."PARAGRAPHE' and ID_LIAISON = ".$this->getID()." and WBT_CODE = 'WBT_LIENEXTERNE' and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aLiensExternesAssocies   = $this->dbh->query($sqlLiensExternesAssocies)->fetchAll(PDO::FETCH_ASSOC);
    $sqlPagesAssocies = "select ".$this->getMode()."PAGE.*, LIA_TEXT from LIAISON_PAGE INNER JOIN ".$this->getMode()."PAGE ON (LIAISON_PAGE.ID_PAGE = ".$this->getMode()."PAGE.ID_PAGE) WHERE LIA_CODE = '".$this->getMode()."PARAGRAPHE' and ID_LIAISON = ".$this->getID()." and LIA_TYPE != 'RTE' order by LIA_ORDRE";
    $aPagesAssocies   = $this->dbh->query($sqlPagesAssocies)->fetchAll(PDO::FETCH_ASSOC);
}
if (count($aDocsAssocies) > 0 || count($aLiensExternesAssocies) > 0 || count($aPagesAssocies) > 0 ) {
    require_once CLASS_DIR . 'class.db_webotheque.php';
    $i = 0;
    $retour .= '
    <div class="enSavoirPlusParagraphe">
        <h3>En savoir plus</h3>
        <ul>';
        foreach ($aDocsAssocies as $unDocAssoc) {
            $oDocTmp = new Webo_DOCUMENT($unDocAssoc['ID_WEBOTHEQUE']);
            $oDocTmp->setFields($unDocAssoc);
            $retour .= '
            <li>
                <a ' . $oDocTmp->getAnchor() . '>'
                    . ((empty($unDocAssoc['LIA_TEXT']))? encode($unDocAssoc['WEB_LIBELLE']) : encode($unDocAssoc['LIA_TEXT'])) .
                '</a>
            </li>';
        }

        foreach ($aPagesAssocies as $unePageAssoc) {
            $oPageTmpAssoc = new Page($unePageAssoc['ID_PAGE'], $this->getMode());
            if ($oPageTmpAssoc->exist()) {
                $oPageTmpAssoc->setFields($unePageAssoc);
                $isHorsLigne = false;
                if ($this->getMode() == 'OFF_') {
                    //si la page est hors ligne
                    if ($oPageTmpAssoc->getField('PST_CODE') == 'PST_HORSLIGNE') {
                        $isHorsLigne = true;
                    }
                }
                if (!$oPageTmpAssoc->isForbidden() && $oPageTmpAssoc->checkAuthorized(false) && !$isHorsLigne) {
                    $retour .= '<li><a href="' . $oPageTmpAssoc->getURL() . '">';
                    $retour .= empty($unePageAssoc['LIA_TEXT'])? encode($unePageAssoc['PAG_TITRE_MENU']) : encode($unePageAssoc['LIA_TEXT']);
                    $retour .= '</a></li>';
                }
            }
        }

        foreach ($aLiensExternesAssocies as $unLienExtAssoc) {
            $oLienexterneTmp = new Webo_LIENEXTERNE($unLienExtAssoc['ID_WEBOTHEQUE']);
            $oLienexterneTmp->setFields($unLienExtAssoc);
            $retour .= '<li><a href="' . $oLienexterneTmp->getField('WEB_CHEMIN') . '" onclick="window.open(this.href);return false;" title="' . $oModule->i18n('esp_nouvelle_fenetre') . '">';
            $retour .= empty($unLienExtAssoc['LIA_TEXT']) ? encode($unLienExtAssoc['WEB_LIBELLE']) : encode($unLienExtAssoc['LIA_TEXT']);
            $retour .= '</a></li>';
        }

        $retour .= '</ul>

    </div>';
}
