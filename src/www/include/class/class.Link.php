<?php

class Link
{

    /**
     * Tous les liens que l'entité utilise doivent disparaitre (mais pas uniquement les champs contenant un éditeur !!)
     *
     * @access public
     * @param String $LIA_CODE
     *                                     Type de liaison -> référence à DD_LIAISON
     * @param Int    $ID_LIAISON
     *                                     Id de l'élément avec laquelle on souhaite supprimer la liaison
     * @param String $filtreSupplementaire
     *                                     $filtreSupplementaire == 'ALL' : supprime tout, à appeler dans la fonction delete (on peut appeler avec vide si la DE n'a pas de liaison ajax)
     *                                     $filtreSupplementaire == '' (comportement originel par défaut) : supprime ce qui est lié à tiny, à appeler avant un Editor::updateContent
     *                                     $filtreSupplementaire != '' : supprime ce qui est lié à des liaisons ajax, à appeler depuis l'ajax
     */
    public static function delete($LIA_CODE, $ID_LIAISON, $filtreSupplementaire = '')
    {
        $dbh = DB::getInstance();
        $LIA_CODE = $dbh->quote($LIA_CODE);
        $ID_LIAISON = $dbh->quote($ID_LIAISON);
        if ($filtreSupplementaire == 'ALL') {
            $sql = "delete from LIAISON_WEBOTHEQUE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_PAGE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_THEMATIQUE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_EXTERNE where LIA_CODE_FROM=" . $LIA_CODE . " and ID_LIAISON_FROM=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_EXTERNE where LIA_CODE_TO=" . $LIA_CODE . " and ID_LIAISON_TO=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_UTILISATEUR where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
        } elseif ($filtreSupplementaire == '') {
            $sql = "delete from LIAISON_WEBOTHEQUE where LIA_TYPE='RTE' and LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_PAGE where LIA_TYPE='RTE' and LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_THEMATIQUE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
            $sql = "delete from LIAISON_UTILISATEUR where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON;
            $dbh->exec($sql);
        } elseif (preg_match('/ID_WEBOTHEQUE/', $filtreSupplementaire) > 0) {
            $sql = "delete from LIAISON_WEBOTHEQUE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON . " " . $filtreSupplementaire;
            $dbh->exec($sql);
        } elseif (preg_match('/ID_PAGE/', $filtreSupplementaire) > 0) {
            $sql = "delete from LIAISON_PAGE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON . " " . $filtreSupplementaire;
            $dbh->exec($sql);
        } elseif (preg_match('/ID_THEMATIQUE/', $filtreSupplementaire) > 0) {
            $sql = "delete from LIAISON_THEMATIQUE where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON . " " . $filtreSupplementaire;
            $dbh->exec($sql);
        } elseif (preg_match('/ID_UTILISATEUR/', $filtreSupplementaire) > 0) {
            $sql = "delete from LIAISON_UTILISATEUR where LIA_CODE=" . $LIA_CODE . " and ID_LIAISON=" . $ID_LIAISON . " " . $filtreSupplementaire;
            $dbh->exec($sql);
        } elseif (preg_match('/_TO/', $filtreSupplementaire) > 0) {
            $sql = "delete from LIAISON_EXTERNE where LIA_CODE_FROM =" . $LIA_CODE . " and ID_LIAISON_FROM=" . $ID_LIAISON . " " . $filtreSupplementaire;
            $dbh->exec($sql);
        }
    }

    public static function insertWebotheque($LIA_CODE, $ID_LIAISON, $ID_WEBOTHEQUE, $ID_REVISION = null, $LIA_TYPE = 'RTE', $LIA_TEMP = '', $LIA_TEXT = null)
    {
        $dbh = DB::getInstance();

        $filtre = " and LIA_TYPE=" . $dbh->quote($LIA_TYPE);
        if (empty($ID_REVISION)) {
            $ID_REVISION = null;
            $filtre .= " and ID_REVISION is null";
        } else {
            $filtre .= " and ID_REVISION=:ID_REVISION";
        }

        $stmt = $dbh->prepare("select ID_LIAISON_WEBOTHEQUE from LIAISON_WEBOTHEQUE where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and ID_WEBOTHEQUE=:ID_WEBOTHEQUE " . $filtre);
        $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_STR);
        $stmt->bindValue(':ID_WEBOTHEQUE', $ID_WEBOTHEQUE, PDO::PARAM_INT);
        if ($ID_REVISION !== null) {
            $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
        }

        $stmt->execute();
        if (! $stmt->fetch()) {
            if ($LIA_TYPE == 'RTE') {
                $LIA_ORDRE = 0;
            } else {
                $stmt = $dbh->prepare("select max(LIA_ORDRE) from LIAISON_WEBOTHEQUE where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and LIA_TYPE=:LIA_TYPE");
                $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
                $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_STR);
                $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
                $stmt->execute();
                $LIA_ORDRE = intval($stmt->fetchColumn()) + 1;
            }

            $stmt = $dbh->prepare("insert into LIAISON_WEBOTHEQUE (
                LIA_CODE,
                ID_LIAISON,
                ID_WEBOTHEQUE,
                ID_REVISION,
                LIA_ORDRE,
                LIA_TYPE,
                LIA_TEMP,
                LIA_TEXT
                ) values (
                :LIA_CODE,
                :ID_LIAISON,
                :ID_WEBOTHEQUE,
                :ID_REVISION,
                :LIA_ORDRE,
                :LIA_TYPE,
                :LIA_TEMP,
                :LIA_TEXT
                )");
            $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_STR);
            $stmt->bindValue(':ID_WEBOTHEQUE', $ID_WEBOTHEQUE, PDO::PARAM_INT);
            $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_ORDRE', $LIA_ORDRE, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEMP', $LIA_TEMP, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEXT', $LIA_TEXT, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public static function getLiaisonWebotheque($LIA_CODE, $WBT_CODE, $ID_LIAISON, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select ID_LIAISON_WEBOTHEQUE, LIA_TEXT, WEBOTHEQUE.* from LIAISON_WEBOTHEQUE
            inner join WEBOTHEQUE using(ID_WEBOTHEQUE)
            where LIA_TYPE = " . $dbh->quote($LIA_TYPE) . " and LIA_CODE=" . $dbh->quote($LIA_CODE) . " and WBT_CODE=" . $dbh->quote($WBT_CODE) . " and ID_LIAISON = " . $dbh->quote($ID_LIAISON) . "
            order by LIA_ORDRE";

        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function reorderLiaisonWebotheque($LIA_CODE, $WBT_CODE, $ID_LIAISON, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select ID_LIAISON_WEBOTHEQUE from LIAISON_WEBOTHEQUE
            inner join WEBOTHEQUE using(ID_WEBOTHEQUE)
            where LIA_TYPE = " . $dbh->quote($LIA_TYPE) . " and LIA_CODE=" . $dbh->quote($LIA_CODE) . " and WBT_CODE=" . $dbh->quote($WBT_CODE) . " and ID_LIAISON = " . $dbh->quote($ID_LIAISON) . "
            order by LIA_ORDRE";
        $cpt = 1;
        $stmt = $dbh->prepare("update LIAISON_WEBOTHEQUE set
            LIA_ORDRE = :LIA_ORDRE
            where ID_LIAISON_WEBOTHEQUE = :ID_LIAISON_WEBOTHEQUE");
        $stmt->bindParam(':LIA_ORDRE', $cpt, PDO::PARAM_INT);
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_LIAISON_WEBOTHEQUE) {
            $stmt->bindValue(':ID_LIAISON_WEBOTHEQUE', $ID_LIAISON_WEBOTHEQUE, PDO::PARAM_INT);
            $stmt->execute();
            $cpt ++;
        }
    }

    public static function insertExterne($LIA_CODE_FROM, $ID_LIAISON_FROM, $LIA_CODE_TO, $ID_LIAISON_TO, $LIA_TYPE = '', $LIA_TEMP = '', $LIA_TEXT = null)
    {
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("select ID_LIAISON_EXTERNE from LIAISON_EXTERNE where LIA_CODE_FROM=:LIA_CODE_FROM and ID_LIAISON_FROM=:ID_LIAISON_FROM and LIA_CODE_TO=:LIA_CODE_TO and ID_LIAISON_TO=:ID_LIAISON_TO and LIA_TYPE=:LIA_TYPE");
        $stmt->bindValue(':LIA_CODE_FROM', $LIA_CODE_FROM, PDO::PARAM_STR);
        $stmt->bindValue(':ID_LIAISON_FROM', $ID_LIAISON_FROM, PDO::PARAM_STR);
        $stmt->bindValue(':LIA_CODE_TO', $LIA_CODE_TO, PDO::PARAM_STR);
        $stmt->bindValue(':ID_LIAISON_TO', $ID_LIAISON_TO, PDO::PARAM_STR);
        $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);

        $stmt->execute();
        if (! $stmt->fetch()) {

            $stmt = $dbh->prepare("select max(LIA_ORDRE) from LIAISON_EXTERNE where LIA_CODE_FROM=:LIA_CODE_FROM and ID_LIAISON_FROM=:ID_LIAISON_FROM and LIA_CODE_TO =:LIA_CODE_TO and LIA_TYPE=:LIA_TYPE");
            $stmt->bindValue(':LIA_CODE_FROM', $LIA_CODE_FROM, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON_FROM', $ID_LIAISON_FROM, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_CODE_TO', $LIA_CODE_TO, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
            $stmt->execute();
            $LIA_ORDRE = intval($stmt->fetchColumn()) + 1;

            $stmt = $dbh->prepare("insert into LIAISON_EXTERNE(
                LIA_CODE_FROM,
                ID_LIAISON_FROM,
                LIA_CODE_TO,
                ID_LIAISON_TO,
                LIA_ORDRE,
                LIA_TYPE,
                LIA_TEMP,
                LIA_TEXT
                ) values (
                :LIA_CODE_FROM,
                :ID_LIAISON_FROM,
                :LIA_CODE_TO,
                 :ID_LIAISON_TO,
                 :LIA_ORDRE,
                 :LIA_TYPE,
                 :LIA_TEMP,
                 :LIA_TEXT
                )");
            $stmt->bindValue(':LIA_CODE_FROM', $LIA_CODE_FROM, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON_FROM', $ID_LIAISON_FROM, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_CODE_TO', $LIA_CODE_TO, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON_TO', $ID_LIAISON_TO, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_ORDRE', $LIA_ORDRE, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEMP', $LIA_TEMP, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEXT', $LIA_TEXT, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public static function getLiaisonExterne($LIA_CODE_FROM, $LIA_CODE_TO, $ID_LIAISON_FROM, $select = '*', $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select " . $select . ", ID_LIAISON_EXTERNE from LIAISON_EXTERNE le
            inner join " . $LIA_CODE_TO . " l on le.ID_LIAISON_TO = l." . str_replace('DE_', 'ID_', $LIA_CODE_TO) . "
            where le.LIA_CODE_FROM=" . $dbh->quote($LIA_CODE_FROM) . "
            and le.ID_LIAISON_FROM=" . $dbh->quote($ID_LIAISON_FROM) . "
            and le.LIA_CODE_TO=" . $dbh->quote($LIA_CODE_TO) . "
            and le.LIA_TYPE=" . $dbh->quote($LIA_TYPE) . "
            order by le.LIA_ORDRE";

        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getLiaisonExterneReverse($LIA_CODE_FROM, $LIA_CODE_TO, $ID_LIAISON_TO, $select = '*', $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select " . $select . ", ID_LIAISON_EXTERNE from LIAISON_EXTERNE le
            inner join " . $LIA_CODE_FROM . " l on le.ID_LIAISON_FROM = l." . str_replace('DE_', 'ID_', $LIA_CODE_FROM) . "
            where le.LIA_CODE_FROM=" . $dbh->quote($LIA_CODE_FROM) . "
            and le.ID_LIAISON_TO=" . $dbh->quote($ID_LIAISON_TO) . "
            and le.LIA_CODE_TO=" . $dbh->quote($LIA_CODE_TO) . "
            and le.LIA_TYPE=" . $dbh->quote($LIA_TYPE) . "
            order by le.LIA_ORDRE";
        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function reorderLiaisonExterne($LIA_CODE_FROM, $LIA_CODE_TO, $ID_LIAISON_FROM, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $aLIA_CODE_TO = explode('@', $LIA_CODE_TO);
        $LIA_CODE_TO = $aLIA_CODE_TO[0];
        $sql = "select ID_LIAISON_EXTERNE from LIAISON_EXTERNE le
            inner join " . $LIA_CODE_TO . " l on le.ID_LIAISON_TO = l." . str_replace('DE_', 'ID_', $LIA_CODE_TO) . "
            where le.LIA_CODE_FROM=" . $dbh->quote($LIA_CODE_FROM) . "
            and le.ID_LIAISON_FROM=" . $dbh->quote($ID_LIAISON_FROM) . "
            and le.LIA_CODE_TO=" . $dbh->quote($LIA_CODE_TO) . "
            and le.LIA_TYPE=" . $dbh->quote($LIA_TYPE) . "
            order by le.LIA_ORDRE";
        $cpt = 1;
        $stmt = $dbh->prepare("update LIAISON_EXTERNE set
            LIA_ORDRE = :LIA_ORDRE
            where ID_LIAISON_EXTERNE = :ID_LIAISON_EXTERNE");
        $stmt->bindParam(':LIA_ORDRE', $cpt, PDO::PARAM_INT);
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_LIAISON_EXTERNE) {
            $stmt->bindValue(':ID_LIAISON_EXTERNE', $ID_LIAISON_EXTERNE, PDO::PARAM_INT);
            $stmt->execute();
            $cpt ++;
        }
    }

    public static function insertPage($LIA_CODE, $ID_LIAISON, $ID_PAGE, $ID_PARAGRAPHE = null, $ID_REVISION = null, $LIA_TYPE = 'RTE', $LIA_TEMP = '', $LIA_TEXT = null)
    {
        $dbh = DB::getInstance();

        $filtre = " and LIA_TYPE=" . $dbh->quote($LIA_TYPE);
        if (empty($ID_PARAGRAPHE)) {
            $ID_PARAGRAPHE = null;
            $filtre .= " and ID_PARAGRAPHE is null";
        } else {
            $filtre .= " and ID_PARAGRAPHE=:ID_PARAGRAPHE";
        }
        if (empty($ID_REVISION)) {
            $ID_REVISION = null;
            $filtre .= " and ID_REVISION is null";
        } else {
            $filtre .= " and ID_REVISION=:ID_REVISION";
        }
        $stmt = $dbh->prepare("select * from LIAISON_PAGE where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and ID_PAGE=:ID_PAGE " . $filtre);
        $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_STR);
        $stmt->bindValue(':ID_PAGE', $ID_PAGE, PDO::PARAM_INT);
        if ($ID_PARAGRAPHE !== null) {
            $stmt->bindValue(':ID_PARAGRAPHE', $ID_PARAGRAPHE, PDO::PARAM_INT);
        }
        if ($ID_REVISION !== null) {
            $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
        }
        $stmt->execute();
        if (! $stmt->fetch()) {
            if ($LIA_TYPE == 'RTE') {
                $LIA_ORDRE = 0;
            } else {
                $stmt = $dbh->prepare("select max(LIA_ORDRE) from LIAISON_PAGE where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and LIA_TYPE=:LIA_TYPE");
                $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
                $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_STR);
                $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
                $stmt->execute();
                $LIA_ORDRE = intval($stmt->fetchColumn()) + 1;
            }

            $stmt = $dbh->prepare("insert into LIAISON_PAGE (
                LIA_CODE,
                ID_LIAISON,
                ID_PAGE,
                ID_PARAGRAPHE,
                ID_REVISION,
                LIA_ORDRE,
                LIA_TYPE,
                LIA_TEMP,
                LIA_TEXT
                ) values (
                :LIA_CODE,
                :ID_LIAISON,
                :ID_PAGE,
                :ID_PARAGRAPHE,
                :ID_REVISION,
                :LIA_ORDRE,
                :LIA_TYPE,
                :LIA_TEMP,
                :LIA_TEXT
                )");
            $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_STR);
            $stmt->bindValue(':ID_PAGE', $ID_PAGE, PDO::PARAM_INT);
            $stmt->bindValue(':ID_PARAGRAPHE', $ID_PARAGRAPHE, PDO::PARAM_INT);
            $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_ORDRE', $LIA_ORDRE, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEMP', $LIA_TEMP, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEXT', $LIA_TEXT, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public static function getLiaisonPage($LIA_CODE, $ID_LIAISON, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select ID_LIAISON_PAGE, LIA_TEXT, OFF_PAGE.* from LIAISON_PAGE
            inner join OFF_PAGE using(ID_PAGE)
            where LIA_TYPE=" . $dbh->quote($LIA_TYPE) . " and LIA_CODE=" . $dbh->quote($LIA_CODE) . " and ID_LIAISON=" . $dbh->quote($ID_LIAISON) . "
            order by LIA_ORDRE";

        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function reorderLiaisonPage($LIA_CODE, $ID_LIAISON, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select OFF_PAGE.* from LIAISON_PAGE
            inner join OFF_PAGE using(ID_PAGE)
            where LIA_TYPE=" . $dbh->quote($LIA_TYPE) . " and LIA_CODE=" . $dbh->quote($LIA_CODE) . " and ID_LIAISON=" . $dbh->quote($ID_LIAISON) . "
            order by LIA_ORDRE";
        $cpt = 1;
        $stmt = $dbh->prepare("update LIAISON_PAGE set
            LIA_ORDRE = :LIA_ORDRE
            where ID_LIAISON_PAGE = :ID_LIAISON_PAGE");
        $stmt->bindParam(':LIA_ORDRE', $cpt, PDO::PARAM_INT);
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_LIAISON_PAGE) {
            $stmt->bindValue(':ID_LIAISON_PAGE', $ID_LIAISON_PAGE, PDO::PARAM_INT);
            $stmt->execute();
            $cpt ++;
        }
    }

    /**
     * Insert une liaison avec la thématique
     *
     * @param  string $LIA_CODE
     *                               Type de liaison -> référence à DD_LIAISON
     * @param  int    $ID_LIAISON
     *                               Id de l'élément à laquelle on souaite créer la liaison
     * @param  int    $ID_THEMATIQUE
     *                               Id de la thématique
     * @return void
     */
    public static function insertThematique($LIA_CODE, $ID_LIAISON, $ID_THEMATIQUE)
    {
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("select * from LIAISON_THEMATIQUE where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and ID_THEMATIQUE=:ID_THEMATIQUE ");
        $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_INT);
        $stmt->bindValue(':ID_THEMATIQUE', $ID_THEMATIQUE, PDO::PARAM_INT);

        $stmt->execute();
        if (! $stmt->fetch()) {
            $stmt = $dbh->prepare("insert into LIAISON_THEMATIQUE (
                LIA_CODE,
                ID_LIAISON,
                ID_THEMATIQUE
                ) values (
                :LIA_CODE,
                :ID_LIAISON,
                :ID_THEMATIQUE
                )");
            $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_INT);
            $stmt->bindValue(':ID_THEMATIQUE', $ID_THEMATIQUE, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    public static function getLiaisonThematique($LIA_CODE, $ID_LIAISON)
    {
        $dbh = DB::getInstance();
        $sql = 'select ID_LIAISON, THEMATIQUE.*
                from LIAISON_THEMATIQUE
                inner join THEMATIQUE using(ID_THEMATIQUE)
                where LIA_CODE=' . $dbh->quote($LIA_CODE) . '
                and ID_LIAISON=' . intval($ID_LIAISON);

        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert une liaison avec l'utilisateur
     *
     * @param  string $LIA_CODE
     *                                Type de liaison -> référence à DD_LIAISON
     * @param  int    $ID_LIAISON
     *                                Id de l'élément à laquelle on souaite créer la liaison
     * @param  int    $ID_UTILISATEUR
     *                                Id de l'utilisateur
     * @param  string $LIA_TYPE
     *                                Soustype -> pour liaison de differentes groupes
     * @param  string $LIA_TEMP
     *                                pour ajout avant creations objet de liaison
     * @param  string $LIA_TEXT
     *                                Pour ajout texte associé
     * @return void
     */
    public static function insertUtilisateur($LIA_CODE, $ID_LIAISON, $ID_UTILISATEUR, $ID_REVISION = null, $LIA_TYPE = '', $LIA_TEMP = '', $LIA_TEXT = null)
    {
        $dbh = DB::getInstance();
        $filtre = " and LIA_TYPE=" . $dbh->quote($LIA_TYPE);
        if (empty($ID_REVISION)) {
            $ID_REVISION = null;
            $filtre .= " and ID_REVISION is null";
        } else {
            $filtre .= " and ID_REVISION=:ID_REVISION";
        }
        $stmt = $dbh->prepare("select ID_LIAISON_UTILISATEUR from LIAISON_UTILISATEUR
        where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and ID_UTILISATEUR=:ID_UTILISATEUR" . $filtre);
        $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_INT);
        $stmt->bindValue(':ID_UTILISATEUR', $ID_UTILISATEUR, PDO::PARAM_INT);
        if ($ID_REVISION !== null) {
            $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
        }

        $stmt->execute();
        if (! $stmt->fetch()) {

            $stmt = $dbh->prepare("select max(LIA_ORDRE) from LIAISON_UTILISATEUR
            where LIA_CODE=:LIA_CODE and ID_LIAISON=:ID_LIAISON and LIA_TYPE=:LIA_TYPE");
            $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
            $stmt->execute();
            $LIA_ORDRE = intval($stmt->fetchColumn()) + 1;

            $stmt = $dbh->prepare("insert into LIAISON_UTILISATEUR (
                LIA_CODE,
                ID_LIAISON,
                ID_UTILISATEUR,
                LIA_ORDRE,
                LIA_TYPE,
                LIA_TEMP,
                LIA_TEXT,
                ID_REVISION
                ) values (
                :LIA_CODE,
                :ID_LIAISON,
                :ID_UTILISATEUR,
                :LIA_ORDRE,
                :LIA_TYPE,
                :LIA_TEMP,
                :LIA_TEXT,
                :ID_REVISION
                )");
            $stmt->bindValue(':LIA_CODE', $LIA_CODE, PDO::PARAM_STR);
            $stmt->bindValue(':ID_LIAISON', $ID_LIAISON, PDO::PARAM_INT);
            $stmt->bindValue(':ID_UTILISATEUR', $ID_UTILISATEUR, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_ORDRE', $LIA_ORDRE, PDO::PARAM_INT);
            $stmt->bindValue(':LIA_TYPE', $LIA_TYPE, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEMP', $LIA_TEMP, PDO::PARAM_STR);
            $stmt->bindValue(':LIA_TEXT', $LIA_TEXT, PDO::PARAM_STR);
            $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    public static function getLiaisonUtilisateur($LIA_CODE, $ID_LIAISON, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select ID_LIAISON_UTILISATEUR, LIA_TEXT, UTILISATEUR.* from LIAISON_UTILISATEUR
                inner join UTILISATEUR using(ID_UTILISATEUR)
                where LIA_TYPE=" . $dbh->quote($LIA_TYPE) . " and LIA_CODE=" . $dbh->quote($LIA_CODE) . " and ID_LIAISON=" . intval($ID_LIAISON) . "
                order by LIA_ORDRE";

        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function reorderLiaisonUtilisateur($LIA_CODE, $ID_LIAISON, $LIA_TYPE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select ID_LIAISON_UTILISATEUR from LIAISON_UTILISATEUR
            inner join UTILISATEUR using(ID_UTILISATEUR)
            where LIA_TYPE=" . $dbh->quote($LIA_TYPE) . " and LIA_CODE=" . $dbh->quote($LIA_CODE) . " and ID_LIAISON=" . intval($ID_LIAISON) . "
            order by LIA_ORDRE";
        $cpt = 1;
        $stmt = $dbh->prepare("update LIAISON_UTILISATEUR set
            LIA_ORDRE = :LIA_ORDRE
            where ID_LIAISON_UTILISATEUR = :ID_LIAISON_UTILISATEUR");
        $stmt->bindParam(':LIA_ORDRE', $cpt, PDO::PARAM_INT);
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_LIAISON_UTILISATEUR) {
            $stmt->bindValue(':ID_LIAISON_UTILISATEUR', $ID_LIAISON_UTILISATEUR, PDO::PARAM_INT);
            $stmt->execute();
            $cpt ++;
        }
    }
}
