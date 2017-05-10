<?php
require_once PHYSICAL_PATH . 'include/phpmailer/class.phpmailer.php';
require_once PHYSICAL_PATH . 'include/phpmailer/class.smtp.php';
require_once CLASS_DIR . 'class.db_emailTemplate.php';

class CMSMailer extends PHPMailer
{
    public static $aKey = array(
        '[DATE_JMA]' => 'Date du jour au format jj/mm/aaaa',
        '[SERVER_URL]' => 'URL absolue du site',
        '[SIT_TITLE]' => 'Libellé du site courant'
    );

    private $_aReplace = array();

    private $_oEmailTemplate;

    public function __construct($template)
    {
        parent::__construct();
        $this->_oEmailTemplate = new EmailTemplate($template);
        $this->CharSet = 'utf-8';
        if (EMAIL_SMTPHOST != '') {
            $this->Host = EMAIL_SMTPHOST;
            $this->isSMTP();
        } else {
            $this->isMail();
        }
        $_oSite = CMS::getCurrentSite();
        if ($this->_oEmailTemplate->getField('EMT_EXPEDITEURFROM') != '') {
            $this->SetFrom($this->_oEmailTemplate->getField('EMT_EXPEDITEURFROM'), $this->_oEmailTemplate->getField('EMT_EXPEDITEURFROMNAME'));
        } elseif ($this->_oEmailTemplate->getField('EMT_EXPEDITEUR') == 'USER' && $oConnected = Utilisateur::getConnected()) {
            $this->SetFrom($oConnected->getField('UTI_EMAIL'), $oConnected->getField('UTI_NOM') . ' ' . $oConnected->getField('UTI_PRENOM'));
        } elseif ($this->_oEmailTemplate->getField('EMT_EXPEDITEUR') == 'SITE' && $_oSite && $_oSite->getField('SIT_EMAIL')) {
            $this->SetFrom($_oSite->getField('SIT_EMAIL'));
        } else {
            $this->SetFrom(EMAIL_FROM, EMAIL_FROMNAME);
        }
        $this->isHTML(true);
        $this->SetLanguage('fr', PHYSICAL_PATH . 'include/phpmailer/language/');
        //clés prédéfinies
        $this->_aReplace['[DATE_JMA]'] = date('d/m/Y');
        if ($_oSite) {
            $this->_aReplace['[SERVER_URL]'] = (($_SERVER['HTTPS'] != 'on') ? 'http' : 'https') . '://' . CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT;
            $this->_aReplace['[SIT_TITLE]'] = $_oSite->getField('SIT_TITLE');
        }
    }

    public function send()
    {
       $this->Subject = $this->_makeSubject();
       $this->Body = $this->_makeBodyHTML();
       $this->AltBody = $this->_makeBodyTXT();
       if (EMAIL_SUBSTITUTION) {
           $this->Body = $this->Body . '<hr>Envoi depuis ' . $_SERVER['HTTP_HOST'] . '<br>Destinataires initiaux :<br>' . implode(', ', array_keys($this->all_recipients));
           $this->ClearAllRecipients();
           foreach (explode(';', EMAIL_SUBSTITUTION) as $email) {
               $this->AddAddress($email);
           }
       }
       return parent::Send() ? 1 : $this->ErrorInfo;
    }

    public function replace($key, $val)
    {
        $this->_aReplace[$key] = $val;
    }

    private function _makeBodyHTML()
    {
        $body = "<html><head><title>" . $this->_oEmailTemplate->getField('EMT_SUJET'). "</title></head><body>";
        $body .= $this->_oEmailTemplate->getField('EMT_BODYHTML');
        $body .= "</body></html>";
        return str_replace(array_keys($this->_aReplace), array_values($this->_aReplace), $body);
    }

    private function _makeBodyTXT()
    {
        $body = strip_tags($this->_oEmailTemplate->getField('EMT_BODYHTML'));
        return str_replace(array_keys($this->_aReplace), array_values($this->_aReplace), $body);
    }

    private function _makeSubject()
    {
        return str_replace(array_keys($this->_aReplace), array_values($this->_aReplace), $this->_oEmailTemplate->getField('EMT_SUJET'));
    }
}
