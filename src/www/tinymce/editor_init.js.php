<?php
ob_start();
session_start();
require '../include/config.php';
header('Content-type: text/javascript; charset=utf-8');
header('Content-script-type: text/javascript');
require '../include/lib.common.php';
require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.CMS.php';
require CLASS_DIR . 'class.db_site.php';
require CLASS_DIR . 'class.db_module.php';
$dbh = DB::getInstance();
CMS::init();

$enabled = array(
    'cmsLienDocument'  => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_DOCUMENT')),
    'cmsFlash'         => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_FLASH')),
    'cmsImage'         => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE')),
    'cmsLienExterne'   => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE')),
    'cmsMusic'         => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_MUSIC')),
    'cmsVideo'         => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_VIDEO')),
    'cmsVideoExterne'  => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_VIDEOEXTERNE')),
    'cmsLienInterne'   => CMS::getCurrentSite()->hasModule(new Module('MOD_CORE')),
    'cmsLienTemplate'  => CMS::getCurrentSite()->hasModule(new Module('MOD_CORE')),
    'cmsLienImage'     => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE')),
    'cmsAbbr'          => CMS::getCurrentSite()->hasModule(new Module('MOD_ABREVIATION')),
    'cmsLangue'        => CMS::getCurrentSite()->hasModule(new Module('MOD_LANGUISME'))
);
$buttons = array(
    array(
        'cmsFlash',
        'cmsVideo',
        'cmsVideoExterne',
        'cmsMusic',
        'cmsImage'
    ),
    array(
        'cmsLienInterne',
        'cmsLienDocument',
        'cmsLienExterne',
        'cmsLienTemplate',
        'cmsLienImage'
    ),
    array(
        'cmsAbbr',
        'cmsLangue'
    )
);

$cmsButtons = '';
foreach ($buttons as $key => $group) {
    $newGroup = $key != 0;
    foreach ($group as $tiny) {
        if ($enabled[$tiny]) {
            if ($cmsButtons == '') {
                $cmsButtons = $tiny;
            } else {
                if ($newGroup) {
                    $cmsButtons .= ',separator';
                    $newGroup = false;
                }
                $cmsButtons .= ',' . $tiny;
            }
        }
    }
}
?>
/**
 * Permet de configurer l'éditeur
 * @param {String} config configuration voulue (full, minimal...)
 * @param {Array} tabIdTextareas tableau contenant les id des textareas sur lesquels appliquer TinyMCE
 */
function editorInit(config, tabIdTextareas, idStylePerso, prs_code)
{
    if (typeof prs_code == 'undefined') {
        prs_code = '';
    }
    var mode, elements;
    if (tabIdTextareas.length != 0) {
        mode = 'exact';
        elements = tabIdTextareas.join(',');
    } else {
        mode = 'textareas';
        elements = '';
    }

    var content_css = SERVER_ROOT + "include/css/css.php?editor=<?php echo secureInput($_GET['editor'])?>&param=" + new Date().getTime();
    if (idStylePerso) {
        content_css += "&idtf=" + idStylePerso;
    }

    var barreBoutons1 = "sub,sup,bold,italic,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,help";
    var barreBoutons2 = "cut,copy,pastetext,pasteword,separator,"
              + "undo,redo,separator,"
              + "search,replace,separator,"
              + "bullist,numlist,separator,"
              + "outdent,indent,separator,"
              + "charmap,hr,separator,"
              + "cleanup,removeformat,separator,"
              + "code,separator,"
              + "visualaid,fullscreen";
    var barreBoutons3 = "<?php echo $cmsButtons ?>";
    var barreBoutons4 = "";
    var blockformats = "p";
    var plugin = "cms,contextmenu,fullscreen,paste,searchreplace,table";
    switch (config) {
        case 'paragraphe' :
            barreBoutons1 = "styleselect,formatselect,separator," + barreBoutons1;
            barreBoutons4 = "tablecontrols"
            blockformats = blockformats + ",h1,h2,h3,h4,h5";
            break;
        case 'module' :
            barreBoutons1 = "styleselect,formatselect,separator," + barreBoutons1;
            blockformats = blockformats + ",h3,h4";
            break;
        case 'externe' :
            break;
        case 'minimal' :
            barreBoutons1 = "bold,italic,separator,bullist,numlist,separator,hr";
            barreBoutons2 = "";
            barreBoutons3 = "";
            plugin = "";
            break;
        case 'eam' :
            barreBoutons3 = "forecolor, fontsizeselect, separator, cmsEamLien, cmsEamTxt, separator, cmsImage, separator, cmsLienInterne, cmsLienDocument, cmsLienExterne, cmsLienTemplate";
            content_css = SERVER_ROOT + "eam/eam_editeurCss.php?" + eam_css_qs;
            break;
        case 'presque-riche' :
            barreBoutons1 = "bold,italic,underline,separator,code";
            barreBoutons2 = "";
            barreBoutons3 = "";
            plugin = "";
            break;
        default :
            barreBoutons1 = barreBoutons2 = barreBoutons3 = plugin = "";
            break;
    }

    tinyMCE.init({
        mode: mode,
        elements: elements,
        theme_advanced_blockformats: blockformats,
        theme: "advanced",
        fix_list_elements: true,
        body_class: prs_code,
        language: "<?php echo secureInput($_GET['language'])?>",
        plugins: plugin,
        theme_advanced_buttons1: barreBoutons1,
        theme_advanced_buttons2: barreBoutons2,
        theme_advanced_buttons3: barreBoutons3,
        theme_advanced_buttons4: barreBoutons4,
        theme_advanced_toolbar_location: "top",
        theme_advanced_toolbar_align: "left",
        theme_advanced_statusbar_location: "bottom",
        content_css: content_css,
        convert_urls: false,
        browser_spellcheck: true,
        extended_valid_elements: "hr,"
            + "span[class|align|style|lang],"
            + "-table[border=0|cellspacing|cellpadding|width|height|class|align|summary=|style|dir|id|lang|bgcolor|background|bordercolor],"
            + "#p/div[style|class|align],"
            + "acronym[title|lang],"
            + "img[id|dir|lang|longdesc|usemap|style|class|src|border|alt=|title|hspace|vspace|width|height|align|idtf|format|popup|legende|credit|rel],"
            + "a[id|style|charset|hreflang|lang|tabindex|accesskey|type|name|href|target|title|class|ancre|typelien|libelle_ancre|rel]",
        invalid_elements: "font,u",
        theme_advanced_resizing: true,
        theme_advanced_resize_horizontal: false,
        button_tile_map: true,
        entity_encoding: "raw",
        forced_root_block: "p",
        skin: "o2k7",
        skin_variant : "silver"
    });
}
