<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_commentaire.php';
require CLASS_DIR . 'class.Link.php';
require CLASS_DIR . 'class.Editor.php';
require_once CLASS_DIR . 'class.CMSMailer.php';
require_once CLASS_DIR . 'class.db_page.php';
$dbh = DB::getInstance();

if (isset($_POST['param'])) {
    CMS::checkAccess(new Module('MOD_COMMENTAIRE'), array('PRO_ROOT_SITE'));

    $sql = "select count(ID_COMMENTAIRE_PARAM) from COMMENTAIRE_PARAM where SIT_CODE = ".$dbh->quote(CMS::getCurrentSite()->getID());
    $cRes = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);

    if ($cRes < 1) {

        $stmt = $dbh->prepare('insert into COMMENTAIRE_PARAM (
            SIT_CODE,
            CPA_TYPEMODERATION,
            CPA_MES_REMERCIEMENT,
            CPA_LIBELLELIEN,
            CPA_EMAILNOTIFICATION,
            CPA_COMMENTAIREVALIDE,
            CPA_COMMENTAIREREFUS,
            CPA_AFFICHAGE_DEFAUT
            ) values (
            :SIT_CODE,
            :CPA_TYPEMODERATION,
            :CPA_MES_REMERCIEMENT,
            :CPA_LIBELLELIEN,
            :CPA_EMAILNOTIFICATION,
            :CPA_COMMENTAIREVALIDE,
            :CPA_COMMENTAIREREFUS,
            :CPA_AFFICHAGE_DEFAUT
            )');
        $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
        $stmt->bindValue(':CPA_TYPEMODERATION', intval($_POST['CPA_TYPEMODERATION']), PDO::PARAM_INT);
        $stmt->bindValue(':CPA_MES_REMERCIEMENT', $_POST['CPA_MES_REMERCIEMENT'], PDO::PARAM_STR);
        $stmt->bindValue(':CPA_LIBELLELIEN', $_POST['CPA_LIBELLELIEN'], PDO::PARAM_INT);
        $stmt->bindValue(':CPA_EMAILNOTIFICATION', intval($_POST['CPA_EMAILNOTIFICATION']), PDO::PARAM_INT);
        $stmt->bindValue(':CPA_COMMENTAIREVALIDE', $_POST['CPA_COMMENTAIREVALIDE'], PDO::PARAM_STR);
        $stmt->bindValue(':CPA_COMMENTAIREREFUS', $_POST['CPA_COMMENTAIREREFUS'], PDO::PARAM_STR);
        $stmt->bindValue(':CPA_AFFICHAGE_DEFAUT', intval($_POST['CPA_AFFICHAGE_DEFAUT']), PDO::PARAM_INT);

        $stmt->execute();
        $idtf = $dbh->lastInsertID();
        Editor::updateContent($_POST['CPA_MES_DEPOT'], 'COMMENTAIRE_PARAM', 'CPA_MES_DEPOT', 'ID_COMMENTAIRE_PARAM', $idtf);
        Editor::updateContent($_POST['CPA_SIGNATUREMAIL'], 'COMMENTAIRE_PARAM', 'CPA_SIGNATUREMAIL', 'ID_COMMENTAIRE_PARAM', $idtf);

        $oComParam = new CommentaireParametrage($idtf);
        $oComParam->attachLiaison();

        setMsg(gettext('INSERT_OK'));

    } else {

        $stmt = $dbh->prepare("update COMMENTAIRE_PARAM set
            CPA_TYPEMODERATION=:CPA_TYPEMODERATION,
            CPA_MES_REMERCIEMENT=:CPA_MES_REMERCIEMENT,
            CPA_LIBELLELIEN=:CPA_LIBELLELIEN,
            CPA_EMAILNOTIFICATION=:CPA_EMAILNOTIFICATION,
            CPA_COMMENTAIREVALIDE=:CPA_COMMENTAIREVALIDE,
            CPA_COMMENTAIREREFUS=:CPA_COMMENTAIREREFUS,
            CPA_AFFICHAGE_DEFAUT=:CPA_AFFICHAGE_DEFAUT
            where SIT_CODE=".$dbh->quote(CMS::getCurrentSite()->getID()));
        $stmt->bindValue(':CPA_TYPEMODERATION', intval($_POST['CPA_TYPEMODERATION']), PDO::PARAM_INT);
        $stmt->bindValue(':CPA_MES_REMERCIEMENT', $_POST['CPA_MES_REMERCIEMENT'], PDO::PARAM_STR);
        $stmt->bindValue(':CPA_LIBELLELIEN', $_POST['CPA_LIBELLELIEN'], PDO::PARAM_INT);
        $stmt->bindValue(':CPA_EMAILNOTIFICATION', intval($_POST['CPA_EMAILNOTIFICATION']), PDO::PARAM_INT);
        $stmt->bindValue(':CPA_COMMENTAIREVALIDE', $_POST['CPA_COMMENTAIREVALIDE'], PDO::PARAM_STR);
        $stmt->bindValue(':CPA_COMMENTAIREREFUS', $_POST['CPA_COMMENTAIREREFUS'], PDO::PARAM_STR);
        $stmt->bindValue(':CPA_AFFICHAGE_DEFAUT', intval($_POST['CPA_AFFICHAGE_DEFAUT']), PDO::PARAM_INT);

        $stmt->execute();

        $sql = "select ID_COMMENTAIRE_PARAM from COMMENTAIRE_PARAM where SIT_CODE = ".$dbh->quote(CMS::getCurrentSite()->getID());
        $idtf = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);

        Editor::updateContent($_POST['CPA_MES_DEPOT'], 'COMMENTAIRE_PARAM', 'CPA_MES_DEPOT', 'ID_COMMENTAIRE_PARAM', $idtf);
        Editor::updateContent($_POST['CPA_SIGNATUREMAIL'], 'COMMENTAIRE_PARAM', 'CPA_SIGNATUREMAIL', 'ID_COMMENTAIRE_PARAM', $idtf);

        setMsg(gettext('UPDATE_OK'));

    }

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('Location:' . SERVER_ROOT . 'cms/cms_commentaireParam.php?idtf=' . $idtf);
    exit();
} elseif (isset($_POST['Update'])) {

    $oExterne = new Commentaire($_POST['idtf']);
    $etatPrec = $oExterne->getField('COM_ETAT');
    $oExterne->checkAuthorized();

    $oSiteParametrage = CommentaireParametrage::getParametrageForSite();
    $paramCommentaire = $oSiteParametrage->getFields();

    //on s'assure que lutilisateur a le bon droit pour modifier le commentaire
    $aTypeInfo = $oExterne->getTypeInfo();
    $oMdlCommentaire = new Module('MOD_COMMENTAIRE');
    CMS::checkAccess($oMdlCommentaire, array($aTypeInfo['PRO_CODE']));

    $stmt = $dbh->prepare('update COMMENTAIRE set
        COM_MESSAGE=:COM_MESSAGE,
        COM_ETAT=:COM_ETAT
        where ID_COMMENTAIRE = :idtf');

    $stmt->bindValue(':COM_MESSAGE', $_POST['COM_MESSAGE'], PDO::PARAM_STR);
    $stmt->bindValue(':COM_ETAT', $_POST['COM_ETAT'], PDO::PARAM_STR);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    if (($_POST['COM_ETAT'] <> $etatPrec) && ($paramCommentaire['CPA_EMAILNOTIFICATION'] == 1)) {
        $oCible = $oExterne->getCible();

        if ($_POST['COM_ETAT'] == 'VALIDE') {
            if (!$url = $oCible->getURLCommentaire()) {
                $url = SERVER_ROOT;
            }
            if (strpos($url, '://') === false) {
                $url = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
            }
            $oMail = new CMSMailer('EMT_COMMENTAIRE_VALIDE');
            $oMail->replace('[LIEN_COMMENTAIRE]', '<a href="'.$url.'">'.secureInput(CMS::getCurrentSite()->getField('SIT_TITLE')).'</a>');
            $oMail->replace('[MESSAGE_VALIDATION_DU_SITE]', secureInput($paramCommentaire['CPA_COMMENTAIREVALIDE']));
            $oMail->replace('[PARAMETRE_SIGNATURE_DU_SITE]', $paramCommentaire['CPA_SIGNATUREMAIL']);
            $oMail->AddAddress($oExterne->getField('COM_MAIL'));
            $oMail->send();
        } elseif ($_POST['COM_ETAT'] == 'REFUS') {
            if (!$url = $oCible->getURLCommentaire()) {
                $url = SERVER_ROOT;
            }
            if (strpos($url, '://') === false) {
                $url = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
            }
            $oMail = new CMSMailer('EMT_COMMENTAIRE_REFUS');
            $oMail->replace('[LIEN_COMMENTAIRE]', '<a href="'.$url.'">'.secureInput(CMS::getCurrentSite()->getField('SIT_TITLE')).'</a>');
            $oMail->replace('[MESSAGE_REFUS_DU_SITE]', secureInput($paramCommentaire['CPA_COMMENTAIREREFUS']));
            $oMail->replace('[PARAMETRE_SIGNATURE_DU_SITE]', $paramCommentaire['CPA_SIGNATUREMAIL']);
            $oMail->AddAddress($oExterne->getField('COM_MAIL'));
            $oMail->send();
        }
    }

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/cms_commentaire.php?idtf=' . $oExterne->getID());
    exit();

} elseif (isset($_GET['Delete'])) {

    $oDelete = new Commentaire(intval($_GET['Delete']));
    if ($oDelete && $oDelete->exist()) {
        $oDelete->checkAuthorized();
        //on s'assure que lutilisateur a le bon droit pour supprimer le commentaire
        $aTypeInfo = $oDelete->getTypeInfo();
        $oMdlCommentaire = new Module('MOD_COMMENTAIRE');
        CMS::checkAccess($oMdlCommentaire, array($aTypeInfo['PRO_CODE']));
        $oDelete->delete();

        // Purge du cache de l'ensemble du site
        Page::clearCache();

        setMsg(gettext('DELETE_OK'));
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_commentaireListe.php');
    exit();
}
