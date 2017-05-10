<?php
require '../inc.fo_init.php';

// Récupération des infos sur le type de liaison
$sql = 'select *
        from DD_LIAISON
        where LIA_CODE = ' . $dbh->quote($_GET['LIA_CODE']);
if ($rowLiaison = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
    // Récupération des identifiants des objets liés à l'élément de la webothèque
    $sql = 'select ID_LIAISON
        from LIAISON_WEBOTHEQUE
        where LIA_CODE = ' . $dbh->quote($rowLiaison['LIA_CODE']) . '
        and ID_WEBOTHEQUE = ' . intval($_GET['idtf']);
    $aIdExterne = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($aIdExterne)) {
        // Récupération des infos sur l'objet
        $sql = 'select ' . $rowLiaison['LIA_NOM_CHAMP_ID'] . ', ' . $rowLiaison['LIA_LIBELLE_CHAMP'] . '
            from ' . $rowLiaison['LIA_CODE'] . '
            where ' . $rowLiaison['LIA_NOM_CHAMP_ID'] . ' in (' . implode(',', array_map(array($dbh, 'quote'), $aIdExterne)) . ');';?>
        <ul>
            <?php foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_GROUP | PDO::FETCH_COLUMN) as $id => $libelle) { ?>
            <li>
                <a href="<?php echo $rowLiaison['LIA_NOM_FICHIER']?>?idtf=<?php echo $id?>"><?php echo secureInput($libelle)?></a>
            </li>
            <?php } ?>
        </ul>
    <?php } ?>
<?php } ?>
