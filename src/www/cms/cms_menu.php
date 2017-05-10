<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array($_GET['N1']); if (isset($_GET['N2'])) {$aMenuKey[] = $_GET['N2'];}; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
        <?php if ($aMenuKey[0] == 'MDL') {
            echo '<h2>Modules</h2>';
        } else if ($aMenuKey[0] == 'CFG') {
            echo '<h2>Configuration</h2>';
        } ?>
            <div>
                <?php foreach ($a[$_GET['N1']]['child'] as $keyN2=>$itemN2) { ?>
                <div class="fallback">
                    <h3><a href="<?php echo $itemN2['url'] ? $itemN2['url'] : SERVER_ROOT . 'cms/cms_menu.php?N1=' . $_GET['N1'] . '&amp;N2=' . $keyN2?>"><?php echo secureInput($itemN2['label'])?></a></h3>
                    <?php if ($itemN2['txt']) { ?>
                    <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                    <?php } ?>
                    <?php if (is_array($itemN2['child'])) { ?>
                    <ul>
                    <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                        <li>
                            <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                            <?php if ($itemN3['txt']) { ?>
                            <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                            <?php } ?>
                            <?php if (is_array($itemN3['child'])) { ?>
                            <ul>
                            <?php foreach ($itemN3['child'] as $keyN4=>$itemN4) { ?>
                                <li>
                                    <a href="<?php echo$itemN4['url'] ?>"><?php echo secureInput($itemN4['label'])?></a>
                                </li>
                            <?php }?>
                            </ul>
                            <?php } ?>
                        </li>
                    <?php }?>
                    </ul>
                    <?php } ?>
                </div>
            <?php } ?>
            </div>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
