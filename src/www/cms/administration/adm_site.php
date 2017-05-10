<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array(
    'PRO_ROOT'
));
require CLASS_DIR . 'class.db_webotheque.php';
require_once CLASS_DIR . 'class.db_page.php';

$oSite = new Site($_GET['idtf']);
$row = $oSite->getFields();
$aFichiersGenerique = $oSite->getFichiersGeneriques(false);
$SIT_IMAGE = CMS::getCurrentSite()->getField('SIT_IMAGE');
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php')?>
    <script>
function postControl_formCreation(oForm)
{
    selectAll('SIT_CODE_AFFECTE');
    <?php
    if (! $oSite->exist()) {
        $sql = "select SIT_CODE, SIT_LIBELLE from DD_SITE";
        $aSite = $dbh->query($sql)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
        $i = 0;
        ?>
        var aSIT_CODE = new Array(<?php echo count($aSite) ?>);
        <?php foreach ($aSite as $SIT_CODE=>$SIT_LIBELLE) { ?>
        aSIT_CODE[<?php echo $i++ ?>] = '<?php echo str_replace('SIT_', '', $SIT_CODE); ?>' ;
        <?php } ?>
        if (aSIT_CODE.length==0) {return true;}
        for (var i = 0 ; i < aSIT_CODE.length ; i++) {
            if (aSIT_CODE[i]==document.getElementById('SIT_CODE').value) {
                alert('<?php echo gettext('Ce code existe deja');?>');

                return false;
            }
        }
    <?php } ?>

    return true;
}

var isChekingGA = false;
function checkGA(submitOnSuccess, oForm) {

    if (!isChekingGA) {
        var id = $('#SIT_GA_ID').val();
        var pwd = $('#SIT_GA_KEYFILE_URL').val();
        if (id != '' && pwd != '') {
            var siteID = $('#SIT_GA_ID_SITE').val();
            isChekingGA = true;
            $('#checkGAButton').removeClass('submit');
            $('#GA_isChecked').val('0');
            $.ajax({
                type: 'POST',
                url: SERVER_ROOT + 'include/ajax/ajax.verifGoogleAnalytics.php',
                data: {SIT_GA_ID: id, SIT_GA_KEYFILE_URL: pwd, SIT_GA_ID_SITE: siteID},
                cache: false,
                success: function (code_retour) {
                    isChekingGA = false;
                    $('#checkGAButton').addClass('submit');

                    switch (code_retour) {
                        case 'OK':
                            $('#GA_isChecked').val('1');
                            if (submitOnSuccess === true) {
                                $('#formCreation input[type="submit"]').click();
                            } else {
                                alert('<?php echo escapeJS(gettext('acces_ga_ok')); ?>');
                            }
                            break;
                        case 'ERREUR_IDSITE':
                            alert('<?php echo escapeJS(gettext('acces_ga_erreur_id_site')); ?>');
                            break;
                        case 'ERREUR_AUTHENTIFICATION':
                            alert('<?php echo escapeJS(gettext('acces_ga_erreur_authentification')); ?>');
                            break;
                        case 'ERREUR_ACCESS':
                            alert('<?php echo escapeJS(gettext('acces_ga_erreur')); ?>');
                            break;
                    }
                },
                error: function () {
                    isChekingGA = false;
                    $('#checkGAButton').addClass('submit');

                    alert('<?php echo escapeJS(gettext('erreur_verification')); ?>');
                }
            });
        }
    } else {
        alert('<?php echo escapeJS(gettext('acces_ga_verif_en_cours')); ?>');
    }
}

function hasChangedGA() {
    $('#GA_isChecked').val('0');
}
var aGABARITSTYLE = new Array();
<?php
$sql = "select GAB_CODE, DD_GABARITSTYLE.* from DD_GABARITSTYLE order by GBS_LIBELLE";
foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP) as $GAB_CODE => $rowTemp) {?>
aGABARITSTYLE["<?php echo $GAB_CODE?>"] = new Array();
<?php foreach ($rowTemp as $val) { ?>
aGABARITSTYLE["<?php echo $GAB_CODE?>"].push(new Option("<?php echo escapeJS(extraireLibelle($val['GBS_LIBELLE']))?>", "<?php echo $val['GBS_CODE']?>"));
<?php }
}
?>

var aGABARITIMAGE = new Array();
<?php
$sql = "select GAB_CODE, DD_GABARITIMAGE.* from DD_GABARITIMAGE order by GBI_LIBELLE";
foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP) as $GAB_CODE => $rowTemp) {?>
aGABARITIMAGE["<?php echo $GAB_CODE?>"] = new Array();
<?php foreach ($rowTemp as $val) { ?>
aGABARITIMAGE["<?php echo $GAB_CODE?>"].push(new Option("<?php echo escapeJS(extraireLibelle($val['GBI_LIBELLE']))?>", "<?php echo $val['GBI_CODE']?>"));
<?php }
}
?>

function majGAB_CODE(GAB_CODE)
{
    var eSelect = document.getElementById('GBS_CODE');
    eSelect.options.length = 0;
    eSelect.options[0] = new Option(' ', '');
    if (aGABARITSTYLE[GAB_CODE]) {
        for (var i=0; aGABARITSTYLE[GAB_CODE][i]; i++) {
            eSelect.options[i+1] = aGABARITSTYLE[GAB_CODE][i];
        }
    }

    eSelect = document.getElementById('GBI_CODE');
    eSelect.options.length = 0;
    eSelect.options[0] = new Option(' ', '');
    if (aGABARITIMAGE[GAB_CODE]) {
        for (var i=0; aGABARITIMAGE[GAB_CODE][i]; i++) {
            eSelect.options[i+1] = aGABARITIMAGE[GAB_CODE][i];
        }
    }

    //verifs et alertes des modules
    $.post("/include/ajax/ajax.verifGabaritModule.php", { gabid: GAB_CODE, sitecode: '<?php echo CMS::getCurrentSite()->getID()?>' },
        function (data) {
            var contentBlocModule = $(data).find("#MODULE_BLOC_DIV").html();
            $("#MODULE_BLOC").html(contentBlocModule);
            var contentAlerteGabarit = $(data).find("#ALERTE_GAB").html();
            $("#ALERTE_GAB_DIV").html(contentAlerteGabarit?contentAlerteGabarit:null);
        });
}
    </script>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'SITE'); if (!$oSite->exist()) $aMenuKey[] = 'ADD'; include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo $oSite->exist() ? secureInput($row['SIT_TITLE']) : 'Nouveau site';?></h2>
            <form method="post" action="adm_siteSubmit.php" class="creation" id="formCreation" enctype="multipart/form-data">
                <fieldset class="tab">
                    <legend>Informations</legend>
                    <fieldset>
                        <legend>Propriétés du site</legend>
                        <table>
                            <?php if (!$oSite->exist()) { ?>
                            <tr>
                                <th>
                                    <label for="SIT_CODE">Code</label>
                                    <div class="helper">Identifiant unique décrivant le site (sous forme de chaine de caractères)</div>
                                </th>
                                <td>SIT_<input type="text" name="SIT_CODE" id="SIT_CODE" value="<?php echo secureInput($row['SIT_CODE'])?>" size="30" maxlength="30" required></td>
                            </tr>
                            <?php } else { ?>
                            <tr>
                                <th>
                                    <label>Code</label>
                                    <div class="helper">Identifiant unique décrivant le site (sous forme de chaine de caractères)</div>
                                </th>
                                <td><?php echo secureInput($row['SIT_CODE'])?></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label for="LNG_CODE">Langue</label></th>
                                <td>
                                    <select name="LNG_CODE" id="LNG_CODE" required>
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from DD_LANGUE where LNG_FO=1 order by LNG_LIBELLE";
                                        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowTemp) { ?>
                                        <option value="<?php echo secureInput($rowTemp['LNG_CODE'])?>"<?php if ($rowTemp['LNG_CODE']==$row['LNG_CODE']) echo ' selected'?>><?php echo secureInput($rowTemp['LNG_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_LIBELLE">Titre du site BO</label>
                                    <div class="helper">Libellé utilisé sur l'espace backoffice </div>
                                </th>
                                <td><input type="text" name="SIT_LIBELLE" id="SIT_LIBELLE" value="<?php echo secureInput($row['SIT_LIBELLE'])?>" size="50" required></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_TITLE">Titre du site FO</label>
                                    <div class="helper">Alimentation de la balise <cite>title</cite> des pages </div>
                                </th>
                                <td>
                                    <input type="text" name="SIT_TITLE" id="SIT_TITLE" value="<?php echo secureInput($row['SIT_TITLE'])?>" size="50" required>
                                    <a class="btnAction" href="#" onclick="document.getElementById('SIT_TITLE').value=document.getElementById('SIT_LIBELLE').value; return false;">Copier le titre du site en BO</a>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_TXTMOBILEHIDDEN">Texte des paragraphes masqués sur mobile</label>
                                    <div class="helper">Texte générique restitué à la place des paragraphes paramétrés <cite>masqués sur mobile</cite> </div>
                                </th>
                                <td><textarea name="SIT_TXTMOBILEHIDDEN" id="SIT_TXTMOBILEHIDDEN" cols="80" rows="6"><?php echo secureInput($row['SIT_TXTMOBILEHIDDEN'])?></textarea></td>
                            </tr>
                            <?php if (!$oSite->exist()) { ?>
                            <tr>
                                <th>
                                    <label for="PAG_TITRE">Titre de la page d'accueil</label>
                                    <div class="helper"><?php echo gettext('helper_PAG_TITRE')?></div>
                                </th>
                                <td><input type="text" name="PAG_TITRE" id="PAG_TITRE" size="50" required></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label for="SIT_HOST">Nom de domaine</label></th>
                                <td><input type="text" name="SIT_HOST" id="SIT_HOST" value="<?php echo secureInput($row['SIT_HOST'])?>" size="50"></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_EMAIL">Email</label>
                                    <span class="helper">Utilisable par le mécanisme d'envoi d'email dans les templates d'emails</span>
                                </th>
                                <td><input type="email" name="SIT_EMAIL" id="SIT_EMAIL" value="<?php echo secureInput($row['SIT_EMAIL'])?>" size="100" required></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_SUPPORTKEY"><?php echo gettext('Cle support CMS')?></label>
                                    <div class="helper"><?php echo gettext('helper_SIT_SUPPORTKEY')?></div>
                                </th>
                                <td><input type="text" name="SIT_SUPPORTKEY" id="SIT_SUPPORTKEY" value="<?php echo secureInput($row['SIT_SUPPORTKEY'])?>" size="40"></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_RSS_ACTU">Flux RSS actualités CMS.eolas</label>
                                    <div class="helper">Par défaut : http://cms-support.eolas.fr/rss_actualite.php</div>
                                </th>
                                <td><input type="text" name="SIT_RSS_ACTU" id="SIT_RSS_ACTU" value="<?php echo secureInput($row['SIT_RSS_ACTU'])?>" size="50"> <a class="action" href="#" onclick="document.getElementById('SIT_RSS_ACTU').value='<?php echo SIT_RSS_ACTU_DEFAULT ?>'; return false;" title="">Par défaut</a></td>
                            </tr>
                            <tr>
                                <th><label for="SIT_RSS_WEBMARKET">Autre flux RSS</label></th>
                                <td><input type="text" name="SIT_RSS_WEBMARKET" id="SIT_RSS_WEBMARKET" value="<?php echo secureInput($row['SIT_RSS_WEBMARKET'])?>" size="50"> <a class="action" href="#" onclick="document.getElementById('SIT_RSS_WEBMARKET').value='<?php echo SIT_RSS_WEBMARKET_DEFAULT ?>'; return false;" title="">Par défaut</a></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_LOGO"><?php echo gettext('Logo')?></label>
                                    <div class="helper"><?php echo sprintf(gettext('helper_SIT_LOGO'), implode(', ', Webotheque::getExtension('WBT_IMAGE')));?></div>
                                </th>
                                <td>
                                     <input type="file" name="SIT_LOGO" id="SIT_LOGO">
                                     <?php if ($src = $oSite->getLogoSRC()) { ?>
                                     <img src="<?php echo $src ?>" alt="">
                                     <input type="checkbox" name="SIT_LOGO_DELETE" id="SIT_LOGO_DELETE" class="checkbox">
                                     <label for="SIT_LOGO_DELETE"><?php echo gettext('Supprimer')?></label>
                                     <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SIT_FAVICON"><?php echo gettext('Favicon')?></label>
                                    <div class="helper"><?php echo gettext('helper_SIT_FAVICON');?></div>
                                </th>
                                <td>
                                    <input type="file" name="SIT_FAVICON" id="SIT_FAVICON">
                                    <?php if ($src = $oSite->getFaviconSRC()) { ?>
                                    <img src="<?php echo $src?>" alt="">
                                    <input type="checkbox" name="SIT_FAVICON_DELETE" id="SIT_FAVICON_DELETE" class="checkbox">
                                    <label for="SIT_FAVICON_DELETE"><?php echo gettext('Supprimer')?></label>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                            if ($oSite->exist()) {
                                    if (Utilisateur::getConnected()->checkProfil(array('PRO_ROOT_SITE'))) {
                                        $sql = "select * from STYLEDYNAMIQUE where SIT_CODE=" . $dbh->quote($oSite->getID()) . " order by STY_LIBELLE";
                                        $aStyles = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                                        if (!$oSite->exist()) {
                                            $row['ID_STYLEDYNAMIQUE'] = '';
                                        }
                                        ?>
                            <tr>
                                <th><label<?php if (count($aStyles)>0) { ?> for="ID_STYLEDYNAMIQUE"<?php } ?>><?php echo gettext('Feuille de style perso')?></label></th>
                                <td>
                                    <select name="ID_STYLEDYNAMIQUE" id="ID_STYLEDYNAMIQUE" onchange="updateModify()">
                                        <option value="">&nbsp;</option>
                                        <?php foreach ($aStyles as $rowTemp) {?>
                                        <option value="<?php echo $rowTemp['ID_STYLEDYNAMIQUE']?>"<?php if($row['ID_STYLEDYNAMIQUE'] == $rowTemp['ID_STYLEDYNAMIQUE']) echo ' selected';?>><?php echo secureInput($rowTemp['STY_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                    <a href="../cms_styleDynamiquePopup.php" class="action popup"><?php echo gettext('ajouter')?></a>
                                    <a href="../cms_styleDynamiquePopup.php" id="modifierStylePerso" class="action popup"><?php echo gettext('modifier')?></a>
                                    <script type="text/javascript">
                                    function updateModify()
                                    {
                                        idSelected = document.getElementById('ID_STYLEDYNAMIQUE').value;
                                        link = document.getElementById('modifierStylePerso');
                                        if (idSelected != '') {
                                            link.style.display = '';
                                            link.href = '../cms_styleDynamiquePopup.php?idtf='+idSelected;
                                        } else {
                                            link.style.display = 'none';
                                        }
                                    }
                                    function addStylePerso(val, texte)
                                    {
                                        var option = new Option(texte, val, false, true);
                                        var select = document.getElementById('ID_STYLEDYNAMIQUE');
                                        select.options[select.options.length] = option;
                                    }
                                    updateModify();
                                    </script>
                                </td>
                            </tr>
                          <?php }
                        } ?>
                        </table>
                    </fieldset>
                    <fieldset>
                        <legend>Authentification des utilisateurs</legend>
                        <table>
                            <tr>
                                <th><label for="SIT_CONNECTION_MAX">Tentatives avant blocage</label></th>
                                <td>
                                    <select id="SIT_CONNECTION_MAX" name="SIT_CONNECTION_MAX">
                                    <?php
                                    $aSIT_CONNECTION_MAX = array(5, 10, 20, 30, 100);
                                    $aSIT_CONNECTION_TTL = array(10, 30, 60, 90, 120);
                                    $SIT_CONNECTION_MAX = $row['SIT_CONNECTION_MAX']? $row['SIT_CONNECTION_MAX'] : Site::$default_connexion_max;
                                    $SIT_CONNECTION_TTL = $row['SIT_CONNECTION_TTL']? $row['SIT_CONNECTION_TTL'] : Site::$default_connexion_ttl;
                                    foreach ($aSIT_CONNECTION_MAX as $v) {
                                        echo '<option value="'.secureInput($v).'"'.($v==$SIT_CONNECTION_MAX?' selected':'').'>' . secureInput($v) . '</option>';
                                    }?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="SIT_CONNECTION_TTL">Durée de blocage</label></th>
                                <td>
                                    <select id="SIT_CONNECTION_TTL" name="SIT_CONNECTION_TTL">
                                    <?php
                                    foreach ($aSIT_CONNECTION_TTL as $v) {
                                        echo '<option value="'.secureInput($v).'"'.($v==$SIT_CONNECTION_TTL?' selected':'').'>' . secureInput($v) . '</option>';
                                    }
                                    ?>
                                    </select>
                                    minutes
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>

                <fieldset class="tab">
                    <legend>Gabarit</legend>
                    <fieldset>
                        <legend>Propriétés</legend>
                        <table>
                            <tr>
                                <th>
                                    <label for="GAB_CODE">Gabarit</label>
                                    <div class="helper">Structure du site (<cite>zoning</cite>): colonnage, en-tête et pied de page, menus de navigation,... </div>
                                </th>
                                <td>
                                    <select name="GAB_CODE" id="GAB_CODE" onchange="majGAB_CODE(this.value)" required>
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from DD_GABARIT order by GAB_LIBELLE";
                                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) { ?>
                                        <option value="<?php echo $rowTemp['GAB_CODE']?>"<?php if ($rowTemp['GAB_CODE']==$row['GAB_CODE']) echo ' selected'?>><?php echo secureInput(extraireLibelle($rowTemp['GAB_LIBELLE']))?></option>
                                        <?php } ?>
                                    </select>
                                    <div id="ALERTE_GAB_DIV"></div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="GBS_CODE">Style</label>
                                    <div class="helper">Feuille de style CSS : habillage, couleurs, polices, visuels de fonds ,...</div>
                                </th>
                                <td>
                                    <select name="GBS_CODE" id="GBS_CODE" required>
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from DD_GABARITSTYLE where GAB_CODE=" . $dbh->quote($row['GAB_CODE']) . " order by GBS_LIBELLE";
                                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) { ?>
                                        <option value="<?php echo $rowTemp['GBS_CODE']?>"<?php if ($rowTemp['GBS_CODE']==$row['GBS_CODE']) echo ' selected'?>><?php echo secureInput(extraireLibelle($rowTemp['GBS_LIBELLE']))?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="GBI_CODE">Image</label>
                                    <div class="helper">Illustration dédiée (exemples : image avec texte incrusté, illustration thématique, …) </div>
                                </th>
                                <td>
                                    <select name="GBI_CODE" id="GBI_CODE" required>
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from DD_GABARITIMAGE where GAB_CODE=" . $dbh->quote($row['GAB_CODE']) . " order by GBI_LIBELLE";
                                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) { ?>
                                        <option value="<?php echo $rowTemp['GBI_CODE']?>"<?php if ($rowTemp['GBI_CODE']==$row['GBI_CODE']) echo ' selected'?>><?php echo secureInput(extraireLibelle($rowTemp['GBI_LIBELLE']))?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>

                <fieldset class="tab">
                    <legend>Référencement</legend>
                        <fieldset>
                            <legend>Meta</legend>
                            <table>
                                <tr>
                                    <th>
                                        <label for="SIT_AUTHOR">Author</label>
                                        <div class="helper">Balise de metadescription <cite>Author</cite> </div>
                                    </th>
                                    <td><input type="text" size="40" name="SIT_AUTHOR" id="SIT_AUTHOR" value="<?php echo secureInput($row['SIT_AUTHOR']) ?>"></td>
                                </tr>
                            </table>
                        </fieldset>
                        <fieldset>
                            <legend>Marqueur Google Analytics</legend>
                            <table>
                                <tr>
                                    <th>
                                        <label for="SIT_GA_TAG">Marqueur</label>
                                        <div class="helper"><?php echo gettext('helper_SIT_GA_TAG');?></div>
                                    </th>
                                    <td><textarea name="SIT_GA_TAG" id="SIT_GA_TAG" rows="5" cols="50"><?php echo secureInput($row['SIT_GA_TAG']) ?></textarea></td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="SIT_GA_TAG_CNIL_1">Conformité CNIL</label>
                                        <div class="helper">Applicable uniquement à un marqueur de type "<code>Universal Analytics Tags</code>"</div>
                                    </th>
                                    <td>
                                        <input type="radio" name="SIT_GA_TAG_CNIL" id="SIT_GA_TAG_CNIL_1" value="1"<?php if ($row['SIT_GA_TAG_CNIL']) echo ' checked'?>>
                                        <label for="SIT_GA_TAG_CNIL_1">Oui</label>
                                        <input type="radio" name="SIT_GA_TAG_CNIL" id="SIT_GA_TAG_CNIL_0" value="0"<?php if (!$row['SIT_GA_TAG_CNIL']) echo ' checked'?>>
                                        <label for="SIT_GA_TAG_CNIL_0">Non</label>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>

                        <fieldset>
                            <legend>Compte Google analytics <span class="helper">Pour pouvoir afficher les statistiques du site, vous devez indiquer les accès du compte administrateur Google Analytics.</span></legend>
                            <table>
                                <tr>
                                    <th><label for="SIT_GA_ID">Identifiant Google analytics</label></th>
                                    <td><input type="text" name="SIT_GA_ID" id="SIT_GA_ID" value="<?php echo secureInput($row['SIT_GA_ID']);?>" size="70" autocomplete="off"> <span class="note">ex : XXX@developer.gserviceaccount.com</span></td>
                                </tr>
                                <tr>
                                    <th><label for="SIT_GA_KEYFILE">Clé fournie par Google</label></th>
                                    <td>
                                        <input type="file" name="SIT_GA_KEYFILE" id="SIT_GA_KEYFILE">
                                         <?php if ($src = $oSite->getGAKeyFile()) { ?>
                                         <a href="<?php echo $src ?>" class="action">Voir</a>
                                         <input type="checkbox" name="SIT_GA_KEYFILE_DELETE" id="SIT_GA_KEYFILE_DELETE" class="checkbox">
                                         <label for="SIT_GA_KEYFILE_DELETE"><?php echo gettext('Supprimer')?></label>
                                         <input type="hidden" name="SIT_GA_KEYFILE_URL" id="SIT_GA_KEYFILE_URL" value="<?php echo encode($row['SIT_GA_KEYFILE'])?>">
                                         <?php }?>
                                     </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="SIT_GA_ID_SITE">Identifiant du site web</label>
                                        <div class="helper"><?php echo gettext('info_analytics_content');?><br><img src="<?php echo SERVER_ROOT ?>images/aide_GA_<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2) ?>.png" width="100%"></div>
                                    </th>
                                    <td>
                                        <input type="text" name="SIT_GA_ID_SITE" id="SIT_GA_ID_SITE" value="<?php echo secureInput($row['SIT_GA_ID_SITE']) ?>" data-type="integer"> <span class="note">ex : 638727867</span>
                                        <?php if ($src) { ?>
                                        <input type="hidden" value="<?php if (!empty($row['SIT_GA_ID']) && $oSite->getGAKeyFile() && !empty($row['SIT_GA_ID_SITE'])) { echo '1'; } else { echo '0';} ?>" name="GA_isChecked" id="GA_isChecked">
                                        <input type="button" id="checkGAButton" class="submit" name="Delete" value="<?php echo gettext('acces_ga_verifier')?>" onclick="checkGA(false);">
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>
                </fieldset>

                <fieldset class="tab">
                    <legend><?php echo gettext('Modules')?></legend>
                    <div id="MODULE_BLOC">
                        <fieldset>
                        <?php
                        $sqlGabLib = "select GAB_LIBELLE from DD_GABARIT where GAB_CODE=" . $dbh->quote($row['GAB_CODE']);
                        $gabLib = secureInput(extraireLibelle($dbh->query($sqlGabLib)->fetchColumn()));

                        $sql = "select MOD_CODE, 1 from SITE_MODULE where SIT_CODE=" . $dbh->quote($oSite->getID());
                        $aMOD_CODE = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);

                        $aMOD_CODE_erreur = !empty($_GET['MOD_CODE']) ? $_GET['MOD_CODE'] : array();

                        $MOD_GROUPE = '';
                        $sql = "select * from DD_MODULE order by MOD_GROUPE, MOD_LIBELLE";
                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
                            //verif acces de ce module pour ce gabarit
                            $aMOD_CODE_GAB_erreur = array();
                            $sqlCountEntreesPourModule = "select count(ID_MODULE_GABARIT) from DD_MODULE_GABARIT where MOD_CODE = " . $dbh->quote($rowTemp['MOD_CODE']);
                            $countEntreesPourModule = $dbh->query($sqlCountEntreesPourModule)->fetchColumn();

                            $sqlCountModuleSpecifiqueGabarit = "select count(ID_MODULE_GABARIT) from DD_MODULE_GABARIT where GAB_CODE=" . $dbh->quote($row['GAB_CODE']) . " and MOD_CODE = " . $dbh->quote($rowTemp['MOD_CODE']);
                            $countModuleSpecifiqueGabarit = $dbh->query($sqlCountModuleSpecifiqueGabarit)->fetchColumn();

                            if ($countModuleSpecifiqueGabarit < 1) {
                                if ($countEntreesPourModule > 0) {
                                    $aMOD_CODE_GAB_erreur[] = $rowTemp['MOD_CODE'];
                                }
                            }

                            if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                if ($MOD_GROUPE != '') {
                                    echo '</ul></td></tr></table></fieldset><fieldset>';
                                }
                                $MOD_GROUPE = $rowTemp['MOD_GROUPE'];?>
                                <legend><?php echo secureInput(extraireLibelle($rowTemp['MOD_GROUPE']))?></legend>
                                <table>
                                    <tr>
                                        <th></th>
                                        <td><ul>
                                    <?php } ?>
                                            <li>
                                                <input type="checkbox" value="<?php echo $rowTemp['MOD_CODE']?>" id="<?php echo $rowTemp['MOD_CODE']?>" name="MOD_CODE[]"<?php if ($rowTemp['MOD_OBLIGATOIRE'] || isset($aMOD_CODE[$rowTemp['MOD_CODE']])) echo ' checked';?><?php if ($rowTemp['MOD_OBLIGATOIRE']) echo ' disabled'?>>
                                                <label for="<?php echo $rowTemp['MOD_CODE']?>"<?php if (in_array($rowTemp['MOD_CODE'], $aMOD_CODE_erreur) || in_array($rowTemp['MOD_CODE'], $aMOD_CODE_GAB_erreur)) echo ' class="alert"'?>><?php echo extraireLibelle(secureInput($rowTemp['MOD_LIBELLE']))?></label>
                                                <span class="alert" id="gab_error_<?php echo $rowTemp['MOD_CODE']?>" style="padding: 0 0 0 40px;">
                                                <?php
                                                if (in_array($rowTemp['MOD_CODE'], $aMOD_CODE_GAB_erreur)) {
                                                  printf(gettext('ce_module_nest_pas_disponible_pour_le_gabarit_X'), secureInput($gabLib));
                                                  if (isset($aMOD_CODE[$rowTemp['MOD_CODE']])) {
                                                    echo '&nbsp;'.gettext('veuillez_la_desactiver');
                                                  }
                                                }?>
                                                </span>
                                            </li>
                                <?php } ?>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>
                    </div>
                </fieldset>

                <fieldset class="tab">
                    <legend>Webothèque</legend>
                    <fieldset>
                        <legend>Webothèque</legend>
                        <table>
                            <?php
                            if (!$oSite->exist()) {
                                $row['SIT_EXT_DOC'] = ".txt\n.rtf\n.pdf\n.doc\n.xls\n.zip\n.ppt\n.gif\n.jpg\n.jpeg\n.bmp\n.png\n.docx\n.pptx\n.odt\n.docm\n.dotx\n.dotm\n.xlsx\n.xlsm\n.xltx\n.xltm\n.xlsb\n.pptm\n.potx\n.potm\n.ppsx\n.ppsm\n.sldx\n.sldm\n";
                                $row['SIT_EXT_IMAGE'] = ".gif\n.jpg\n.jpeg\n.bmp\n.png\n";
                                $row['SIT_EXT_FLASH'] = ".swf\n";
                                $row['SIT_EXT_VIDEO'] = ".flv\n.mp4\n.webm\n.ogv\n";
                                $row['SIT_EXT_MUSIC'] = ".mp3\n.ogg\n";
                            } ?>
                            <tr>
                                <th><label for="SIT_EXT_IMAGE">Extensions image autorisées</label>
                                <td><textarea rows="10" cols="80" name="SIT_EXT_IMAGE" id="SIT_EXT_IMAGE" required><?php echo secureInput($row['SIT_EXT_IMAGE'])?></textarea></td>
                                <th><label for="SIT_EXT_DOC">Extensions document autorisées</label>
                                <td><textarea rows="10" cols="80" name="SIT_EXT_DOC" id="SIT_EXT_DOC" required><?php echo secureInput($row['SIT_EXT_DOC'])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="SIT_EXT_FLASH">Extensions flash autorisées</label>
                                <td><textarea rows="5" cols="80" name="SIT_EXT_FLASH" id="SIT_EXT_FLASH" required><?php echo secureInput($row['SIT_EXT_FLASH'])?></textarea></td>
                                <th><label for="SIT_EXT_VIDEO">Extensions vidéos autorisées</label>
                                <td><textarea rows="5" cols="80" name="SIT_EXT_VIDEO" id="SIT_EXT_VIDEO" required><?php echo secureInput($row['SIT_EXT_VIDEO'])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="SIT_EXT_MUSIC">Extensions audio autorisées</label>
                                <td><textarea rows="5" cols="80" name="SIT_EXT_MUSIC" id="SIT_EXT_MUSIC" required><?php echo secureInput($row['SIT_EXT_MUSIC'])?></textarea></td>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" name="SIT_CHECKMD5" id="SIT_CHECKMD5"<?php echo (!$oSite->exist() || $row['SIT_CHECKMD5']) ? ' checked' : ''?>>
                                    <label for="SIT_CHECKMD5"><?php echo gettext('verification_unicite_weboteque');?></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>

                <fieldset class="tab">
                    <legend>Propriétés de page</legend>
                    <fieldset>
                        <legend>Propriétés de page</legend>
                        <table>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" value="1" name="SIT_PAGE_HTTPS" id="SIT_PAGE_HTTPS"<?php if ($row['SIT_PAGE_HTTPS']) echo ' checked'?>>
                                    <label for="SIT_PAGE_HTTPS">Accès sécurisé (HTTPS)</label>
                                </td>
                            </tr>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" value="1" name="SIT_PAGE_TXTACCROCHE" id="SIT_PAGE_TXTACCROCHE"<?php if (!$oSite->exist() || $row['SIT_PAGE_TXTACCROCHE']) echo ' checked'?>>
                                    <label for="SIT_PAGE_TXTACCROCHE">Texte d'accroche <span class="helper">Activation du champ dans les propriétés de pages</span></label>
                                </td>
                            </tr>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" value="1" name="SIT_PAGE_IMGACCROCHE" id="SIT_PAGE_IMGACCROCHE"<?php if (!$oSite->exist() || $row['SIT_PAGE_IMGACCROCHE']) echo ' checked'?>>
                                    <label for="SIT_PAGE_IMGACCROCHE">Image d'accroche <span class="helper">Activation du champ dans les propriétés de pages</span></label>
                                </td>
                            </tr>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" value="1" name="SIT_PAGE_CACHE" id="SIT_PAGE_CACHE"<?php if ($row['SIT_PAGE_CACHE']) echo ' checked'?>>
                                    <label for="SIT_PAGE_CACHE">Cache</label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>

                <fieldset class="tab">
                    <legend>Fichiers statiques</legend>
                    <fieldset>
                        <legend>Nouveau fichier</legend>
                        <table>
                            <tr>
                                <th>
                                    <label for="SFI_FICHIER_0">Libellé</label>
                                    <div class="helper">Libellé du fichier statique présent à la racine du site</div>
                                </th>
                                <td><input type="text" name="SFI_FICHIER_0" id="SFI_FICHIER_0" size="40" value=""></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="SFI_CONTENU_0"><?php echo gettext('Contenu')?></label>
                                    <div class="helper">Contenu texte du fichier statique</div>
                                </th>
                                <td><textarea name="SFI_CONTENU_0" id="SFI_CONTENU_0" cols="40" rows="5"></textarea></td>
                            </tr>
                        </table>
                        <input type="hidden" name="NB_FICHIERSGENERIQUES" value="<?php echo count($aFichiersGenerique)+1?>">
                    </fieldset>
                    <?php
                    if (count($aFichiersGenerique)>0) {
                        $i = 1;
                        foreach ($aFichiersGenerique as $fileName => $fileContent) { ?>
                        <fieldset>
                            <legend><?php echo secureInput($fileName) ?></legend>
                            <table>
                                <tr>
                                    <th>
                                        <label for="SFI_FICHIER_<?php echo $i ?>">Libellé <?php echo $i ?></label>
                                        <div class="helper">Libellé du fichier <?php echo $i ?> statique présent à la racine du site</div>
                                    </th>
                                    <td>
                                        <input type="text" name="SFI_FICHIER_<?php echo $i ?>" id="SFI_FICHIER_<?php echo $i ?>" size="40" value="<?php echo secureInput($fileName) ?>">
                                        <input type="checkbox" name="SFI_FICHIER_DELETE_<?php echo $i ?>" id="SFI_FICHIER_DELETE_<?php echo $i ?>" class="case" value="1">
                                        <label for="SFI_FICHIER_DELETE_<?php echo $i ?>"><?php echo gettext('Supprimer')?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="SFI_CONTENU_<?php echo $i ?>">Contenu</label>
                                        <div class="helper">Contenu texte du fichier statique <?php echo $i ?></div>
                                    </th>
                                    <td><textarea name="SFI_CONTENU_<?php echo $i ?>" id="SFI_CONTENU_<?php echo $i ?>" cols="40" rows="5"><?php echo secureInput($fileContent) ?></textarea></td>
                                </tr>
                            </table>
                        </fieldset>
                        <?php
                            $i++;
                        }
                    } ?>
                </fieldset>

                <?php if ($row['SIT_PAGE_CACHE']) { ?>
                <fieldset class="tab">
                    <legend>Cache</legend>
                    <fieldset>
                        <legend>Gestion du cache <span class="helper">A noter : par défaut toutes les pages sont gérées en cache, pour le modifier voir les propriétés de page</span></legend>
                            <?php
                            if (isset($_GET['clearCache'])) {
                                Page::clearCache($oSite->getID());
                                header('Location:' . PHP_SELF . '?idtf=' . $oSite->getID());
                                exit();
                            }?>
                            <table>
                                <tr>
                                    <th>
                                        <label>Pages en cache</label>
                                        <div class="helper">Nombre de pages stockées en cache  <br>
                                        --------------------------------------------------------<br>
                                        Une page en cache est une page qui consomme moins de bande passante.
                                        <br>
                                        Si une page a déjà été consultée, elle ne sera pas entièrement rechargée pour être affichée à nouveau.
                                        Elle est chargée plus rapidement.
                                        <br><br>
                                        <strong>Attention</strong>, l'activation du cache n'est pas conseillée sur une page dont les contenus sont mis à jour régulièrement (accueil, agenda, actualité,...)
                                        </div>
                                    </th>
                                    <td>
                                        <?php echo sizeof(glob(UPLOAD_CACHE_PHYSIQUE . CMS::getCurrentSite()->getID() . "-*.htm"))?>
                                        <a href="?idtf=<?php echo $oSite->getID()?>&amp;clearCache=1" class="btnAction">Vider le cache</a>
                                    </td>
                                </tr>
                            </table>
                    </fieldset>
                </fieldset>
                <?php } ?>

                <?php
                $oModuleAspmail = new Module('MOD_EAM');
                if ($oSite->exist() && $oSite->hasModule($oModuleAspmail)) {?>
                <fieldset class="tab">
                    <legend><?php echo secureInput(extraireLibelle($oModuleAspmail->getField('MOD_LIBELLE')))?></legend>
                    <table>
                        <tr>
                            <th><label for="SIT_EAM_ID">Identifiant ASPMail</label></th>
                            <td><input type="text" name="SIT_EAM_ID" id="SIT_EAM_ID" value="<?php echo secureInput($row['SIT_EAM_ID']) ?>" size="20" data-type="integer"></td>
                        </tr>
                        <tr>
                            <th><label for="SIT_EAM_SECURITYCODE">Code de sécurité</label></th>
                            <td><input type="text" name="SIT_EAM_SECURITYCODE" id="SIT_EAM_SECURITYCODE" value="<?php echo secureInput($row['SIT_EAM_SECURITYCODE']) ?>" size="50"></td>
                        </tr>
                        <tr>
                            <th><label for="SIT_EAM_STAT_1">Activer les statistiques</label></th>
                            <td>
                                <input type="radio" name="SIT_EAM_STAT" id="SIT_EAM_STAT_1" value="1"<?php if ($row['SIT_EAM_STAT']) echo ' checked'?>>
                                <label for="SIT_EAM_STAT_1">Oui</label>
                                <input type="radio" name="SIT_EAM_STAT" id="SIT_EAM_STAT_0" value="0"<?php if (!$row['SIT_EAM_STAT']) echo ' checked'?>>
                                <label for="SIT_EAM_STAT_0">Non</label>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                <?php } ?>

                <?php if (count(Utilisateur::getConnected()->getSites(true)) > 1) { ?>
                <fieldset class="tab">
                    <legend>Partage</legend>
                    <fieldset>
                        <legend>Partage <span class="helper">Liste des sites sur lesquels les données "noyau" du site courant sont partagées : webothèque, pages, utilisateurs</span></legend>
                        <table>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <table class="selection" style="position: relative;">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <th>&nbsp;</th>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="SIT_CODE_AFFECTE[]" id="SIT_CODE_AFFECTE" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('SIT_CODE_AFFECTE'), document.getElementById('SIT_CODE_ALL'));">
                                                <?php
                                                $sql = "select DD_SITE.* from DD_SITE
                                                    inner join SITE_PARTAGE on (DD_SITE.SIT_CODE=SITE_PARTAGE.SIT_CODE_TO)
                                                    where SIT_CODE_FROM=" . $dbh->quote($oSite->getID()) . "
                                                    order by SIT_LIBELLE";
                                                $notIn = "not in (" . $dbh->quote($oSite->getID());
                                                foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
                                                    $notIn .= ',' . $dbh->quote($rowTemp['SIT_CODE']);?>
                                                    <option value="<?php echo $rowTemp['SIT_CODE']?>"><?php echo secureInput($rowTemp['SIT_LIBELLE'])?></option>
                                                <?php
                                                }
                                                $notIn .= ')';?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('SIT_CODE_ALL'), document.getElementById('SIT_CODE_AFFECTE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('SIT_CODE_AFFECTE'), document.getElementById('SIT_CODE_ALL'));">
                                            </td>
                                            <td>
                                                <select name="SIT_CODE_ALL[]" id="SIT_CODE_ALL" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('SIT_CODE_ALL'), document.getElementById('SIT_CODE_AFFECTE'));">
                                                <?php
                                                $sql = "select * from DD_SITE
                                                    where SIT_CODE " . $notIn  . "
                                                    order by SIT_LIBELLE";
                                                foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {?>
                                                    <option value="<?php echo $rowTemp['SIT_CODE']?>"><?php echo secureInput($rowTemp['SIT_LIBELLE'])?></option>
                                                <?php
                                                } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>
                <?php } ?>

                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                            <?php if ($oSite->exist()) { ?>
                                <input type="hidden" name="idtf" value="<?php echo secureInput($oSite->getID())?>">
                                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                <?php if ($oSite->isDeletable()) { ?>
                                <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oSite->isDeletable()) echo ' disabled'?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='adm_siteSubmit.php?Delete=<?php echo $oSite->getID()?>'">
                                <?php } ?>
                            <?php } else { ?>
                                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                            <?php } ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
