<?php
if ($registeredVersion && version_compare($registeredVersion, '7.0.2dev6', '<')) {
    $sql = "select LIA_CODE_TO as LIA_CODE from LIAISON_EXTERNE where LIA_CODE_TO not in (select LIA_CODE from DD_LIAISON)
            union
            select LIA_CODE_FROM as LIA_CODE from LIAISON_EXTERNE where LIA_CODE_FROM not in (select LIA_CODE from DD_LIAISON)
            order by LIA_CODE";
    $rowListe = $app->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rowListe)) {
        $err = '<p>Avant de pouvoir mettre à jour le noyau, veuillez déclarer les liaisons ci-dessous au sein de la table "<code>DD_LIAISON</code>" :</p>';
        $err .='<ul>';
        foreach ($rowListe as $row) {
            $err .= '<li><code>' . htmlspecialchars($row['LIA_CODE'], ENT_QUOTES, 'UTF-8') . '</code></li>';
        }
        $err .='</ul>';
        return false;
    }
}
