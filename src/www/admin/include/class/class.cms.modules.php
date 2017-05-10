<?php
/**
 * Classe faisant l'interface entre le CMS et les modules (enregistré et disponibles)
 * Librement inspirée de l'architecture @Dotclear<dotclear.net> qui correspond bien - pour partie - à notre besoin
 */
abstract class CMS_modules
{
    protected $modules = array(); //< Liste des modules disponible

    abstract public function loadModules();

    public function getModules($id=null)
    {
        if ($id && isset($this->modules[$id])) {
            return $this->modules[$id];
        } elseif ($id) {
            return null;
        }

        return $this->modules;
    }

    /**
    Retourne - si elle existe - une information sur le module
    - root
    - nom
    - description
    - auteur
    - version
    - dépendances
    @param string $id identifiant du module (nom du répertoire ou in se trouve dans MODULES_DIR)
    @param string $info Nom de l'information à retourner
    @return	string
    */
    public function moduleInfo($id,$info)
    {
        return isset($this->modules[$id][$info]) ? $this->modules[$id][$info] : null;
    }
    /**
     * Tri l'ensemble des modules par ordre de dépendance içnversée (les - dependant en premier)
     * @param  string $id Indentifiant d'un module dont on veut ordonner les dépendances
     * @return array  le nom des modules par ordre croissant de presence comme dépendance d'autres modules et sous modules
     */
    protected function sortDependency($id = null)
    {
        static $dependency = array();
        if (empty($id)) {
            $ids = array_keys($this->modules);
            foreach ($ids as $id) {
                $this->sortDependency($id);
            }
        }
        $mdl = $this->getModules($id);
        // Si dépendance : on augmente réccurcivenement les poids de chacune des dépendances
        if ($mdl && !empty($mdl['dependency'])) {
            // Pour chacune des dépendances
            foreach (array_keys($mdl['dependency']) as $dep_id) {
                if (!isset($dependency[$dep_id])) {$dependency[$dep_id] = 0;}
                $dependency[$dep_id]++;
                $this->sortDependency($dep_id);
            }
        }
        arsort($dependency, SORT_NUMERIC);

        return array_keys($dependency);
    }

    protected function sortModules()
    {
        // Tri alpha sur les ID sans dépendance avec la casse
        $ids = array_keys($this->modules);
        $k = array_map('strtolower', $ids);
        array_multisort($k, SORT_ASC, SORT_STRING, $this->modules);
        // Tri sur les dépendances aux modules (mais pas de leur version pour le moment)
        $dependency = $this->sortDependency();
        $aDep = array();
        foreach ($dependency as $id) {
            // On suprime le module de la liste des modules et on le repostitione en tête des dépendances
            //$mdl = array_splice($this->modules, array_search($id, array_keys($this->modules)), 1);
            //$aDep = array_merge($aDep, $mdl);
            $aDep = array_merge($aDep,
                array_splice($this->modules, array_search($id, array_keys($this->modules)), 1)
            );
        }
        $this->modules = array_merge($aDep, $this->modules);
    }
    /**
     * Valide les dépendance d'un module (présence de l'autre module dans la bonne version)
     * @param  string $id     Indentifiant d'un module
     * @param  string $dep_id Identifiant éventuel de la dépendance qu'il faut valider la bonne version
     * @return array  tableau des résultats sur les contrôles
     */
    protected function checkDependency($id, $dep_id = null)
    {
        $mdl = $this->getModules($id);
        if (empty($mdl['dependency'])) {
            return null;
        // Si présence de dépendance
        } else {
            $res = array('success'=>array(),'failure'=>array());
            // Si pas de $dep_id ...
            // On valide la présence des dites dépendances
            if (is_null($dep_id)) {
                foreach (array_keys($mdl['dependency']) as $dep_id) {
                    $res = array_merge_recursive($res, $this->checkDependency($id, $dep_id));
                }

                return $res;
            }

            // Si le module n'existe pas
            if (! $dep_mdl = $this->getModules($dep_id)) {
                $res['failure'][$dep_id] = 'Le module "<strong>'.$dep_id.'</strong>" n\'est pas disponible alors qu\'il est nécessaire.';
            } else {
                // On récupère la version du module dispo
                $dep_version = $this->moduleInfo($dep_id, 'version');
                // On récupère les versions demandées dans les dépendances
                // Si la valeur de version de la dépendance est un tableau,
                // ==> il y a une dépendance par rapport à une version min et max
                if (is_array($mdl['dependency'][$dep_id])) {
                    $dep_min = $mdl['dependency'][$dep_id][0];
                    $dep_max = $mdl['dependency'][$dep_id][1];
                } else {
                    $dep_min = $mdl['dependency'][$dep_id];
                    $dep_max = null;
                    // Si la valeur minimale comporte la chaine ".x",
                    // il sagit d'une désignation de toutes les versions
                    // de la plage mentionnée (1.1.x >= {version mdl} < 1.2.x)
                    if (stripos(strtolower($dep_min), '.x') !== false) {
                        $minParts = explode('.',$dep_min);
                        $dep_max = '';
                        $prevValue = '';
                        foreach ($minParts as $v) {
                            if ($prevValue != '' && $dep_max!='') $dep_max .= '.';
                            if (strtolower($v) == 'x' && is_numeric($prevValue)) {
                                $dep_max .=  ($prevValue + 1);
                            } elseif ($prevValue != '') {
                                $dep_max .= $prevValue;
                            }
                            $prevValue = $v;
                        }
                        if ($v != '') $dep_max .= '.';
                        if (strtolower($v) == 'x' && is_numeric($v)) {
                            $dep_max .= ($v + 1);
                        } elseif (!empty($v)) {
                            $dep_max .= $v;
                        }
                    }
                }
                if ($dep_max) {
                    if (version_compare($dep_version, $dep_min, '>=') &&
                        version_compare($dep_version, $dep_max, '<')
                    ) {
                        $res['success'][$dep_id] = true;
                    } else {
                        $res['failure'][$dep_id] = 'La version <strong>'.$dep_version.'</strong> du module "<strong>'.$dep_id.'</strong>" ne rentre pas le cadre des versions nécessaires (entre <strong>'.$dep_min.'</strong> et <strong>'.$dep_max.'</strong> non incluse)';
                    }
                } else {
                    if (version_compare($dep_version, $dep_min, '=')) {
                        $res['success'][$dep_id] = true;
                    } else {
                        $res['failure'][$dep_id] = 'La version <strong>'.$dep_version.'</strong> du module "<strong>'.$dep_id.'</strong>" ne correspond pas à la version nécessaire (<strong>'.$dep_min.'</strong>).';
                    }
                }
            }

            return $res;
        }
    }
    /**
     * Valide les dépendances d'un module
     * @param  string    $id identifiant du module à tester
     * @return bool|null Les dépendances sont valides (true) ou non (false) ou NA (null)
     */
     public function dependencyIsChecked($id)
     {
         $res = $this->checkDependency($id);

         return is_null($res)?null:empty($res['failure']);
     }
}

class CMS_registeredModules extends CMS_modules
{
    private $dbh = null; //< Connection
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->dbh = DB_logger :: getInstance();
    }
    public function loadModules()
    {
        if (empty($this->modules)) {
            // Si la table existe (que le module "core" est installé)
            try {
                $sql = 'SELECT MOD_CODE, MOD_SIGNATURE FROM DD_MODULES';
                $rowListe = $this->dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                foreach ($rowListe as $row) {
                    $this->modules[$row['MOD_CODE']] = unserialize($row['MOD_SIGNATURE']);
                }
            } catch (Exception $e) {
                return;
            }
            $this->sortModules();
        }
    }
    /**
     * Enregistre une version d'un module.
     * @param string      $id     Identifiant du module
     * @param CMS_modules $module
     */
    public function registerModule($id,$module)
    {
        if (is_null($module)) {return;}
        if ($this->moduleInfo($id, 'version') === null) {
            $sql = 'insert into DD_MODULES (
                MOD_CODE,
                MOD_VERSION,
                MOD_SIGNATURE,
                MOD_DATETIME
            ) value (
                '.$this->dbh->quote($id).',
                '.$this->dbh->quote($module['version']).',
                '.$this->dbh->quote(serialize($module)).',
                '.$this->dbh->quote($module['datetime']).'
            )';
        } else {
            $sql = 'update DD_MODULES
                    set MOD_VERSION='.$this->dbh->quote($module['version']).',
                    MOD_SIGNATURE='.$this->dbh->quote(serialize($module)).',
                    MOD_DATETIME='.$this->dbh->quote($module['datetime']).'
                    where MOD_CODE='.$this->dbh->quote($id);
        }
        $this->dbh->exec($sql);
        $this->modules[$id] = $module;
    }
}
class CMS_availableModules extends CMS_modules
{
    private $id; //< Variable utilisée pour nommé l'identifiant de chacun des modules (nom du répertoire qui le défini)
    private $mroot; //< Variable utilisée pour nommé la racine de chacun des modules
    # Inclusion variables
    private static $superglobals = array('GLOBALS','_SERVER','_GET','_POST','_COOKIE','_FILES','_ENV','_REQUEST','_SESSION');
    private static $_k;
    private static $_n;
    /**
     * Constructeur
     */
    public function __construct() {}

    /**
     * Tente de charger l'ensemble des modules dispo dans le repertoire MODULES_DIR
     */
    public function loadModules()
    {
        if (empty($this->modules)) {
            //Le repertoire doit exister et être disponible à la lecture
            if (!is_dir(MODULES_DIR) || !is_readable(MODULES_DIR)) {
                throw new Exception('Le répertoire "'.MODULES_DIR.'" n\'éxiste pas ou ne peut être lu');
                exit;
            }
            // On tente de récupèrer un objet "Directory"
            if (($dir = @dir(MODULES_DIR)) === false) {
                exit;
            }
            while (false !== ($entry = $dir->read())) {
                $path = MODULES_DIR . $entry;
                // Si l'entrée représente un module (repertoire avec un "_define.php")
                if ($entry != '.' && $entry != '..' && is_dir($path) && file_exists($path.'/_define.php')) {
                    $this->id = $entry;
                    $this->mroot = str_replace(PHYSICAL_PATH, '',$path);
                    // On tente de l'ajouter à la liste des modules
                    // (=>le _define.php doit implémenter un truc du genre "$this->registerModule()")
                    require PHYSICAL_PATH.$this->mroot.'/_define.php';
                    $this->id = null;
                    $this->mroot = null;
                }
            }
            $dir->close();
            $this->sortModules();
        }
    }
    /**
    Cette methode tente d'installer les modules qui ont un fichier "_install.php"

    @see cmsModules::installModule
    */
    public function installModules()
    {
        $res = array('success'=>array(),'failure'=>array());
        foreach ($this->modules as $id => &$m) {
            $err = '';
            $i = $this->installModule($id, $err);
            if ($i === true) {
                $res['success'][$id] = !empty($err)?$err:true;
            } elseif ($i === false) {
                $res['failure'][$id] = $err;
            }
        }

        return $res;
    }

    /**
     * Cette methode tente d'installer un module à partir de son fichier "_install.php"
     * Cette methode true en cas de succes, false en cas d'erreur non bloquante, ou une erreur en cas... d'erreur :p
     *
     * @param  string  $id  Identifiant du module
     * @param  string  $err (in / out) message passé par référence contenant l'éventuelle erreur d'installation
     * @return boolean
     */
    public function installModule($id,&$err)
    {
        //$checkDep = $this->checkDependency($id); // Valide les dépendances sur les modules dispo (mais n'assure pas qu'une installe ratée mette en danger l'installe d'un autre module après)
        //* Validation des dépendances en DB pour être sûr (ok mais pas satisfaisant au niveau performance)
        $registeredMdl = new CMS_registeredModules();
        $registeredMdl->loadModules();
        $registeredMdl->modules[$id] = $this->getModules($id);
        $checkDep = $registeredMdl->checkDependency($id);
        //*/
        if (!empty($checkDep['failure'])) {
            //$err .= '<!-- <p>Erreur sur le contrôle des dépendances :</p> -->';
            $err .= '<ul>';
            foreach ($checkDep['failure'] as $id => $s) {
                $err .= "\n<li>".$s."</li>";
            }
            $err .= '</ul>';

            return false;
        }
        //*/
        $f  = PHYSICAL_PATH.$this->modules[$id]['root'].'/_install.php';
        if (!file_exists($f)) {
            throw new Exception('Le fichier "'.$f.'" n\'est pas disponible.');
        }
        $i = require $f;
        if ($i === true) {
            return true;
        } elseif ($i === false) {
             return false;
        }

        return null;
    }
    /**
     * Ajoute un nouveau module à la liste existante
     * @param string $nom         Nom du plugin
     * @param string $description Déscription (pas trop longue) du module
     * @param string $auteur      Nom de la personne référante du module
     * @param string $version     Numéro de version du module
     * @param array  $dependency  Tableau des dépendances et de leurs versions
     */
    public function registerModule($nom, $description, $auteur, $version, $dependency = array())
    {
        if ($this->id) {
            // Tri alpha sans dépendance avec la casse des dépendances
            $id = array_keys($dependency);
            $id = array_map('strtolower', $id);
            array_multisort($id, SORT_ASC, SORT_STRING, $dependency);
            $this->modules[$this->id] = array(
                'id' => $this->id,
                'root' => $this->mroot,
                'name' => $nom,
                'desc' => $description,
                'author' => $auteur,
                'version' => $version,
                'datetime' => date('Y-m-d H:i:s',filemtime(PHYSICAL_PATH.$this->mroot.'/_define.php')),
                'dependency' => $dependency
            );
        }
    }
}
