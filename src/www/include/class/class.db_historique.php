<?php

    /**
     * Combinaison des codes d'historiques
     *
     *
     */
    $aHistoriqueAction = array(
        'CREATION' => array (
                        'PAGE'            =>   gettext('creation_page'),
                        'PARAGRAPHE'      =>   gettext('creation_paragraphe'),
                        'REVISION'        =>   gettext('creation_revision'),
                        'UTILISATEUR'	  =>   gettext('creation_utilisateur'),
                        'SITE'	          =>   gettext('creation_site'),
                        'FICHE'		      =>   gettext('creation_fiche'),
                        'FORMULAIRE'	  =>   gettext('creation_formulaire'),
                        'GROUPE'		  =>   gettext('creation_groupe'),
                        'QUESTION'		  =>   gettext('creation_question'),
                        'SITE'		      =>   gettext('creation_site'),
                        'UTILISATEUR'	  =>   gettext('creation_utilisateur')
            ),
        'MODIFICATION' => array (
                        'PAGE'            =>   gettext('modification_page'),
                        'PARAGRAPHE'      =>   gettext('modification_paragraphe'),
                        'REVISION'        =>   gettext('modification_revision'),
                        'WORKFLOW'        =>   gettext('modification_workflow'),
                        'REFERENCEMENT'   =>   gettext('modification_referencement'),
                        'UTILISATEUR'	  =>   gettext('modification_utilisateur'),
                        'SITE'	          =>   gettext('modification_site'),
                        'FICHE'		      =>   gettext('modification_fiche'),
                        'FORMULAIRE'	  =>   gettext('modification_formulaire'),
                        'GROUPE'		  =>   gettext('modification_groupe'),
                        'QUESTION'		  =>   gettext('modification_question'),
                        'SITE'		      =>   gettext('modification_site'),
                        'UTILISATEUR'	  =>   gettext('modification_utilisateur')
                        ),
        'SUPPRESSION' => array (
                        'PAGE'            =>   gettext('suppression_page'),
                        'PARAGRAPHE'      =>   gettext('suppression_paragraphe'),
                        'REVISION'        =>   gettext('suppression_revision'),
                        'UTILISATEUR'	  =>   gettext('suppression_utilisateur'),
                        'FICHE'		      =>   gettext('suppression_fiche'),
                        'FORMULAIRE'	  =>   gettext('suppression_formulaire'),
                        'GROUPE'		  =>   gettext('suppression_groupe'),
                        'QUESTION'		  =>   gettext('suppression_question'),
                        'SITE'		      =>   gettext('suppression_site'),
                        'UTILISATEUR'	  =>   gettext('suppression_utilisateur')
                        )
    );



class Historique
{
    /**
     *
     * Fonction permettant d'historiser les actions d'un module externe
     * @param string $HEX_CODE        : Nom du module à historiser (Ex : 'ACTUALITE')
     * @param string $HIS_IDENTIFIANT : Identifiant du de la fiche du module
     * @param string $HIS_ACTION      : type d'action : CREATION / MODIFICATION / SUPPRESSION d'une fiche du module
     * @param string $HIS_DETAIL      : Détail de l'action si besoin
     */
    static public function historizeExterne($HEX_CODE, $SIT_CODE, $HIS_IDENTIFIANT, $HIS_ACTION, $HIS_DETAIL = '')
    {
        $dbh = DB::getInstance();

        $stmt = $dbh->prepare("insert into HISTORIQUE_EXTERNE (
            SIT_CODE,
            HEX_CODE,
            HIS_IDENTIFIANT,
            ID_HISTORIQUE_UTILISATEUR,
            HIS_ACTION,
            HIS_DETAIL,
            HIS_DATE
            ) values(
            :SIT_CODE,
            :HEX_CODE,
            :HIS_IDENTIFIANT,
            :ID_HISTORIQUE_UTILISATEUR,
            :HIS_ACTION,
            :HIS_DETAIL,
            :HIS_DATE
            )");

        $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':HEX_CODE', $HEX_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_IDENTIFIANT', $HIS_IDENTIFIANT, PDO::PARAM_STR);
        // On peut historiser des modifications sans passer par un utilisateur (ex via un crontab).
        // Dans ce cas, nous n'avons pas les infos sur ledit utilisateur
        $S_ID_HISTORIQUE = '';
        if (isset($_SESSION['S_ID_HISTORIQUE']) && CMS::getCurrentSite() && is_numeric($_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()])) {
             $S_ID_HISTORIQUE = $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()];
        }
        $stmt->bindValue(':ID_HISTORIQUE_UTILISATEUR', (!empty($S_ID_HISTORIQUE)) ? $S_ID_HISTORIQUE : null, PDO::PARAM_INT);
        $stmt->bindValue(':HIS_ACTION', $HIS_ACTION, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DETAIL', $HIS_DETAIL, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DATE', time(), PDO::PARAM_INT);
        $stmt->execute();

        if (isset($_SESSION['S_ID_HISTORIQUE'])) {
            Utilisateur::historizeAction();
        }

        if ($HIS_ACTION == 'SUPPRESSION') {
             $dbh->exec('update HISTORIQUE_EXTERNE
                               set HIS_INFO = ' . $dbh->quote($HIS_DETAIL) . '
                               where HEX_CODE = ' . $dbh->quote($HEX_CODE) . '
                               and HIS_IDENTIFIANT = ' . $dbh->quote($HIS_IDENTIFIANT));
        }
    }

    /**
     *
     * Fonction permettant d'insérer une action sur une fiche utilisateur ou site de l'onglet d'administration
     * @param string $HIS_ACTION      nom de l'action CREATION / MODIFICATION / SUPPRESSION
     * @param string $HIS_TYPE        type sur lequel l'action est effectué (SITE ou UTLISATEUR)
     * @param string $HIS_IDENTIFIANT identifiant du site ou de l'utilisateur
     * @param string $HIS_DETAIL      détail de l'action effectuée
     **/
    static public function historizeAdmin($HIS_ACTION, $HIS_TYPE, $HIS_IDENTIFIANT, $HIS_DETAIL = '')
    {
        $dbh = DB::getInstance();

        $stmt = $dbh->prepare("insert into HISTORIQUE_ADMIN (
            SIT_CODE,
            ID_HISTORIQUE_UTILISATEUR,
            HIS_IDENTIFIANT,
            HIS_ACTION,
            HIS_TYPE,
            HIS_DETAIL,
            HIS_DATE
            ) values(
            :SIT_CODE,
            :ID_HISTORIQUE_UTILISATEUR,
            :HIS_IDENTIFIANT,
            :HIS_ACTION,
            :HIS_TYPE,
            :HIS_DETAIL,
            :HIS_DATE
            )");

        $stmt->bindValue(':SIT_CODE', $HIS_TYPE == 'SITE' ? $HIS_IDENTIFIANT : CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
        // On peut historiser des modifications sans passer par un utilisateur (ex via un crontab).
        // Dans ce cas, nous n'avons pas les infos sur ledit utilisateur
        $S_ID_HISTORIQUE = '';
        if (isset($_SESSION['S_ID_HISTORIQUE']) && CMS::getCurrentSite() && is_numeric($_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()])) {
             $S_ID_HISTORIQUE = $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()];
        }
        $stmt->bindValue(':ID_HISTORIQUE_UTILISATEUR', (!empty($S_ID_HISTORIQUE)) ? $S_ID_HISTORIQUE : null, PDO::PARAM_INT);
        $stmt->bindValue(':HIS_IDENTIFIANT', $HIS_IDENTIFIANT, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_ACTION', $HIS_ACTION, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_TYPE', $HIS_TYPE, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DETAIL', $HIS_DETAIL, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DATE', time(), PDO::PARAM_INT);
        $stmt->execute();

        if (isset($_SESSION['S_ID_HISTORIQUE'])) {
            Utilisateur::historizeAction();
        }

        if (($HIS_TYPE == 'UTILISATEUR') && ($HIS_ACTION == 'SUPPRESSION')) {
            $oUti = new Utilisateur($HIS_IDENTIFIANT);
            if ($oUti && $oUti->exist()) {
                $dbh->exec('update HISTORIQUE_ADMIN  set
                    HIS_UTILISATEUR = ' . $dbh->quote($oUti->getField('UTI_PRENOM') . ' ' . $oUti->getField('UTI_NOM')) . '
                    where HIS_TYPE = \'UTILISATEUR\'
                    and HIS_IDENTIFIANT =' . $oUti->getID());

                $dbh->exec('update HISTORIQUE_UTILISATEUR  set
                    HIS_UTILISATEUR = ' . $dbh->quote($oUti->getField('UTI_PRENOM') . ' ' . $oUti->getField('UTI_NOM')) . '
                    where ID_UTILISATEUR=' . $oUti->getID());
            }
        }
    }

    /**
     * Fonction retournant les informations d'une fiche d'un module
     * @return array  tableau des champs de la fiche module
     * @param  string $HEX_TABLE    nom de la table
     * @param  string $HEX_CHAMP_ID nom de la clé primaire
     * @param  mixed  $IDENTIFIANT  identifiant de la fiche (int, string)
     */
    static public function getInfoModule($HEX_TABLE, $HEX_CHAMP_ID, $IDENTIFIANT) {
        $dbh = DB::getInstance();
        $sql = "select * from " . $HEX_TABLE . " where " . $HEX_CHAMP_ID . " = " . $dbh->quote($IDENTIFIANT);

         if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }
        return false;
    }

    static public function isModuleActif () {
        $dbh = DB::getInstance();
        $sql = "select count(HEX_CODE) from DD_HISTORIQUE_EXTERNE";
        if ($dbh->query($sql)->fetchColumn() > 0) {
            return true;
        }
        return false;
    }
}
