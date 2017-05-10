<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_webotheque.php';
Utilisateur::checkConnected();

$enabled = array(
    'MOD_WEBOTHEQUE_IMAGE' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE'))
        && Utilisateur::getConnected()->checkProfil(array('PRO_WEBIMAGE', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_DOCUMENT' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_DOCUMENT'))
        && Utilisateur::getConnected()->checkProfil(array('PRO_WEBDOCUMENT', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_LIENEXTERNE' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE'))
         && Utilisateur::getConnected()->checkProfil(array('PRO_WEBLIENEXTERNE', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_FLASH' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_FLASH'))
         && Utilisateur::getConnected()->checkProfil(array('PRO_WEBFLASH', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_MUSIC' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_MUSIC'))
         && Utilisateur::getConnected()->checkProfil(array('PRO_WEBMUSIC', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_VIDEO' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_VIDEO'))
         && Utilisateur::getConnected()->checkProfil(array('PRO_WEBVIDEO', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_VIDEOEXTERNE' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_VIDEOEXTERNE'))
         && Utilisateur::getConnected()->checkProfil(array('PRO_WEBVIDEOEXTERNE', 'PRO_WEBROOT')),
    'MOD_WEBOTHEQUE_WIDGET' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_WIDGET'))
         && Utilisateur::getConnected()->checkProfil(array('PRO_WEBWIDGET', 'PRO_WEBROOT'))
);
if (array_sum($enabled) == 0) {
    header('Location:' . SERVER_ROOT . 'cms/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('WEB'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Webothèque</h2>
            <?php
            if ($enabled['MOD_WEBOTHEQUE_IMAGE']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_IMAGE' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Images')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) {
                            $oWebo = new Webo_IMAGE($row['ID_WEBOTHEQUE']);
                            $oWebo->setFields($row);?>
                            <li><span><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : '; ?></span>
                            <?php if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBIMAGE', 'PRO_WEBROOT'))) { ?>
                                <a href="web_image.php?idtf=<?php echo $oWebo->getID()?>"><img src="<?php echo $oWebo->getThumbSRC()?>" alt=""></a>
                            <?php } else { ?>
                                <img src="<?php echo $oWebo->getThumbSRC()?>" alt="">
                            <?php } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_IMAGE']['child']['IMAGE']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_DOCUMENT']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_DOCUMENT' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Documents')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) {?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBDOCUMENT', 'PRO_WEBROOT'))) { ?>
                                <a href="web_document.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_DOCUMENT']['child']['DOCUMENT']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_LIENEXTERNE']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_LIENEXTERNE' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Liens externes')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) { ?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBLIENEXTERNE', 'PRO_WEBROOT'))) { ?>
                                <a href="web_lienExterne.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_LIENEXTERNE']['child']['LIENEXTERNE']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_FLASH']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_FLASH' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Flashs')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) { ?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBFLASH', 'PRO_WEBROOT'))) { ?>
                                <a href="web_flash.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_FLASH']['child']['FLASH']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_MUSIC']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_MUSIC' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Audios')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) { ?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBMUSIC', 'PRO_WEBROOT'))) { ?>
                                <a href="web_music.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_MUSIC']['child']['MUSIC']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_VIDEO']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_VIDEO' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Videos')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) { ?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBVIDEO', 'PRO_WEBROOT'))) { ?>
                                <a href="web_video.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_VIDEO']['child']['VIDEO']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_VIDEOEXTERNE']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_VIDEOEXTERNE' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Videos externes')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) { ?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBVIDEOEXTERNE', 'PRO_WEBROOT'))) { ?>
                                <a href="web_videoExterne.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_VIDEOEXTERNE']['child']['VIDEOEXTERNE']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>

            <?php
            if ($enabled['MOD_WEBOTHEQUE_WIDGET']) {
                $sql = "select * from WEBOTHEQUE
                    left join UTILISATEUR using(ID_UTILISATEUR)
                    where WBT_CODE = 'WBT_WIDGET' and WEBOTHEQUE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by WEB_DATEMODIFICATION desc limit 0, 3";
                $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aRow)> 0) { ?>
                <div class="weboFallBack clearfix">
                    <h3><?php echo gettext('Widgets')?></h3>
                    <div class="left">
                        <h4><?php echo gettext('Derniers elements modifies')?></h4>
                        <ul>
                        <?php foreach ($aRow as $row) { ?>
                            <li><?php
                            echo gettext('Le') . ' ' . date('d/m/Y H:i', $row['WEB_DATEMODIFICATION']) . ', ' .
                            secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM'] . ' ' . $row['WEB_REDACTEUR']) . ' : ';
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBWIDGET', 'PRO_WEBROOT'))) { ?>
                                <a href="web_widget.php?idtf=<?php echo $row['ID_WEBOTHEQUE']?>"><?php echo secureInput($row['WEB_LIBELLE'])?></a>
                            <?php } else {
                                echo secureInput($row['WEB_LIBELLE']);
                            } ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class="right">
                        <h4>Actions</h4>
                        <?php $itemN2 = $a['WEB']['child']['MOD_WEBOTHEQUE_WIDGET']['child']['WIDGET']; ?>
                        <?php if ($itemN2['txt']) { ?>
                        <p><em><?php echo secureInput($itemN2['txt'])?></em></p>
                        <?php } ?>
                        <ul>
                        <?php foreach ($itemN2['child'] as $keyN3=>$itemN3) { ?>
                            <li>
                                <a href="<?php echo$itemN3['url'] ?>"><?php echo secureInput($itemN3['label'])?></a>
                                <?php if ($itemN3['txt']) { ?>
                                <p><em><?php echo secureInput($itemN3['txt'])?></em></p>
                                <?php } ?>
                            </li>
                        <?php }?>
                        </ul>
                    </div>
                </div>
                <?php
                }
            } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
