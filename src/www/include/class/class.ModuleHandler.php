<?php
class ModuleHandler
{

    /**
     * Site en cours
     *
     * @var Site
     */
    protected $_oSite;

    /**
     * Tableau d'objet ModuleGeneric
     *
     * @var array
     */
    protected $_aModule;

    /**
     * Constructeur
     *
     * @param Site $oSite
     */
    public function __construct(Site $oSite)
    {
        $this->_oSite   = $oSite;
        $this->_aModule = array();
    }

    /**
     * Retourne l'objet Module<MonModule>
     * (qui permet de gérer l'activation et la désactivation d'un module sur un site)
     * à partir d'un objet Module
     *
     * @param  Module          $oModule
     * @return ModuleGeneric
     * @throws DomainException
     */
    protected function _module(Module $oModule)
    {
        if (!in_array($oModule->getID(), $this->_aModule)) {

            $className  = 'Module' . implode
            ('', array_map
                ('ucfirst', preg_split
                    ('/_/', str_replace
                        ('mod_', '', strtolower($oModule->getID()))
                    )
                )
            );
            if (!file_exists(CLASS_DIR . 'class.' . $className  . '.php')) {
                $className = 'ModuleGeneric';
            }
            require_once CLASS_DIR . 'class.' . $className  . '.php';
            $this->_aModule[$oModule->getID()] = new $className ($oModule);
        }

        return $this->_aModule[$oModule->getID()];
    }

    /**
     * Exécute la méthode disable sur le module
     *
     * @param  Module $oModule
     * @return bool
     */
    public function disable(Module $oModule)
    {
        return $this->_module($oModule)->disable($this->_oSite);
    }

    /**
     * Exécute la méthode enable sur le module
     *
     * @param  Module $oModule
     * @return bool
     */
    public function enable(Module $oModule)
    {
        return $this->_module($oModule)->enable($this->_oSite);
    }

    /**
     * Exécute la méthode delete sur le module
     *
     * @param  Module $oModule
     * @return bool
     */
    public function delete(Module $oModule)
    {
        return $this->_module($oModule)->delete($this->_oSite);
    }

    /**
     * Exécute la méthode isDeletable sur le module
     *
     * @param  Module $oModule
     * @return bool
     */
    public function isDeletable(Module $oModule)
    {
        return $this->_module($oModule)->isDeletable($this->_oSite);
    }
}
