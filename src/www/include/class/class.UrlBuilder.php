<?php

class UrlBuilder
{

    /**
     *
     * @var Page
     */
    protected $_oPage = null;

    /**
     *
     * @var Template
     */
    protected $_oTemplate = null;

    /**
     * Paramètres de la requete
     *
     * @var array
     */
    protected $_aParam = array();

    /**
     * Répertoires virtuels de l'URL
     *
     * @var array
     */
    protected $_aUrlDirectory;

    /**
     * Paramètres à passer sans rewritting
     *
     * @var array
     */
    protected $_aParamToSet;

    /**
     *
     * @var string
     */
    protected $_urlrewriting = null;

    /**
     *
     * @var string
     */
    protected $_uri = null;

    /**
     * Constructor
     *
     * @param  Page                     $oPage
     * @param  array                    $aParam
     * @throws InvalidArgumentException
     */
    public function __construct(Page $oPage)
    {
        if (! $oPage->exist()) {
            throw new InvalidArgumentException('L\'instance d\'objet Page n\'est pas valide.');
        }
        $this->_oPage = $oPage;
        $this->setUrlrewriting($oPage->getField('PAG_URLREWRITING'));
    }

    public function build()
    {
        if (null === $this->_uri) {

            $this->_uri = '';
            $this->loadUrlDirectories();

            if (! empty($this->_aUrlDirectory)) {
                $this->_uri .= implode(DIRECTORY_SEPARATOR, $this->getUrlDirectories()) . DIRECTORY_SEPARATOR;
            }

            if ($this->getTemplate() && $this->getTemplate()->getField('TPL_REWRITEURL') != '') {
                $this->setUrlrewriting($this->getUrlParser()->parse());
            }
            $this->_uri .= $this->getPage()->getID() . '-' . $this->getUrlrewriting() . '.htm';

            if (! empty($this->_aParamToSet)) {
                $this->_uri .= '?' . http_build_query($this->_aParamToSet);
            }
        }

        return $this;
    }

    public function setUri($uri)
    {
        $this->_uri = (string) $uri;

        return $this;
    }

    public function retrieve($uri)
    {
        require_once CLASS_DIR . 'class.db_template.php';

        $this->_aUrlDirectory = array();
        $this->_aParam = array();

        $aDir = explode('/', $uri);
        if ($uri{0} != '/') {
            $uri = '/' . $uri;
        }
        // $info est de la forme [ID_PAGE]-[PAG_URLREWRITING].htm ou [ID_PAGE]
        $info = array_pop($aDir);

        if (count($aDir) > 0) {

            $dbh = DB::getInstance();

            if ($aDir[0] == 'TPL_CODE') {

                $sql = 'select *
                        from DD_TEMPLATE
                        where TPL_CODE = ' . $dbh->quote($aDir[1]);

                if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {

                    $this->_oTemplate = new Template($row['TPL_CODE']);
                    $this->_oTemplate->setFields($row);

                    $this->addParam('TPL_CODE', $row['TPL_CODE']);
                    $this->_aUrlDirectory[] = 'TPL_CODE';
                    $this->_aUrlDirectory[] = $aDir[1];

                    if (array_key_exists(2, $aDir) && $aDir[2] == 'PAR_TPL_IDENTIFIANT') {
                        $this->addParam('PAR_TPL_IDENTIFIANT', $this->_urldecode($aDir[3]));
                        $this->_aUrlDirectory[] = 'PAR_TPL_IDENTIFIANT';
                        $this->_aUrlDirectory[] = $aDir[3];
                    }
                }
            } else {

                $sql = 'select *
                        from DD_TEMPLATE
                        where TPL_URLCODE = ' . $dbh->quote($aDir[0]);

                if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {

                    $this->_oTemplate = new Template($row['TPL_CODE']);
                    $this->_oTemplate->setFields($row);

                    $this->addParam('TPL_CODE', $row['TPL_CODE']);
                    $this->_aUrlDirectory[] = safeFromRfc1738($row['TPL_URLCODE']);

                    unset($aDir[0]);

                    // si le nombre de / est impair c'est qu'on a un PAR_TPL_IDENTIANT
                    if ((substr_count($uri, '/') % 2 == 1)) {
                        $this->addParam('PAR_TPL_IDENTIFIANT', $this->_urldecode($aDir[1]));
                        $this->_aUrlDirectory[] = $aDir[1];
                        unset($aDir[1]);
                    }
                }
            }
            $aDir = array_values($aDir);
            for ($i = 0; $i < count($aDir); $i += 2) {

                $this->_aUrlDirectory[] = $aDir[$i];
                $this->_aUrlDirectory[] = $aDir[$i + 1];

                $key = $this->_urldecode($aDir[$i]);
                $val = $this->_urldecode($aDir[$i + 1]);

                $this->addParam($key, $val);
            }
        }

        // ajout des parametres apres '?'
        $strParamsGetStartPos = strpos($uri, '?');
        if ($strParamsGetStartPos !== false) {
            $strParamsGet = substr($uri, $strParamsGetStartPos + 1);
            $aParamsGet = array();
            parse_str($strParamsGet, $aParamsGet);
            if (get_magic_quotes_gpc()) {
                $aParamsGet = stripslashes_deep($aParamsGet);
            }
            $this->_aParam = array_merge($aParamsGet, $this->_aParam);
        }
    }

    public function getUri()
    {
        return $this->build()->_uri;
    }

    /**
     * Retourne une instance de Parser
     *
     * @return Parser
     */
    public function getUrlParser()
    {
        $className = 'UrlParser' . implode('', array_map('ucfirst', preg_split('/_/', str_replace('mod_', '', strtolower($this->getTemplate()->getField('MOD_CODE'))))));
        if (! file_exists(CLASS_DIR . 'class.' . $className . '.php')) {
            die("class " . CLASS_DIR . 'class.' . $className . '.php non trouvée');
        }
        require_once CLASS_DIR . 'class.' . $className . '.php';

        return new $className($this);
    }

    /**
     * Charge les répertoires virtuels de l'URL dans un tableau, en encodant
     * chaque entrées du tableau
     *
     * @return UrlBuilder
     */
    public function loadUrlDirectories()
    {
        if (null === $this->_aUrlDirectory) {
            $this->_aUrlDirectory = array();
            $skipOptions = array();

            if ($this->hasParam('TPL_CODE')) {
                require_once CLASS_DIR . 'class.db_template.php';
                $this->_oTemplate = new Template($this->getParam('TPL_CODE'));

                if (! $this->_oTemplate->exist()) {
                    $this->_oTemplate = false;
                } elseif ($this->_oTemplate->getField('TPL_URLCODE') != '') {
                    $skipOptions[] = 'TPL_CODE';
                    $this->_aUrlDirectory[] = $this->_urlencode(safeFromRfc1738($this->_oTemplate->getField('TPL_URLCODE')));
                    if ($this->hasParam('PAR_TPL_IDENTIFIANT')) {
                        $skipOptions[] = 'PAR_TPL_IDENTIFIANT';
                        $this->_aUrlDirectory[] = $this->_urlencode($this->getParam('PAR_TPL_IDENTIFIANT'));
                    }
                }
            }

            $this->_aParamToSet = array();
            foreach ($this->_aParam as $key => $val) {
                if (! in_array($key, $skipOptions)) {
                    if ($key == 'TPL_CODE' || $key == 'PAR_TPL_IDENTIFIANT') {
                        if (is_array($val)) {
                            foreach ($val as $valBis) {
                                $this->_aUrlDirectory[] = $this->_urlencode($key . '[]');
                                $this->_aUrlDirectory[] = $this->_urlencode($valBis);
                            }
                        } else {
                            $this->_aUrlDirectory[] = $this->_urlencode($key);
                            $this->_aUrlDirectory[] = $this->_urlencode($val);
                        }
                    } else {
                        $this->_aParamToSet[$key] = $val;
                    }
                }
            }
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getUrlDirectories()
    {
        return $this->_aUrlDirectory;
    }

    /**
     *
     * @param  array      $aParam
     * @return UrlBuilder
     */
    public function setParam(array $aParam = array())
    {
        $this->_aParam = $aParam;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_aParam;
    }

    /**
     * Retourne le parametre de la requete
     *
     * @param  string $key
     * @return mixed
     */
    public function getParam($key)
    {
        if ($this->hasParam($key)) {
            return $this->_aParam[$key];
        }

        return null;
    }

    /**
     * Ajoute le parametre $key dans le tableau des parametres
     *
     * @param  string     $key
     * @param  string     $value
     * @return UrlBuilder
     */
    public function addParam($key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->_aParam[$k][] = $v;
            }
        } else {
            if (substr($key, - 2) == '[]') {
                $key = substr($key, 0, - 2);
                $this->_aParam[$key][] = $value;
            } else {
                $this->_aParam[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Le paramétre est défini dans la requete
     *
     * @param  string $key
     * @return bool
     */
    public function hasParam($key)
    {
        return array_key_exists($key, $this->_aParam);
    }

    /**
     *
     * @return string
     */
    public function getUrlrewriting()
    {
        return $this->_urlrewriting;
    }

    /**
     *
     * @param  string     $str
     * @return UrlBuilder
     */
    public function setUrlrewriting($str)
    {
        $this->_urlrewriting = (string) $str;

        return $this;
    }

    /**
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->_oPage;
    }

    /**
     *
     * @return Template
     */
    public function getTemplate()
    {
        return $this->_oTemplate;
    }

    /**
     *
     * @param  string $str
     * @return string
     */
    protected function _urlencode($str)
    {
        return urlencode(str_replace('/', '*$#£#$*', $str));
    }

    /**
     *
     * @param  string $str
     * @return string
     */
    protected function _urldecode($str)
    {
        return str_replace('*$#£#$*', '/', urldecode($str));
    }
}
