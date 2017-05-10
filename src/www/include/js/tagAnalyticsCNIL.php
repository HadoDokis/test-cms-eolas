<?php
require dirname(__FILE__) . '/../inc.fo_init.php';
$oModule = new Module('MOD_CORE');
header('Content-type: text/javascript;charset=utf-8');

$gaPropertyPattern = '/UA-\d+-\d+/';
$gaMarqueurPattern = '/\(function\(.+\(window,document,\'script\',\'[^\']+\',\'([^\']+)\'\);/ims';
?>
var tagAnalyticsCNIL = {}

tagAnalyticsCNIL.CookieConsent = function () {
    // Désactive le tracking si le cookie d’Opt-out existe déjà.
    var disableStr     = null; // Initialisée dans le start()
    var gaProperty     = null; // Initialisée dans le start()
    var firstCall      = false;
    var domaineName    = '';
    var keyTab         = 9;
    var keyEnter       = 13;
    var keyEsc         = 27;
    var consentFocused = null;
    var location = window.location.protocol + '//' + window.location.hostname + window.location.pathname + window.location.search;

    // Gestionnaire d'évenement "keydown" pour la modale
    function evtKeyDown(evt) {
        if (evt.ctrlKey || evt.AltKey) {
            // do nothing
            return true;
        }
        switch (evt.keyCode) {
            case keyTab:
                if (evt.shiftKey) {
                    if ( evt.target && (evt.target == $("#inform-and-consent input, #inform-and-consent a").first().get(0)) ) {
                        $("#inform-and-consent input, #inform-and-consent a").last().get(0).focus();
                        evt.stopPropagation;
                        return false;
                    }
                } else {
                    if ( evt.target && (evt.target == $("#inform-and-consent input, #inform-and-consent a").last().get(0)) ) {
                        $("#inform-and-consent input, #inform-and-consent a").first().get(0).focus();
                        evt.stopPropagation;
                        return false;
                    }
                }
                return true;
                break;
            case keyEnter:
                if (typeof evt.target.click === "function") {
                    evt.target.click();
                }
                return true;
                break
            case keyEsc: {
                tagAnalyticsCNIL.CookieConsent.hideModal();
                evt.stopPropagation;
                return false;
                break;
            }
        }
        return true;
    }

    //Cette fonction retourne la date d’expiration du cookie de consentement
    function getCookieExpireDate() {
        var cookieTimeout = 33696000000;// Le nombre de millisecondes que font 13 mois
        var date = new Date();
        date.setTime(date.getTime()+cookieTimeout);
        var expires = "expires="+date.toGMTString();
        return expires;
    }

    function getDomainName() {
        if (domaineName != '') {
            return domaineName;
        } else {
            var hostname = document.location.hostname;
            if (hostname.indexOf("www.") === 0)
                hostname = hostname.substring(4);
            return hostname;
        }
    }

    //Cette fonction définie le périmétre du consentement ou de l'opposition (en fonction du domaine)
    //Par défaut nous considérons que le domaine est tout ce qu'il y'a aprés  "www"
    function getCookieDomainName() {
        var hostname = getDomainName();
        var domain = "domain=" + (!isIPv4(hostname)?".":"") + hostname;
        return domain;
    }

    //Cette fonction permet de vérifier si une chaine correspond à une IPv4
    function isIPv4(s) {
        return /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(s);
    }

    //Cette fonction vérifie si on  a déjà obtenu le consentement de la personne qui visite le site
    function checkFirstVisit() {
       var consentCookie =  getCookie('hasConsent');
       if ( !consentCookie ) return true;
    }

    //Affiche une  banniére d'information en haut de la page
    function showBanner() {
        var oEl = document.getElementById("document");
        var div = document.createElement('div');
        div.setAttribute('id','cookie-banner');
        div.setAttribute('tabindex', '0');
        // Le code HTML de la demande de consentement
        // Vous pouvez modifier le contenu ainsi que le style
        div.innerHTML = '<div id="cookie-banner-message">\
        <?php echo str_replace("\r", '', $oModule->i18n('cnil_txt_bandeau_cnil'));?>\
        <a href="javascript:tagAnalyticsCNIL.CookieConsent.showModal()" >\
            <?php echo $oModule->i18n('cnil_lien_savoir_plus');?>\
        </a></div>';
        oEl.insertBefore(div,oEl.firstChild); // Ajoute la banniére juste au début de la page
        createInformAndAskDiv();
     }


    // Fonction utile pour récupérer un cookie a partire de son nom
    function getCookie(NameOfCookie) {
        if (document.cookie.length > 0) {
            begin = document.cookie.indexOf(NameOfCookie+"=");
            if (begin != -1) {
                begin += NameOfCookie.length+1;
                end = document.cookie.indexOf(";", begin);
                if (end == -1) end = document.cookie.length;
                return unescape(document.cookie.substring(begin, end));
            }
         }
        return null;
    }

    //Récupère la version d'Internet Explorer, si c'est un autre navigateur la fonction renvoie -1
    function getInternetExplorerVersion() {
      var rv = -1;
      if (navigator.appName == 'Microsoft Internet Explorer') {
        var ua = navigator.userAgent;
        var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
          rv = parseFloat( RegExp.$1 );
      } else if (navigator.appName == 'Netscape') {
        var ua = navigator.userAgent;
        var re  = new RegExp("Trident/.*rv:([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
          rv = parseFloat( RegExp.$1 );
      }
      return rv;
    }

    //Effectue une demande de confirmation de DNT pour les utilisateurs d'IE
    function askDNTConfirmation() {
        var r = confirm("L'option \"Do Not Track\" de votre navigateur est activée, confirmez-vous vouloir appliquer de cette option sur ce site ?")
        return r;
    }

    //Vérifie la valeur de navigator.DoNotTrack pour savoir si le signal est activé et est à 1
    function notToTrack() {
        if ( (navigator.doNotTrack && (navigator.doNotTrack=='yes' || navigator.doNotTrack=='1'))
            || ( navigator.msDoNotTrack && navigator.msDoNotTrack == '1')
            || (window.doNotTrack && (window.doNotTrack=='1'))
        ) {
            return askDNTConfirmation();
        }
    }

    //Si le signal est à 0 on considére que le consentement a déjà été obtenu
    function isToTrack() {
        if ( (navigator.doNotTrack && (navigator.doNotTrack=='no' || navigator.doNotTrack==0 ))
            || ( navigator.msDoNotTrack && navigator.msDoNotTrack == '0')
            || (window.doNotTrack && (window.doNotTrack=='0'))
        ) {
            return true;
        }
    }

    // Fonction d'effacement des cookies
    function delCookie(name) {
        var path = ";path=" + "/";
        var expiration = "Thu, 01-Jan-1970 00:00:01 GMT";
        document.cookie = name + "=" + path +" ; "+ getCookieDomainName() + ";expires=" + expiration;
    }

    // Efface tous les types de cookies utilisés par Google Analytics
    function deleteAnalyticsCookies() {
        var cookieNames = ["__utma","__utmb","__utmc","__utmz","_ga","_gat"]
        for (var i=0; i<cookieNames.length; i++)
            delCookie(cookieNames[i])
    }

    //La fonction qui informe et demande le consentement. Il s'agit d'un div qui apparait au centre de la page
    function createInformAndAskDiv() {
        var oEl = document.getElementById("document");
        var div = document.createElement('div');
        div.setAttribute('id','inform-and-ask');
        div.style.width= window.innerWidth+"px" ;
        div.style.height= window.innerHeight+"px";
        div.style.display= "none";
        // Le code HTML de la demande de consentement
        // Vous pouvez modifier le contenu ainsi que le style
        div.innerHTML = '<div id="inform-and-consent" role="dialog" tabindex="-1" aria-labelledby="consentTitle" aria-describedby="consentDesc" aria-hidden="true">\
            <div>\
                <span id="consentTitle">\
                    <strong><?php echo $oModule->i18n('cnil_titre_popup')?></strong>\
                </span>\
            </div>\
            <br>\
            <div id="consentDesc">\
                <?php echo str_replace("\r", '', $oModule->i18n('cnil_contenu_popup'));?>\
            </div>\
            <div id="consentChoice">\
                <input type="button" id="ask-accept" value="<?php echo $oModule->i18n('cnil_btn_popup_accepter')?>" tabindex="-1">\
                <input type="button" id="ask-oppose" value="<?php echo $oModule->i18n('cnil_btn_popup_opposer')?>" tabindex="-1">\
            </div>\
            <a id="consentClose"><?php echo $oModule->i18n('cnil_btn_popup_close')?></a>\
        </div>';
        oEl.insertBefore(div,oEl.firstChild); // Ajoute la banniére juste au début de la page
    }

    function isClickOnOptOut(evt) { // On ne prends pas en compte si la cible de l'évenement est la banière ou la modale (sauf sur le bouton "accepter")
        return(("cookie-banner" == evt.target.id) || ($("#cookie-banner").has(evt.target).length > 0) || ("inform-and-ask" == evt.target.id) || (($("#inform-and-ask").has(evt.target).length > 0) && (evt.target.id != "ask-accept")));
    }

    function consent(evt) {
        if (!isClickOnOptOut(evt) ) { // On vérifie qu'il ne s'agit pas d'un clic sur la banniére ou la modale
            if (!clickprocessed) {
                evt.preventDefault();
                document.cookie = 'hasConsent=true; '+ getCookieExpireDate() +' ; ' +  getCookieDomainName() + ' ; path=/';
                delCookie(disableStr);
                callGoogleAnalytics();
                <?php
                if (preg_match($gaMarqueurPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaMarqueur)) {
                    echo $gaMarqueur[1] . "('create', gaProperty, { 'storage': 'none', 'clientId': '0'});\r\n";
                    echo $gaMarqueur[1] . "('send', 'event', 'Conformité CNIL', 'Consentement', {'eventLabel': location, 'nonInteraction': 1});\r\n";
                }
                ?>
                clickprocessed = true;
                tagAnalyticsCNIL.CookieConsent.hideModal();
                $("#cookie-banner").attr('tabindex', '-1');
                $("#cookie-banner").hide();
                window.setTimeout(function () {
                    if (eval("typeof evt.target." + evt.type) === "function") {
                        eval("evt.target."+evt.type+ "();");
                    }}, 1000
                )
            }
        }
    }

    // Cette fonction en test permet de faire une call GA  afin de pouvoir compter le nombre de visite sans faire de suivi des utilisateurs (fonction en cours de test)
    // Cela crée un evenement page qui est consultable depuis le panneau evenement de GA
    // Potentiellement cette méthode pourrait être utilisé pour comptabiliser les click sur l'opt-out
    function callGABeforeConsent() {
        <?php
        // Récupération de l'ID de suivi
        if (preg_match($gaPropertyPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaProperty)) {
        ?>
            var gaProperty = '<?php echo escapeJS($gaProperty[0])?>';
            <?php
            // Récupération du marqueur seul et du nom de l'objet GA (on ne lance pas les fonctions du marqueur "ga('create'...);", "ga('send'...);", etc...
            if (preg_match($gaMarqueurPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaMarqueur)) {
                // On inclut le marqueur seul :
                echo $gaMarqueur[0] . "\r\n";
                // Et on appel les fonctions ne necessitant pas de cookie
                echo $gaMarqueur[1] . "('create', gaProperty, { 'storage': 'none', 'clientId': '0'});\r\n";
                echo $gaMarqueur[1] . "('send', 'event', 'Conformité CNIL', 'Affichage bannière', {'eventLabel': location, 'nonInteraction': 1});\r\n";
            }
        }
        ?>
    }

    // Tag Google Analytics, cette version est avec le tag Universal Analytics
    function callGoogleAnalytics() {
        if (firstCall) return;
        else firstCall = true;
        <?php
        // Appel du marqueur complet
        echo strip_tags(CMS::getCurrentSite()->getField('SIT_GA_TAG'))
        ?>
    }

    return {

        // La fonction d'opt-out
        gaOptout: function () {
            <?php
            if (preg_match($gaMarqueurPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaMarqueur)) {
                echo $gaMarqueur[1] . "('create', gaProperty, { 'storage': 'none', 'clientId': '0'});\r\n";
                echo $gaMarqueur[1] . "('send', 'event', 'Conformité CNIL', 'Refus', {'eventLabel': location, 'nonInteraction': 1});\r\n";
            }
            ?>
            document.cookie = disableStr + '=true;'+ getCookieExpireDate() + ' ; ' +  getCookieDomainName() +' ; path=/';
            document.cookie = 'hasConsent=false;'+ getCookieExpireDate() + ' ; ' +  getCookieDomainName() + ' ; path=/';
            // on considère que le site a été visité
            clickprocessed = true;
            var div = document.getElementById('cookie-banner');
            // Ci dessous le code de la bannière affichée une fois que l'utilisateur s'est opposé au dépot
            // Vous pouvez modifier le contenu et le style
            if ( div!= null ) div.innerHTML = '<div id="cookie-banner-message">\
                <?php echo str_replace("\r", '', $oModule->i18n('cnil_txt_bandeau_cnil_refus'));?>\
                <a href="javascript:tagAnalyticsCNIL.CookieConsent.showModal()" >\
                    <?php echo $oModule->i18n('cnil_lien_savoir_plus');?>\
                </a></div>';
            window[disableStr] = true;
            deleteAnalyticsCookies();
        },

        showModal: function () {
            $("#cookie-banner").attr('tabindex', '-1');
             //* Pour tous click en dehord de la modale
            $(document).bind('click', function (evt) {
                if (("inform-and-consent" != evt.target.id) && ($("#inform-and-consent").has(evt.target).length == 0)) {
                    // On redonne le focus à ladite modale
                    if (!consentFocused) {
                        consentFocused = $("#inform-and-consent input, #inform-and-consent a").first().get(0);
                    }
                    consentFocused.focus();
                    evt.stopPropagation;
                    return false;
                }
            });
            //*/
            //* Gestion de la navigation au clavier après avoir donné le focus à la modale (#inform-and-consent)
            $("a").bind('focus', function (evt) {
                if (("inform-and-consent" != evt.target.id) && ($("#inform-and-consent").has(evt.target).length == 0)) {
                    // On redonne le focus à la modale
                    if (!consentFocused) {
                        consentFocused = $("#inform-and-consent input, #inform-and-consent a").first().get(0);
                    }
                    consentFocused.focus();
                    evt.stopPropagation;
                    return false;
                }
            });
            //*/
            $("#inform-and-ask").show();
            $("#inform-and-consent").attr('aria-hidden', 'false');
            $("#inform-and-consent").attr('tabindex', '0');
            var i= 1;
            $("#inform-and-consent input, #inform-and-consent a").each(function () {
                $( this ).attr('tabindex', i++);
            });
            $("#inform-and-consent input, #inform-and-consent a").first().get(0).focus();
            clickprocessed = false;
        },

        hideModal : function () {
            $("#inform-and-consent").attr('aria-hidden', 'true');
            $("#inform-and-consent").attr('tabindex', '-1');
            $("#inform-and-consent input, #inform-and-consent a").attr('tabindex', '-1');
            $(document).unbind('click');
            $("a").unbind('focus');
            $("#inform-and-ask").hide();
            $("#cookie-banner").attr('tabindex', '0');
            $("#cookie-banner").get(0).focus();
        },

        start: function () {
            <?php
            if (preg_match($gaPropertyPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaProperty)
                && preg_match($gaMarqueurPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaMarqueur))
            {
            ?>
            gaProperty = '<?php echo escapeJS($gaProperty[0])?>';
            // Désactive le tracking si le cookie d’Opt-out existe déjà.
            disableStr = 'ga-disable-' + gaProperty;
            //Ce bout de code vérifie que le consentement n'a pas déjà été obtenu avant d'afficher la bannière
            var consentCookie =  getCookie('hasConsent');
            clickprocessed = false;
            if (!consentCookie) {//L'utilisateur n'a pas encore de cookie, on affiche la banniére et si il clique sur un autre élément que la banniére, on enregistre le consentement
                if ( notToTrack() ) { //L'utilisateur a activé "Do Not Track". Do not ask for consent and just opt him out
                    <?php
                    // Déclenchement de l'évt associé à l'activation de l'option "Ne pas être pisté"
                    // On inclut le marqueur seul :
                    echo $gaMarqueur[0] . "\r\n";
                    // Et on appel les fonctions ne necessitant pas de cookie
                    echo $gaMarqueur[1] . "('create', gaProperty, { 'storage': 'none', 'clientId': '0'});\r\n";
                    echo $gaMarqueur[1] . "('send', 'event', 'Conformité CNIL', 'Do Not Track', {'eventLabel': location, 'nonInteraction': 1});\r\n";
                    ?>
                    $( window ).load(function () {
                        tagAnalyticsCNIL.CookieConsent.gaOptout();
                        alert("L'option \"Ne pas être pisté\" ou\"Interdire le suivi\" de votre navigateur est activée.\r\nNous respectons votre choix et ne déposons pas les cookies utilisés pour la mesure d'audience.");
                    });
                } else {
                    if (isToTrack() ) { //DNT is set to 0, no need to ask for consent just set cookies
                        consent();
                    } else {
                        $(document).ready(function () {
                            showBanner();
                            $("a").not($("#cookie-banner a, #inform-and-consent a, .external")).click(function (evt) {
                                consent(evt);
                            });
                            $("form").submit(function (evt) {
                                consent(evt);
                                return true;
                            });
                            $("#consentClose").click(function (evt) {
                                tagAnalyticsCNIL.CookieConsent.hideModal();
                                return true;
                            });
                            $("#inform-and-consent").keydown(function (evt) {
                                return evtKeyDown(evt);
                            });
                            $( "#inform-and-consent").focusin(function (evt) {
                                if (evt.target.id == "inform-and-consent") {
                                    consentFocused = $("#inform-and-consent input, #inform-and-consent a").first().get(0);
                                } else {
                                    consentFocused = evt.target;
                                }
                                return false;
                            });
                            $("#ask-accept").click(function (evt) {
                                consent(evt);
                            });
                            $("#ask-oppose").click(function (evt) {
                                tagAnalyticsCNIL.CookieConsent.gaOptout();
                                tagAnalyticsCNIL.CookieConsent.hideModal();
                            });
                        });
                        callGABeforeConsent();
                    }
                }
            } else {
                if (document.cookie.indexOf('hasConsent=false') > -1)
                    window[disableStr] = true;
                else
                    callGoogleAnalytics();
            }
            <?php
            // si pas de marqueur conforme ou attendu, on l'insére directement
            } else {
                echo strip_tags(CMS::getCurrentSite()->getField('SIT_GA_TAG'));
            } // FIN : if (preg_match($gaPropertyPattern, CMS::getCurrentSite()->getField('SIT_GA_TAG'), $gaProperty)) {} else {}
            ?>
        }
    }

}();
