<?php
/*
 * Classe permetant de manipuler les structures relative à une base de données
 * @contributors harmen CHRISTOPHE <harmen@eolas.fr>
 * Librement adapté et inspiré de Clearbricks <http://clearbricks.org/>
 * (Copyright (c) 2006 Olivier Meunier and contributors - GNU General Public License)
 */
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2006 Olivier Meunier and contributors. All rights
# reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****

class dbStruct
{
	protected $con;
	protected $prefix;
	protected $tables = array();
	protected $references = array();
	
	public function __construct($con,$prefix='')
	{
		$this->con =& $con;
		$this->prefix = $prefix;
	}
	
	public function driver()
	{
		return $this->con->driver();
	}
	
	public function table($name)
	{
		$this->tables[$name] = new dbStructTable($name);
		return $this->tables[$name];
	}
	
	public function __get($name)
	{
		if (!isset($this->tables[$name])) {
			return $this->table($name);
		}
		
		return $this->tables[$name];
	}
	
	public function reverse()
	{
		$schema = dbSchema::init($this->con);
		
		# Get tables
		$tables = $schema->getTables();
		
		foreach ($tables as $t_name)
		{
			if ($this->prefix && strpos($t_name,$this->prefix) !== 0) {
				continue;
			}
			
			$t = $this->table($t_name);
			
			# Get columns
			$cols = $schema->getColumns($t_name);
			
			foreach ($cols as $c_name => $col) {
				/*
				$type = $schema->dbt2udt($col['type'],$col['len'],$col['default']);
				$t->field($c_name,$type,$col['len'],$col['attr'],$col['null'],$col['default'],$col['extra'],true);
				//*/
				$t->field($c_name,$col['type'],$col['len'],$col['attr'],$col['null'],$col['default'],$col['extra'],true);
			}
			
			# Get keys
			$keys = $schema->getKeys($t_name);
			
			foreach ($keys as $k)
			{
				$args = $k['cols'];
				array_unshift($args,$k['name']);
				
				if ($k['primary']) {
					call_user_func_array(array($t,'primary'),$args);
				} elseif ($k['unique']) {
					call_user_func_array(array($t,'unique'),$args);
				}
			}
			
			# Get indexes
			$idx = $schema->getIndexes($t_name);
			foreach ($idx as $i)
			{
				$args = array($i['name'],$i['type']);
				$args = array_merge($args,$i['cols']);
					
				call_user_func_array(array($t,'index'),$args);
			}
			
			# Get foreign keys
			$ref = $schema->getReferences($t_name);
			foreach ($ref as $r) {
				$t->reference($r['name'],$r['c_cols'],$r['p_table'],$r['p_cols'],$r['update'],$r['delete']);
			}
		}
	}
	
	/**
	Synchronize this schema taken from database with $schema.
	
	@param	s		<b>dbStruct</b>		Structure to synchronize with
	*/
	public function synchronize($s)
	{
		if (!($s instanceof self)) {
			throw new Exception('Invalid database schema');
		}

		$dbCmp = $this->compare($s, false);

		$got_work = true;
		if(empty($dbCmp['table_create']) && empty($dbCmp['field_create']) &&
			empty($dbCmp['key_create']) && empty($dbCmp['index_create']) &&
			empty($dbCmp['reference_create']) &&
			empty($dbCmp['field_update']) && empty($dbCmp['key_update']) &&
			empty($dbCmp['index_update']) && empty($dbCmp['reference_update'])
		)
		{
			$got_work = false;
		}
		
		if (!$got_work) {
			return;
		}
		
		$schema = dbSchema::init($this->con);
		
		$reSynchronize = false;
		# Create tables
		foreach ($dbCmp['table_create'] as $table => $fields)
		{
			$schema->createTable($table,$fields);
			
			// Des champs peuvent avoir un extra qui necessitent des index au préalable (auto_increment)
			// Il est donc nedessaire de rechercher les champs qui ont ses extras.
			// S'il y en a on relance un synchro après la créaction des tables
			// pour mettre à jours ces champs au second passage
			foreach ($fields as $fname => $f)
			{
				if (in_array($f['extra'],array('auto_increment')))
				{
					$reSynchronize = true;
					break;
				}
			}
			//*/
		}
		
		# Create new fields
		foreach ($dbCmp['field_create'] as $tname => $fields)
		{
			foreach ($fields as $fname => $f) {
				$schema->createField($tname,$fname,$f['type'],$f['len'],$f['attr'],$f['null'],$f['default'],$f['extra']);
				// Des champs peuvent avoir un extra qui necessitent des index au préalable (auto_increment)
				// Il est donc nedessaire de rechercher les champs qui ont ses extras.
				// S'il y en a on relance un synchro après la créaction des tables
				// pour mettre à jours ces champs au second passage
				if (in_array($f['extra'],array('auto_increment')))
				{
					$reSynchronize = true;
				}
			}
		}
		
		# Update fields
		foreach ($dbCmp['field_update'] as $tname => $fields)
		{
			foreach ($fields as $fname => $f) {
				$schema->alterField($tname,$fname,$f['type'],$f['len'],$f['attr'],$f['null'],$f['default'],$f['extra']);
			}
		}
		
		# Create new keys
		foreach ($dbCmp['key_create'] as $tname => $keys)
		{
			foreach ($keys as $kname => $k)
			{
				if ($k['type'] == 'primary') {
					$schema->createPrimary($tname,$kname,$k['cols']);
				} elseif ($k['type'] == 'unique') {
					$schema->createUnique($tname,$kname,$k['cols']);
				}
			}
		}
		
		# Update keys
		foreach ($dbCmp['key_update'] as $tname => $keys)
		{
			foreach ($keys as $kname => $k)
			{
				if ($k['type'] == 'primary') {
					$schema->alterPrimary($tname,$kname,$k['name'],$k['cols']);
				} elseif ($k['type'] == 'unique') {
					$schema->alterUnique($tname,$kname,$k['name'],$k['cols']);
				}
			}
		}
		
		# Create indexes
		foreach ($dbCmp['index_create'] as $tname => $index)
		{
			foreach ($index as $iname => $i) {
				$schema->createIndex($tname,$iname,$i['type'],$i['cols']);
			}
		}
		
		# Update indexes
		foreach ($dbCmp['index_update'] as $tname => $index)
		{
			foreach ($index as $iname => $i) {
				$schema->alterIndex($tname,$iname,$i['name'],$i['type'],$i['cols']);
			}
		}

		// Avec MariaDB, il est nécessaire de définir complétement les clés primaires (avec "auto_increment") avant d'ajouter les clés étrangères
		if (!$reSynchronize) {
			# Create references
			foreach ($dbCmp['reference_create'] as $tname => $ref)
			{
				foreach ($ref as $rname => $r)
				{
					$schema->createReference($rname,$tname,$r['c_cols'],$r['p_table'],$r['p_cols'],$r['update'],$r['delete']);
				}
			}

			# Update references
			foreach ($dbCmp['reference_update'] as $tname => $ref)
			{
				foreach ($ref as $rname => $r) {
					$schema->alterReference($rname,$r['name'],$tname,$r['c_cols'],$r['p_table'],$r['p_cols'],$r['update'],$r['delete']);
				}
			}
		}
		
		# Flush execution stack
		$schema->flushStack();
		
		if ($reSynchronize) {
			return
			count($dbCmp['table_create']) + count($dbCmp['key_create']) + count($dbCmp['index_create']) +
			count($dbCmp['reference_create']) + count($dbCmp['field_create']) + count($dbCmp['field_update']) +
			count($dbCmp['field_update']) + count($dbCmp['index_update']) + count($dbCmp['reference_update']) + $this->synchronize($s);
		} else {
			return
			count($dbCmp['table_create']) + count($dbCmp['key_create']) + count($dbCmp['index_create']) +
			count($dbCmp['reference_create']) + count($dbCmp['field_create']) + count($dbCmp['field_update']) +
			count($dbCmp['field_update']) + count($dbCmp['index_update']) + count($dbCmp['reference_update']);
		}
	}
	
	public function getTables()
	{
		$res = array();
		foreach ($this->tables as $t => $v)
		{
			$res[$t] = $v;
		}
		
		return $res;
	}
	
	public function tableExists($name)
	{
		return isset($this->tables[$name]);
	}
	
	private function fieldsDiffer($db_field,$schema_field)
	{
		//* @todo - Known Issues : prendre en charge correctement les ', ", \
		$d_type = $db_field['type'];
		//*/
		$d_len = (integer) $db_field['len'];
		$d_attr = $db_field['attr'];
		$d_default = $db_field['default'];
		$d_null = $db_field['null'];
		$d_extra = $db_field['extra'];

		$s_type = $schema_field['type'];
		$s_len = (integer) $schema_field['len'];
		$s_attr = $schema_field['attr'];
		$s_default = $schema_field['default'];

		$s_null = $schema_field['null'];
		$s_extra = $schema_field['extra'];

		return $d_type != $s_type || $d_len != $s_len || $d_attr != $s_attr || $d_default != $s_default || $d_null != $s_null || $d_extra != $s_extra;
	}
	
	private function keysDiffer($d_name,$d_cols,$s_name,$s_cols)
	{
		return $d_name != $s_name || $d_cols != $s_cols;
	}
	
	private function indexesDiffer($d_name,$d_i,$s_name,$s_i)
	{
		return $d_name != $s_name || $d_i['cols'] != $s_i['cols'] || $d_i['type'] != $s_i['type']; 
	}
	
	private function referencesDiffer($d_name,$d_r,$s_name,$s_r)
	{
		return
		$d_name != $s_name || $d_r['c_cols'] != $s_r['c_cols']
		|| $d_r['p_table'] != $s_r['p_table'] || $d_r['p_cols'] != $s_r['p_cols']
		|| $d_r['update'] != $s_r['update'] || $d_r['delete'] != $s_r['delete'];
	}
	
	/**
	 * (Cette chaine correspond aux lignes PHP d'initialisation de l'objet dbStruc)
	 */
	public function __toString() {
		$tables = $this->getTables();
		$s = '';
		foreach($tables as $tname=>$t) {
			//$s .= '%1$s->'.$tname."\n";
			$s .= '$_s->'.$tname."\n";
			$fields = $t->getFields();
			$keys = $t->getKeys();
			$indexes = $t->getIndexes();
			$references = $t->getReferences();
			foreach($fields as $fname => $f) {
				$p = array();
				
				$p['type'] = "'".str_replace("'", "\'", $f['type'])."'";
				
				$p['len'] = is_numeric($f['len'])?$f['len']:"'".$f['len']."'";
				
				$p['attr'] = "'".$f['attr']."'";
				
				$p['null'] = $f['null'] ? 'true' : 'false';
				
				if ($f['default'] === null) {
					$p['default'] = "null";
				} elseif ($f['default'] !== false && trim($f['default']) !== '') {
					if (!is_numeric($f['default'])) {
						$p['default'] = "'".str_replace("'", "\'", $f['default'])."'";
					} else {
						$p['default'] = $f['default'];
					}
				} else {
					$p['default'] = "false";
				}
				
				if ($f['extra']) {
					$p['extra'] = "'".$f['extra']."'";	
				}
				// On retire les options avec les valeurs par défaut
				if (!$p['extra']) {
					if ($p['default'] == "false") {
						unset($p['default']);
						if ($p['null'] == 'true') {
							unset($p['null']);
							if ($p['attr'] == "''") {
								unset($p['attr']);
								if (empty($p['len'])) {
									unset($p['len']);	
								}
							}
						}
					}
				}
				$s .= "\t->".$fname.str_repeat(" ",abs(25-strlen($fname)))."(".implode(',',$p).")\n";
			}
			
			if (!empty($keys)) {
				$s .= "\n";
				foreach($keys as $kname => $k) {
					$s .= "\t->".$k['type']."('".$kname."','".implode("','",array_values($k['cols']))."')\n";
				}
			}
			
			if (!empty($indexes)) {
				$s .= "\n";
				foreach($indexes as $iname => $i) {
					$s .= "\t->index('".$iname."','".$i['type']."','".implode("','",array_values($i['cols']))."')\n";
				}
			}

			if (!empty($references)) {
				$s .= "\n";
				foreach($references as $rname => $r) {
					if (count($r['c_cols'])>1) {
						$c_cols = "array('".implode("','",$r['c_cols'])."')";
					} else {
						$c_cols = "'".$r['c_cols'][0]."'";
					}
					if (count($r['p_cols'])>1) {
						$p_cols = "array('".implode("','",$r['p_cols'])."')";
					} else {
						$p_cols = "'".$r['p_cols'][0]."'";
					}
					$updel = '';
					if ($r['update'] && !$r['delete']) {
						$updel = array("'".$r['update']."'");
					} elseif (!$r['update'] && $r['delete']) {
						$updel = array();
						$updel[] = "false";
						$updel[] = "'".$r['delete']."'";
					} elseif ($r['update'] && $r['delete']) {
						$updel = array();
						$updel[] = "'".$r['update']."'";
						$updel[] = "'".$r['delete']."'";
					}
					if (!empty($updel)) {
						$updel = ",".implode(",",$updel);
					}
					$s .= "\t->reference('".$rname."',".$c_cols.",'".$r['p_table']."',".$p_cols.$updel.")\n";
				}
			}
			$s .= "\t;\n\n";
		}
		return $s;
	}
	/**
	 * Calcul les différences entre la base de donées et la dbStruct passée en param (ce que dbStruct propose en plus, en mise à jour ou ne décrit pas par rapport à la structure de la BD).
	 * @param sbStruct $s
	 * @param boolean $withDeleteElements Est-il necessaire de calculer les éléments présents en base qui ne sont pas décrits dans $s
	 * @return array tableau multidimensionnel contenant l'ensemble des différences présent des $s :
	 * 					array('table_create','field_create','key_create','index_create','reference_create',
	 * 						'field_update','key_update','index_update','reference_update',
	 * 						'table_delete','field_delete','key_delete','index_delete','reference_delete')
	 */
	public function compare($s, $withDeleteElements = true) {
		$this->tables = array();
		$this->reverse();
		
		if (!($s instanceof self)) {
			throw new Exception('Invalid database schema');
		}
		
		$tables = $s->getTables();
		
        $dbCmp = array();
		$dbCmp['table_create'] = array();
		$dbCmp['key_create'] = array();
		$dbCmp['index_create'] = array();
		$dbCmp['reference_create'] = array();

		$dbCmp['field_create'] = array();
		$dbCmp['field_update'] = array();
		$dbCmp['key_update'] = array();
		$dbCmp['index_update'] = array();
		$dbCmp['reference_update'] = array();

		$dbCmp['table_delete'] = array();
		$dbCmp['field_delete'] = array();
		$dbCmp['key_delete'] = array();
		$dbCmp['index_delete'] = array();
		$dbCmp['reference_delete'] = array();

		
		$schema = dbSchema::init($this->con);
		
		foreach ($tables as $tname => $t)
		{
			if (!$this->tableExists($tname))
			{
				# Table does not exist, create table
				$dbCmp['table_create'][$tname] = $t->getFields();
				
				# Add keys, indexes and references
				$keys = $t->getKeys();
				$indexes = $t->getIndexes();
				$references = $t->getReferences();
				
				foreach ($keys as $k => $v) {
					$dbCmp['key_create'][$tname][$this->prefix.$k] = $v;
				}
				foreach ($indexes as $k => $v) {
					$dbCmp['index_create'][$tname][$this->prefix.$k] = $v;
				}
				foreach ($references as $k => $v) {
					$v['p_table'] = $this->prefix.$v['p_table'];
					$dbCmp['reference_create'][$tname][$this->prefix.$k] = $v;
				}
				
			}
			else # Table exists
			{
				# Check new fields to create
				$fields = $t->getFields();
				$db_fields = $this->tables[$tname]->getFields();
				foreach ($fields as $fname => $f)
				{
					if (!$this->tables[$tname]->fieldExists($fname))
					{
						# Field doest not exist, create it
						$dbCmp['field_create'][$tname][$fname] = $f;
					}
					elseif ($this->fieldsDiffer($db_fields[$fname],$f))
					{
						# Field exists and differs from db version
						$dbCmp['field_update'][$tname][$fname] = $f;
					}
				}
				
				# Check keys to add or upgrade
				$keys = $t->getKeys();
				$db_keys = $this->tables[$tname]->getKeys();
				
				foreach ($keys as $kname => $k)
				{
					if ($k['type'] == 'primary' && $this->con->driver() == 'mysql') {
						$kname = 'PRIMARY';
					} else {
						$kname = $this->prefix.$kname;
					}
					
					$db_kname = $this->tables[$tname]->keyExists($kname,$k['type'],$k['cols']);
					if (!$db_kname)
					{
						# Key does not exist, create it
						$dbCmp['key_create'][$tname][$kname] = $k;
					}
					elseif ($this->keysDiffer($db_kname,$db_keys[$db_kname]['cols'],$kname,$k['cols']))
					{
						# Key exists and differs from db version
						$dbCmp['key_update'][$tname][$db_kname] = array_merge(array('name'=>$kname),$k);
					}
				}
				
				# Check index to add or upgrade
				$idx = $t->getIndexes();
				$db_idx = $this->tables[$tname]->getIndexes();
				
				foreach ($idx as $iname => $i)
				{
					$iname = $this->prefix.$iname;
					$db_iname = $this->tables[$tname]->indexExists($iname,$i['type'],$i['cols']);
					
					if (!$db_iname)
					{
						# Index does not exist, create it
						$dbCmp['index_create'][$tname][$iname] = $i;
					}
					elseif ($this->indexesDiffer($db_iname,$db_idx[$db_iname],$iname,$i))
					{
						# Index exists and differs from db version
						$dbCmp['index_update'][$tname][$db_iname] = array_merge(array('name'=>$iname),$i);
					}
				}
				
				# Check references to add or upgrade
				$ref = $t->getReferences();
				$db_ref = $this->tables[$tname]->getReferences();
				
				foreach ($ref as $rname => $r)
				{
					$rname = $this->prefix.$rname;
					$r['p_table'] = $this->prefix.$r['p_table'];
					$db_rname = $this->tables[$tname]->referenceExists($rname,$r['c_cols'],$r['p_table'],$r['p_cols']);
					
					if (!$db_rname)
					{
						# Reference does not exist, create it
						$dbCmp['reference_create'][$tname][$rname] = $r;
					}
					elseif ($this->referencesDiffer($db_rname,$db_ref[$db_rname],$rname,$r))
					{
						$dbCmp['reference_update'][$tname][$db_rname] = array_merge(array('name'=>$rname),$r);
					}
				}
			}
		}
		
		if (!$withDeleteElements) {
			return $dbCmp;
		}
		
		// Tables, champs, index et autres en plus dans $this (absents de $s)
		$tables = $this->getTables();
		foreach ($tables as $tname => $t)
		{
			if (!$s->tableExists($tname))
			{
				# Table does not exist, deleted table
				$dbCmp['table_delete'][$tname] = $t->getFields();
				
				# Add keys, indexes and references
				$keys = $t->getKeys();
				$indexes = $t->getIndexes();
				$references = $t->getReferences();
				
				foreach ($keys as $k => $v) {
					$dbCmp['key_delete'][$tname][$k] = $v;
				}
				foreach ($indexes as $k => $v) {
					$dbCmp['index_delete'][$tname][$k] = $v;
				}
				foreach ($references as $k => $v) {
					$v['p_table'] = $v['p_table']; 
					$dbCmp['reference_delete'][$tname][$k] = $v;
				}
				
			}
			else # Table exists
			{
				# Check deleted fields
				$fields = $t->getFields();
				$s_fields = $s->tables[$tname]->getFields();
				foreach ($fields as $fname => $f)
				{
					if (!$s->tables[$tname]->fieldExists($fname))
					{
						# Field doest not exist, it's deleted
						$dbCmp['field_delete'][$tname][$fname] = $f;
					}
				}
				
				# Check deleted keys
				$keys = $t->getKeys();
				$s_keys = $s->tables[$tname]->getKeys();
				
				foreach ($keys as $kname => $k)
				{
					if ($k['type'] == 'primary' && $this->con->driver() == 'mysql') {
						$kname = 'PRIMARY';
					} else {
						$kname = $kname;
					}
					
					$s_kname = $s->tables[$tname]->keyExists($kname,$k['type'],$k['cols']);
					if (!$s_kname)
					{
						# Key does not exist, it's deleted
						$dbCmp['key_delete'][$tname][$kname] = $k;
					}
				}
				
				# Check deleted index
				$idx = $t->getIndexes();
				$s_idx = $s->tables[$tname]->getIndexes();
				
				foreach ($idx as $iname => $i)
				{
					$iname = $iname;
					$s_iname = $s->tables[$tname]->indexExists($iname,$i['type'],$i['cols']);
					
					if (!$s_iname)
					{
						# Index does not exist
						$dbCmp['index_delete'][$tname][$iname] = $i;
					}
				}
				
				# Check deleted references
				$ref = $t->getReferences();
				$s_ref = $s->tables[$tname]->getReferences();
				
				foreach ($ref as $rname => $r)
				{
					$rname = $rname;
					$r['p_table'] = $r['p_table'];
					$s_rname = $s->tables[$tname]->referenceExists($rname,$r['c_cols'],$r['p_table'],$r['p_cols']);
					
					if (!$s_rname)
					{
						# Reference does not exist,it's deleted
						$dbCmp['reference_delete'][$tname][$rname] = $r;
					}
				}
			}
		}

		return $dbCmp;
	}
	/**
	 * Return un objet dbstruct à partir d'un tableau
	 * @param array $a array('tables, fields, keys, indexes, references')
	 * @return 	 dbStructLight
	 */
	public static function fromArray($a = array()) {
		$s = new dbStructLight();
		$s->tables = array();

		if (!empty($a['tables'])) {
			foreach ($a['tables'] as $tname => $t) {
				if (!$s->tableExists($tname)) {
					$st = $s->table($tname);
				} else {
					$st = $s->tables[$tname];	
				}
				foreach($t as $fname => $f) {
					$args = $f;
					array_unshift($args, $fname);
					call_user_func_array(array($st, 'field'), $args);
				}
			}
		}

		foreach ($a['fields'] as $tname => $t) {
			if (!$s->tableExists($tname)) {
				$st = $s->table($tname);
			} else {
				$st = $s->tables[$tname];	
			}
			foreach($t as $fname => $f) {
				$st->field($fname,$f['type'],$f['len'],$f['attr'],$f['null'],$f['default'], $f['extra']);
			}
		}

		foreach ($a['keys'] as $tname => $t) {
			if (!$s->tableExists($tname)) {
				$st = $s->table($tname);
			} else {
				$st = $s->tables[$tname];	
			}
			foreach($t as $kname => $k) {
				$args = array();
				$args[] = $kname;
				foreach ($k['cols'] as $c) {
					$args[] = $c;
				}
				call_user_func_array(array($st, strtolower($k['type'])), $args);
			}
		}
		
		foreach ($a['indexes'] as $tname => $t) {
			if (!$s->tableExists($tname)) {
				$st = $s->table($tname);
			} else {
				$st = $s->tables[$tname];	
			}
			foreach($t as $iname => $idx) {
				$args = array();
				$args[] = $iname;
				$args[] = $idx['type'];
				foreach ($idx['cols'] as $c) {
					$args[] = $c;
				}
				call_user_func_array(array($st, 'index'), $args);
			}
		}
		
		foreach ($a['references'] as $tname => $t) {
			if (!$s->tableExists($tname)) {
				$st = $s->table($tname);
			} else {
				$st = $s->tables[$tname];	
			}
			foreach($t as $rname => $r) {
				$st->reference($rname, $r['c_cols'], $r['p_table'], $r['p_cols'], $r['update'], $r['delete']);
			}
		}
		
		return $s;
	}
	/**
	 * Génére le contenu du fichier db-schema.php pour la version courrante de l'application
	 * @param application $app instance de l'application (récupératon de la version)
	 * @return string chaine représentant le contenu complet du fichier
	 */
	public function getSchemaFileContent($app) {
		$this->reverse();
		
		$help = @file_get_contents(MODULES_DIR.'db-schema.txt');
		$app->registeredMdl->loadModules();
		$s = "<?php\n/**
 * 
 * Génération automatique du schéma de l'application
 * Date : ".strftime('%Y/%m/%d %H:%M:%S');
 
 		$s .= "\n *\n * Modules :";
		foreach ($app->registeredMdl->getModules() as $id => $m) {
			$s .= "\n * > ".$id. " (".$app->registeredMdl->moduleInfo($id, "version"). ")";
		}
 		$s .= "\n *
 * \n".
 ($help?$help:"")."
 */

if (!(\$_s instanceof dbStruct)) {
	throw new Exception('No valid schema object');
}

/* Tables
-------------------------------------------------------- */\n\n";

		/*$s .= sprintf($this,'$_s')."?>";*/
		$s .= $this."?>";
		
		return $s;
	}
}

class dbStructTable
{
	protected $name;
	protected $has_primary = false;
	
	protected $fields = array();
	protected $keys = array();
	protected $indexes = array();
	protected $references = array();
	
	/**
	Universal data types supported by dbSchema
	
	SMALLINT	: signed 2 bytes integer
	INTEGER	: signed 4 bytes integer
	BIGINT	: signed 8 bytes integer
	REAL		: signed 4 bytes floating point number
	FLOAT	: signed 8 bytes floating point number
	NUMERIC	: exact numeric type
	
	DATE		: Calendar date (day, month and year)
	TIME		: Time of day
	TIMESTAMP	: Date and time
	
	CHAR		: A fixed n-length character string
	VARCHAR	: A variable length character string
	TEXT		: A variable length of text
	*/
	protected $allowed_types = array(
		'smallint','integer','bigint','real','float','numeric',
		'date','time','timestamp',
		'char','varchar','text'
	);
	
	public function __construct($name)
	{
		$this->name = $name;
		return $this;
	}
	
	public function getFields()
	{
		return $this->fields;
	}
	
	public function getKeys($primary=null)
	{
		return $this->keys;
	}
	
	public function getIndexes()
	{
		return $this->indexes;
	}
	
	public function getReferences()
	{
		return $this->references;
	}
	
	public function fieldExists($name)
	{
		return isset($this->fields[$name]);
	}
	
	public function keyExists($name,$type,$cols)
	{
		# Look for key with the same name
		if (isset($this->keys[$name])) {
			return $name;
		}
		
		# Look for key with the same columns list and type
		foreach ($this->keys as $n => $k)
		{
			if ($k['cols'] == $cols && $k['type'] == $type) {
				# Same columns and type, return new name
				return $n;
			}
		}
		
		return false;
	}
	
	public function indexExists($name,$type,$cols)
	{
		# Look for key with the same name
		if (isset($this->indexes[$name])) {
			return $name;
		}
		
		# Look for index with the same columns list and type
		foreach ($this->indexes as $n => $i)
		{
			if ($i['cols'] == $cols && $i['type'] == $type) {
				# Same columns and type, return new name
				return $n;
			}
		}
		
		return false;
	}
	
	public function referenceExists($name,$c_cols,$p_table,$p_cols)
	{
		if (isset($this->references[$name])) {
			return $name;
		}
		
		# Look for reference with same chil columns, parent table and columns
		foreach ($this->references as $n => $r)
		{
			if ($c_cols == $r['c_cols'] && $p_table == $r['p_table'] && $p_cols == $r['p_cols']) {
				# Only name differs, return new name
				return $n;
			}
		}
		
		return false;
	}
	
	public function field($name,$type,$len=0,$attr=null,$null=true,$default=false, $extra=null,$to_null=false)
	{
		//$type = strtolower($type);
		/*
		if (!in_array($type,$this->allowed_types))
		{
			if ($to_null) {
				$type = null;
			} else {
				throw new Exception('Invalid data type '.$type.' in schema');
			}
		}
		//*/
		
		$this->fields[$name] = array(
			'type' => $type,
			'len' => $len,
			'attr' => $attr,
			'null' => (boolean) $null,
			'default' => $default,
			'extra' => $extra
		);

		return $this;
	}
	
	public function __call($name,$args)
	{
		array_unshift($args,$name);
		return call_user_func_array(array($this,'field'),$args);
	}
	
	public function primary($name,$col)
	{
		if ($this->has_primary) {
			throw new Exception(sprintf('Table %s already has a primary key',$this->name));
		}
		
		$cols = func_get_args();
		array_shift($cols);
		
		return $this->newKey('primary',$name,$cols);
	}
	
	public function unique($name,$col)
	{
		$cols = func_get_args();
		array_shift($cols);
		
		return $this->newKey('unique',$name,$cols);
	}
	
	public function index($name,$type,$col)
	{
		$cols = func_get_args();
		array_shift($cols);
		array_shift($cols);
		
		$this->checkCols($cols);
		
		$this->indexes[$name] = array(
			'type' => strtolower($type),
			'cols' => $cols
		);
		
		return $this;
	}
	
	public function reference($name,$c_cols,$p_table,$p_cols,$update=false,$delete=false)
	{
		if (!is_array($p_cols)) {
			$p_cols = array($p_cols);
		}
		if (!is_array($c_cols)) {
			$c_cols = array($c_cols);
		}
		
		$this->checkCols($c_cols);
		
		$this->references[$name] = array(
			'c_cols' => $c_cols,
			'p_table' => $p_table,
			'p_cols' => $p_cols,
			'update' => $update,
			'delete' => $delete
		);
		
		return $this;
	}
	
	protected function newKey($type,$name,$cols)
	{
		$this->checkCols($cols);
		
		$this->keys[$name] = array(
			'type' => $type,
			'cols' => $cols
		);
		
		if ($type == 'primary') {
			$this->has_primary = true;
		}
		
		return $this;
	}
	
	protected function checkCols($cols)
	{
		foreach ($cols as $v) {
			if (!preg_match('/^\(.*?\)$/',$v) && !isset($this->fields[$v])) {
				throw new Exception(sprintf('Field %s does not exist in table %s',$v,$this->name));
			}
		}
	}
}
/**
 * Identique à son parent mais permet de passer outre les controles sur l'existance des colonnes lors de la création des clés, indexes, références, etc. (checkCols)
 */
class dbStructLight extends dbStruct {
	public function __construct() {}
	public function table($name)
	{
		$this->tables[$name] = new dbStructTableLight($name);
		return $this->tables[$name];
	}
}
class dbStructTableLight extends dbStructTable {
	public function __construct($name)
	{
		$this->name = $name;
		return $this;
	}
	protected function checkCols($cols){}
}
?>
