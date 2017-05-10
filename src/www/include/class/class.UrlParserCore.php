<?php

class UrlParserCore
{

    /**
     *
     * @var UrlBuilder
     */
    protected $_oUrlBuilder;

    private $_oExterne;

    /**
     * Constructor
     *
     * @param UrlBuilder $oUrlBuilder
     */
    public function __construct(UrlBuilder $oUrlBuilder)
    {
        $this->_oUrlBuilder = $oUrlBuilder;
    }

    public function setObjet($oExterne)
    {
        $this->_oExterne = $oExterne;
    }

    public function parse()
    {
        if ($this->getTemplate()) {
            // TPL_REWRITEMETHOD doit initier un objet, vérifier les regles de restitution et le fixer via setObjet
            if ($this->getTemplate()->getField('TPL_REWRITEMETHOD') != '') {
                call_user_func(array(
                    $this,
                    $this->getTemplate()->getField('TPL_REWRITEMETHOD')
                ));
            }
            $url = $this->getTemplate()->getField('TPL_REWRITEURL');
            $url = $this->getTemplate()->replaceKey($url, $this->_oExterne, $this->getUrlBuilder()->getPage()); // $this->_oExterne peut être null
            return formatUrl($url);
        }
        return '';
    }

    public function getUrlBuilder()
    {
        return $this->_oUrlBuilder;
    }

    public function getTemplate()
    {
        return $this->getUrlBuilder()->getTemplate();
    }
}
