<?php
function pre_PST_ENLIGNE($oPageOff, $idsPageToBeModified = array(), $majDate = 0)
{
    if (!$oPageOff->exist()) return false;

    $aIdsParent = $oPageOff->getParentsID();

    //on enleve les ids des pages qui sont aussi a mettre a jour
    $aTempIdsParents = array();
    if (count($aIdsParent) > 0 && count($idsPageToBeModified) > 0 ) {
        foreach ($aIdsParent as $idParent) {
            if (!in_array($idParent, $idsPageToBeModified)) {
                $aTempIdsParents[] = $idParent;
            }
        }

        $aIdsParent = $aTempIdsParents;
        unset($aTempIdsParents);
    }

    // On verifie que les parents de cette page existent sur le site ON
    if (count($aIdsParent) > 0) {
        $dbh = DB :: getInstance();
        $sql = "select count(ID_PAGE) from ON_PAGE where ID_PAGE in (".implode(',', $aIdsParent).")";

        if ($dbh->query($sql)->fetchColumn() < count($aIdsParent) ) {
            return false;
        }
    }

    return ($majDate > 0)? ($majDate < time()) : ($oPageOff->getField('PAG_DATEONLINE') < time());
    //return ($oPageOff->getField('PAG_DATEONLINE') < time());
}

function pre_PST_HORSLIGNE($oPageOff, $majEnfants = false, $majDate = 0)
{
    if (!$oPageOff->exist()) return false;

    // Si pas de maj enfants, on verifie si cette page a une page fille sur le site ON
    // Et que les dates sont valides
    if (!$majEnfants && $oPageOff->hasOnlineChildren()) {
        return false;
    }

    return ($majDate > 0)? ($majDate < time()) : ($oPageOff->getField('PAG_DATEOFFLINE') < time());
    //return ($oPageOff->getField('PAG_DATEOFFLINE') < time());
}

function post_PST_ENLIGNE($oPageOff, $PST_CODE, $dummy = null, $noPreTest = false)
{
    if (!$noPreTest) {
       if (!pre_PST_ENLIGNE($oPageOff)) {
            return false;
        }
    }

    require_once CLASS_DIR . 'class.Link.php';
    $dbh = DB :: getInstance();
    $oPageOn = new Page($oPageOff->getID(), 'ON_');

    $oPageOn->delete(true);

    // on conserve les dates on et off originales + modifie l'état
    $sql = "update OFF_PAGE set PAG_DATEONLINE_ON=PAG_DATEONLINE, PAG_DATEOFFLINE_ON=PAG_DATEOFFLINE, PST_CODE=" . $dbh->quote($PST_CODE) . " where ID_PAGE=" . $oPageOff->getID();
    $dbh->exec($sql);

    // copie de la page OFF=>ON
    $sql = "insert into ON_PAGE (ID_PAGE, SIT_CODE, ID_WEBOTHEQUE_IMAGE, ID_WEBOTHEQUE_LIENEXTERNE, ID_PAGE_REDIRECT, ID_PAGE_CANONICAL, PGS_CODE, PSS_CODE, ID_STYLEDYNAMIQUE, PAG_IDPERE, PAG_TITRE, PAG_TITRE_MENU, PAG_TITRE_REFERENCEMENT, PAG_TITLE, PAG_ACCROCHE, PAG_METADESCRIPTION, PAG_HEAD, PAG_POIDS, PAG_VISIBLE_MENU, PAG_EXCLURECHERCHE, PAG_NOINDEX, PAG_MASQUERGAUCHE, PAG_MASQUERDROITE, PAG_MOTCLE1, PAG_MOTCLE2, PAG_MOTCLE3, PAG_MOTCLE4, PAG_MOTCLE5, PAG_URLREWRITING, PAG_GOOGLEFREQUENCE, PAG_GOOGLEPRIORITE, PAG_DATEMODIFICATION, PAG_DATEMISEENLIGNE, PAG_COMMENTAIREACTIF, PAG_HTTPS, PAG_CACHE, PAG_DATEMISEAJOUR)
                          select ID_PAGE, SIT_CODE, ID_WEBOTHEQUE_IMAGE, ID_WEBOTHEQUE_LIENEXTERNE, ID_PAGE_REDIRECT, ID_PAGE_CANONICAL, PGS_CODE, PSS_CODE, ID_STYLEDYNAMIQUE, PAG_IDPERE, PAG_TITRE, PAG_TITRE_MENU, PAG_TITRE_REFERENCEMENT, PAG_TITLE, PAG_ACCROCHE, PAG_METADESCRIPTION, PAG_HEAD, PAG_POIDS, PAG_VISIBLE_MENU, PAG_EXCLURECHERCHE, PAG_NOINDEX, PAG_MASQUERGAUCHE, PAG_MASQUERDROITE, PAG_MOTCLE1, PAG_MOTCLE2, PAG_MOTCLE3, PAG_MOTCLE4, PAG_MOTCLE5, PAG_URLREWRITING, PAG_GOOGLEFREQUENCE, PAG_GOOGLEPRIORITE, PAG_DATEMODIFICATION, "  .  time()  .   ", PAG_COMMENTAIREACTIF, PAG_HTTPS, PAG_CACHE, PAG_DATEMISEAJOUR from OFF_PAGE where ID_PAGE=" . $oPageOff->getID();
    $dbh->exec($sql);

    // copie des paragraphes OFF=>ON
    $sql = "insert into ON_PARAGRAPHE (ID_PARAGRAPHE, ID_PAGE, PRT_CODE, PRS_CODE, PRS_WIDTH, TPL_CODE, PAR_TPL_IDENTIFIANT, PAR_POIDS, PAR_TITRE, PAR_CONTENU, PAR_CONTENUPARSE, PAR_CONTENUTEXTE, PAR_APARSER, PAR_COLONNE, PAR_HERITABLE, PAR_MOBILEHIDDEN)
                                select ID_PARAGRAPHE, ID_PAGE, PRT_CODE, PRS_CODE, PRS_WIDTH, TPL_CODE, PAR_TPL_IDENTIFIANT, PAR_POIDS, PAR_TITRE, PAR_CONTENU, PAR_CONTENUPARSE, PAR_CONTENUTEXTE,     1      , PAR_COLONNE, PAR_HERITABLE, PAR_MOBILEHIDDEN from OFF_PARAGRAPHE where ID_PAGE=" . $oPageOff->getID();
    $dbh->exec($sql);

    // la page doit être rechargée (info PAGE + PARAGRAPHE ont changé)
    $oPageOn = new Page($oPageOff->getID(), 'ON_');

    // on copie les poids OFF=>ON des frères et soeurs de la page
    if ($oPageParent = end($oPageOn->getParents())) {
        $sql = "update ON_PAGE inner join OFF_PAGE on ON_PAGE.ID_PAGE=OFF_PAGE.ID_PAGE set
            ON_PAGE.PAG_POIDS = OFF_PAGE.PAG_POIDS
            where ON_PAGE.PAG_IDPERE=" . $oPageParent->getID();
        $dbh->exec($sql);
    }

    // on parcourt les parents pour les nouveaux paragraphes héritables non pris en compte
    // ainsi que les enfant pour ceux qui ne le sont plus (cas d'un déplacement de page en OFF)
    // on les prend en sens inverse car le dernier inséré se retrouve en haut
    $sql = "select * from ON_PARAGRAPHE where PAR_HERITABLE <> 0 and ID_PAGE in (" . implode(',', $oPageOn->getParentsID(true)). ") order by PAR_POIDS desc";
    $aParagrapheExistant = array();
    foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $row) {
        $aParagrapheExistant[] = $row['ID_PARAGRAPHE'];
        $oParagraphe = new Paragraphe($row['ID_PARAGRAPHE'], 'ON_');
        $oParagraphe->setFields($row);
        $oParagraphe->inherit();
    }
    $sql = "select distinct(PAR_TPL_IDENTIFIANT) from ON_PARAGRAPHE where TPL_CODE='TPL_HERITAGE'
        and ID_PAGE in (" . implode(',', array_merge($oPageOn->getChildrenID(), array($oPageOn->getID()))). ")";
    if (sizeof($aParagrapheExistant) > 0) {
        $sql .= " and PAR_TPL_IDENTIFIANT not in (" . implode(',', $aParagrapheExistant) . ")";
    }
    foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_COLUMN) as $idtf) {
        $oParagraphe = new Paragraphe($idtf, 'ON_');
        //seulement si le paragraphe existe et que le paragraphe héritable n'est pas dans la descendance de la page
        if ($oParagraphe->exist() && !in_array($oParagraphe->getField('ID_PAGE'), $oPageOn->getChildrenID())) {
            $oParagraphe->deleteInherited($oPageOn);
        }
    }

    //Il faut republier les paragraphes des autres pages en OFF qui ont des paragraphes partagés liés à la page en cours de publication
    //Pour cela, on parcourt les paragraphes de la page qui ont des paragraphes partagés liés
    //Ces paragraphes partagés doivent avoir déjà une version de la page en ligne
    //Il ne faut pas prendre en compte les paragraphe partagés de la page en cours qui sont déjà publiés
    $sql = "select OFF_PARAGRAPHE.ID_PARAGRAPHE from OFF_PARAGRAPHE
        inner join ON_PARAGRAPHE on OFF_PARAGRAPHE.PAR_TPL_IDENTIFIANT = ON_PARAGRAPHE.ID_PARAGRAPHE
        inner join ON_PAGE on OFF_PARAGRAPHE.ID_PAGE = ON_PAGE.ID_PAGE
        where OFF_PARAGRAPHE.TPL_CODE = 'TPL_PARTAGE'
        AND ON_PARAGRAPHE.ID_PAGE =".$oPageOff->getID()." AND OFF_PARAGRAPHE.ID_PAGE!=".$oPageOff->getID();
    //Pour chaque paragraphe lié à un partage sur la page en cours, on regénère le paragraphe partagé équivalent (Copie des infos en ON)
    foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_COLUMN) as $idParagraphe) {
        // copie des paragraphes partagés OFF=>ON
        $sql = "replace into ON_PARAGRAPHE (ID_PARAGRAPHE, ID_PAGE, PRT_CODE, PRS_CODE, PRS_WIDTH, TPL_CODE, PAR_TPL_IDENTIFIANT, PAR_POIDS, PAR_TITRE, PAR_CONTENU, PAR_CONTENUPARSE, PAR_CONTENUTEXTE, PAR_APARSER, PAR_COLONNE, PAR_HERITABLE, PAR_MOBILEHIDDEN)
                                     select ID_PARAGRAPHE, ID_PAGE, PRT_CODE, PRS_CODE, PRS_WIDTH, TPL_CODE, PAR_TPL_IDENTIFIANT, PAR_POIDS, PAR_TITRE, PAR_CONTENU, PAR_CONTENUPARSE, PAR_CONTENUTEXTE,     1      , PAR_COLONNE, PAR_HERITABLE, PAR_MOBILEHIDDEN from OFF_PARAGRAPHE where ID_PARAGRAPHE=" . $idParagraphe;
        $dbh->exec($sql);
    }

    // copie des liaisons (obligatoire à cause des liens hors éditeur qui ne seront pas reparsés)
    $sql = "select LIA_CODE, ID_LIAISON, ID_PAGE, ID_PARAGRAPHE, LIA_TYPE, LIA_TEXT from LIAISON_PAGE where
        (LIA_CODE='OFF_PARAGRAPHE' and ID_LIAISON in (select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PAGE=" . $oPageOff->getID() . "))
        or (LIA_CODE='OFF_PAGE' and ID_LIAISON=" . $oPageOff->getID() . ") order by LIA_ORDRE asc";
    foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $rowTemp) {
        Link::insertPage(($rowTemp['LIA_CODE'] == 'OFF_PARAGRAPHE') ? 'ON_PARAGRAPHE' : 'ON_PAGE', $rowTemp['ID_LIAISON'], $rowTemp['ID_PAGE'], $rowTemp['ID_PARAGRAPHE'], null, $rowTemp['LIA_TYPE'], '', $rowTemp['LIA_TEXT']);
    }
    $sql = "select LIA_CODE, ID_LIAISON, ID_WEBOTHEQUE, LIA_TYPE, LIA_TEXT from LIAISON_WEBOTHEQUE where
        (LIA_CODE='OFF_PARAGRAPHE' and ID_LIAISON in (select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PAGE=" . $oPageOff->getID() . "))
        or (LIA_CODE='OFF_PAGE' and ID_LIAISON=" . $oPageOff->getID() . ") order by LIA_ORDRE asc";
    foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $rowTemp) {
        Link::insertWebotheque(($rowTemp['LIA_CODE'] == 'OFF_PARAGRAPHE') ? 'ON_PARAGRAPHE' : 'ON_PAGE', $rowTemp['ID_LIAISON'], $rowTemp['ID_WEBOTHEQUE'], null, $rowTemp['LIA_TYPE'], '', $rowTemp['LIA_TEXT']);
    }
    $sql = "delete from LIAISON_THEMATIQUE where LIA_CODE='ON_PAGE' and ID_LIAISON=" . $oPageOff->getID() ;
    $dbh->exec($sql);
    $sql = "select ID_THEMATIQUE from LIAISON_THEMATIQUE where LIA_CODE='OFF_PAGE' and ID_LIAISON=" . $oPageOff->getID() ;
    foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $rowTemp) {
        Link :: insertThematique('ON_PAGE',$oPageOn->getID(),$rowTemp['ID_THEMATIQUE']);
    }

    $sql = "insert into GROUPE_ON_PAGE (ID_GROUPE, ID_PAGE) select ID_GROUPE, ID_PAGE from GROUPE_OFF_PAGE where ID_PAGE=" . $oPageOff->getID();
    $dbh->exec($sql);

    //Remise en place des anciens liens vers cette page suite à une mise hors ligne
    $sql = "update ON_PARAGRAPHE set PAR_APARSER=1 where ID_PARAGRAPHE in (
                select ID_LIAISON from LIAISON_PAGE
                where LIA_CODE='OFF_PARAGRAPHE' and ID_PAGE=" . $oPageOff->getID() . ")";
    $dbh->exec($sql);

    return true;
}

function post_PST_HORSLIGNE($oPageOff, $PST_CODE, $majEnfants = false, $noPreTest = false)
{
    if (!$noPreTest) {
        if (!pre_PST_HORSLIGNE($oPageOff, $majEnfants)) {
            return false;
        }
    }
    $dbh = DB :: getInstance();
    $oPageOn = new Page($oPageOff->getID(), 'ON_');

    //Suppression de la page
    $oPageOn->delete();

    // on efface les dates on et off conservées + modifie l'état
    $sql = "update OFF_PAGE set PAG_DATEONLINE_ON=null, PAG_DATEOFFLINE_ON=null, PST_CODE=" . $dbh->quote($PST_CODE) . " where ID_PAGE=" . $oPageOff->getID();
    $dbh->exec($sql);

    return true;
}
