<?php
/*
 * Classe permetant de manipuler le schéma particulier d'une base MySQL
 * @contributor harmen CHRISTOPHE <harmen@eolas.fr>
 * Librement adapté et inspiré de Clearbricks <http://clearbricks.org/>
 * (Copyright (c) 2006 Olivier Meunier and contributors - GNU General Public License)
 * => Ajout des spécificités sur les attributs de champs (unsigned, etc.)
 */
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2007 Olivier Meunier and contributors. All rights
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

class mysqlSchema extends dbSchema implements i_dbSchema
{
	public function dbt2udt($type,&$len,&$default)
	{
		$type = parent::dbt2udt($type,$len,$default);
		
		switch ($type)
		{
			case 'float':
				return 'real';
			case 'double':
				return 'float';
			case 'datetime':
				# DATETIME real type is TIMESTAMP
				if ($default == "'1970-01-01 00:00:00'") {
					# Bad hack
					$default = 'now()';
				}
				return 'timestamp';
			case 'integer':
			case 'mediumint':
				if ($len == 11) { $len = 0; }
				return 'integer';
			case 'bigint':
				if ($len == 20) { $len = 0; }
				break;
			case 'tinyint':
			case 'smallint':
				if ($len == 6) { $len = 0; }
				return 'smallint';
			case 'numeric':
				$len = 0;
				break;
			case 'tinytext':
			case 'longtext':
				return 'text';
		}
		
		return $type;
	}
	
	public function udt2dbt($type,&$len,&$default)
	{
		$type = parent::udt2dbt($type,$len,$default);
		
		switch ($type)
		{
			case 'real':
				return 'float';
			case 'float':
				return 'double';
			case 'timestamp':
				if ($default == 'now()') {
					# MySQL does not support now() default value...
					$default = "'1970-01-01 00:00:00'";
				}
				return 'datetime';
			case 'text':
				$len = 0;
				return 'longtext';
		}
		
		return $type;
	}
	
	public function db_get_tables()
	{
		$sql = 'SHOW TABLES';
		$rowListe = $this->con->query($sql)->fetchAll(PDO :: FETCH_NUM);
		
		$res = array();
		foreach($rowListe as $row) {
			$res[] = $row[0];
		}
		return $res;
	}
	
	public function db_get_columns($table)
	{
		$sql = 'SHOW COLUMNS FROM '.$this->con->escapeSystem($table);
		$rowListe = $this->con->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
		
		$res = array();
		foreach($rowListe as $row)
		{
			$field = trim($row['FIELD']);
			$type = trim($row['TYPE']);
			$null = strtolower($row['NULL']) == 'yes';
			$default = $row['DEFAULT'];
			$extra = $row['EXTRA'];
			$len = null;
			$attr = '';
			if (preg_match('/^(.+?)\(([\d,]+)\)(.*)$/si',$type,$m)) {
				$type = $m[1];
				$len = $m[2];
				$attr = trim($m[3]);
			}
			
			$res[$field] = array(
				'type' => $type,
				'len' => $len,
				'attr' => $attr,
				'null' => $null,
				'default' => $default,
				'extra' => $extra
			);
		}
		return $res;
	}
	
	public function db_get_keys($table)
	{
		$sql = 'SHOW INDEX FROM '.$this->con->escapeSystem($table);
		$rowListe = $this->con->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
		
		$t = array();
		$res = array();
		foreach($rowListe as $row)
		{
			$key_name = $row['KEY_NAME'];
			$unique = $row['NON_UNIQUE'] == 0;
			$seq = $row['SEQ_IN_INDEX'];
			$col_name = $row['COLUMN_NAME'];
			
			if ($key_name == 'PRIMARY' || $unique) {
				$t[$key_name]['cols'][$seq] = $col_name;
				$t[$key_name]['unique'] = $unique;
			}
		}
		
		foreach ($t as $name => $idx)
		{
			ksort($idx['cols']);
			
			$res[] = array(
				'name' => $name,
				'primary' => $name == 'PRIMARY',
				'unique' => $idx['unique'],
				'cols' => array_values($idx['cols'])
			);
		}
		
		return $res;
	}
	
	public function db_get_indexes($table)
	{
		$sql = 'SHOW INDEX FROM '.$this->con->escapeSystem($table);
		$rowListe = $this->con->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
		
		$t = array();
		$res = array();
		foreach($rowListe as $row)
		{
			$key_name = $row['KEY_NAME'];
			$unique = $row['NON_UNIQUE'] == 0;
			$seq = $row['SEQ_IN_INDEX'];
			$col_name = $row['COLUMN_NAME'];
			$type = $row['INDEX_TYPE'];
			
			if ($key_name != 'PRIMARY' && !$unique) {
				$t[$key_name]['cols'][$seq] = $col_name;
				$t[$key_name]['type'] = $type;
			}
		}
		
		foreach ($t as $name => $idx)
		{
			ksort($idx['cols']);
			
			$res[] = array(
				'name' => $name,
				'type' => $idx['type'],
				'cols' => $idx['cols']
			);
		}
		
		return $res;
	}
	
	public function db_get_references($table)
	{
		$sql = 'SHOW CREATE TABLE '.$this->con->escapeSystem($table);
		$this->con->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$s = $this->con->query($sql)->fetch(PDO :: FETCH_ASSOC);
		$this->con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$s = $s['CREATE TABLE'];
		$res = array();
		
		$n = preg_match_all('/^\s*CONSTRAINT\s+`(.+?)`\s+FOREIGN\s+KEY\s+\((.+?)\)\s+REFERENCES\s+`(.+?)`\s+\((.+?)\)(.*?)$/msi',$s,$match);
		if ($n > 0)
		{
			foreach ($match[1] as $i => $name)
			{
				# Columns transformation
				$t_cols = str_replace('`','',$match[2][$i]);
				$t_cols = explode(',',$t_cols);
				$r_cols = str_replace('`','',$match[4][$i]);
				$r_cols = explode(',',$r_cols);
				
				# ON UPDATE|DELETE
				$on = trim($match[5][$i],', ');
				$on_delete = null;
				$on_update = null;
				if ($on != '') {
					if (preg_match('/ON DELETE (.+?)(?:\s+ON|$)/msi',$on,$m)) {
						$on_delete = strtolower(trim($m[1]));
					}
					if (preg_match('/ON UPDATE (.+?)(?:\s+ON|$)/msi',$on,$m)) {
						$on_update = strtolower(trim($m[1]));
					}
				}
				
				$res[] = array (
					'name' => $name,
					'c_cols' => $t_cols,
					'p_table' => $match[3][$i],
					'p_cols' => $r_cols,
					'update' => $on_update,
					'delete' => $on_delete
				);
			}
		}
		return $res;
	}
	
	public function db_create_table($name,$fields)
	{
		$a = array();
		
		foreach ($fields as $n => $f)
		{
			//$type = $this->con->quote($f['type']);
			//* @todo - Known Issues : prendre en charge correctement les ', ", \
			//$type = preg_replace("/^'(.+)'$/",'\\1',$this->con->quote($f['type']));
			//$type = str_replace("\\'", "'", $type);
			//*/
			$type = $f['type'];
		    $len = $f['len'];
			$attr = $f['attr']?$f['attr']:'';
			$default = $f['default'];
			$null = $f['null'];
			//* Les "auto_increment" ne peuvent être posés que sur des champ indéxés
			if (strtolower($f['extra']) != 'auto_increment') {
				$extra = !empty($f['extra'])?$f['extra']:'';
			}
			//*/
//			$type = $this->udt2dbt($type,$len,$default);
			$len = (integer) $len > 0 ? '('.$len.')' : '';
			$null = $null ? 'NULL' : 'NOT NULL';
			
			if ($default === null) {
				$default = 'DEFAULT NULL';
			} elseif ($default !== false) {
				if (!is_numeric($default)) {
					$default = 'DEFAULT '.$this->con->quote($default);	
				} else {
					$default = 'DEFAULT '.$default;
				}
			} else {
				$default = '';
			}
			
			$a[] =
			$this->con->escapeSystem($n).' '.
			$type.$len.' '.$attr.' '.$null.' '.$default.' '.$extra;
		}
		
		$sql =
		'CREATE TABLE '.$this->con->escapeSystem($name)." (\n".
			implode(",\n",$a).
		"\n) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		
		$this->con->exec($sql);
	}
	
	public function db_create_field($table,$name,$type,$len,$attr,$null,$default,$extra,$after)
	{
//		$type = $this->udt2dbt($type,$len,$default);
		//$type = $this->con->quote($type);
		//* @todo - Known Issues : prendre en charge correctement les ', ", \
		//$type = preg_replace("/^'(.+)'$/",'\\1',$this->con->quote($type));
		//$type = str_replace("\\'", "'", $type);
		//*/
		$len = (integer) $len > 0 ? '('.$len.')' : '';
		$attr = $attr?$attr:'';
		$null = $null ? 'NULL' : 'NOT NULL';
		//* Les "auto_increment" ne peuvent être posés que sur des champ indéxés
		if (strtolower($extra) == 'auto_increment') {$extra = '';}
		//*/
		if ($default === null) {
			$default = 'DEFAULT NULL';
		} elseif ($default !== false) {
			if (!is_numeric($default)) {
				$default = 'DEFAULT '.$this->con->quote($default);	
			} else {
				$default = 'DEFAULT '.$default;
			}
		} else {
			$default = '';
		}
		
		$after = !empty($after)?'AFTER '.$this->con->escapeSystem($after):'';
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'ADD COLUMN '.$this->con->escapeSystem($name).' '.
		$type.$len.' '.$attr.' '.$null.' '.$default.' '.$extra.$after;
		
		$this->con->exec($sql);
	}
	
	public function db_create_primary($table,$name,$cols)
	{
		$c = array();
		$cols = is_array($cols)?$cols:array($cols);
		foreach ($cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'ADD CONSTRAINT PRIMARY KEY ('.implode(',',$c).') ';
		
		$this->con->exec($sql);
	}
	
	public function db_create_unique($table,$name,$cols)
	{
		$c = array();
		$cols = is_array($cols)?$cols:array($cols);
		foreach ($cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'ADD CONSTRAINT UNIQUE KEY '.$this->con->escapeSystem($name).' '.
		'('.implode(',',$c).') ';
		
		$this->con->exec($sql);
	}
	
	public function db_create_index($table,$name,$type,$cols)
	{
		$c = array();
		$cols = is_array($cols)?$cols:array($cols);
		foreach ($cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'ADD INDEX '.$this->con->escapeSystem($name).' USING '.$type.' '.
		'('.implode(',',$c).') ';
		
		$this->con->exec($sql);
	}
	
	public function db_create_reference($name,$c_table,$c_cols,$p_table,$p_cols,$update,$delete)
	{
		$c = array();
		$p = array();
		$c_cols = is_array($c_cols)?$c_cols:array($c_cols);
		foreach ($c_cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		$p_cols = is_array($p_cols)?$p_cols:array($p_cols);
		foreach ($p_cols as $v) {
			$p[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($c_table).' '.
		'ADD CONSTRAINT '.$name.' FOREIGN KEY '.
		'('.implode(',',$c).') '.
		'REFERENCES '.$this->con->escapeSystem($p_table).' '.
		'('.implode(',',$p).') ';
		
		if ($update) {
			$sql .= 'ON UPDATE '.$update.' ';
		}
		if ($delete) {
			$sql .= 'ON DELETE '.$delete.' ';
		}
		
		$this->con->exec($sql);
	}
	
	public function db_alter_field($table,$name,$type,$len,$attr,$null,$default,$extra,$after)
	{
//		$type = $this->udt2dbt($type,$len,$default);
		//$type = $this->con->quote($type);
		//* @todo - Known Issues : prendre en charge correctement les ', ", \ 
		//$type = preg_replace("/^'(.+)'$/",'\\1',$this->con->quote($type));
		//$type = str_replace("\\'", "'", $type);
		//*/
		$len = (integer) $len > 0 ? '('.$len.')' : '';
		$attr = $attr?$attr:'';
		$null = $null ? 'NULL' : 'NOT NULL';
		$extra = $extra?$extra:'';
		
		if ($default === null) {
			$default = 'DEFAULT NULL';
		} elseif ($default !== false) {
			if (!is_numeric($default)) {
				$default = 'DEFAULT '.$this->con->quote($default);	
			} else {
				$default = 'DEFAULT '.$default;
			}
		} else {
			$default = '';
		}

		$after = !empty($after)?'AFTER '.$this->con->escapeSystem($after):'';
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'CHANGE COLUMN '.$this->con->escapeSystem($name).' '.$this->con->escapeSystem($name).' '.
		$type.$len.' '.$attr.' '.$null.' '.$default.' '.$extra.' '.$after;
		
		$this->con->exec($sql);
	}
	
	public function db_alter_primary($table,$name,$newname,$cols)
	{
		$c = array();
		$cols = is_array($cols)?$cols:array($cols);
		foreach ($cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP PRIMARY KEY, ADD PRIMARY KEY '.
		'('.implode(',',$c).') ';
		
		$this->con->exec($sql);
	}
	
	public function db_alter_unique($table,$name,$newname,$cols)
	{
		$c = array();
		$cols = is_array($cols)?$cols:array($cols);
		foreach ($cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP INDEX '.$this->con->escapeSystem($name).', '.
		'ADD UNIQUE '.$this->con->escapeSystem($newname).' '.
		'('.implode(',',$c).') ';
		
		$this->con->exec($sql);
	}
	
	public function db_alter_index($table,$name,$newname,$type,$cols)
	{
		$c = array();
		$cols = is_array($cols)?$cols:array($cols);
		foreach ($cols as $v) {
			$c[] = $this->con->escapeSystem($v);
		}
		
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP INDEX '.$this->con->escapeSystem($name).', '.
		'ADD INDEX '.$this->con->escapeSystem($newname).' '.
		'USING '.$type.' '.
		'('.implode(',',$c).') ';
		
		$this->con->exec($sql);
	}

	public function db_alter_reference($name,$newname,$c_table,$c_cols,$p_table,$p_cols,$update,$delete)
	{
		$this->dropReference($c_table,$name);
		$this->createReference($newname,$c_table,$c_cols,$p_table,$p_cols,$update,$delete);
	}

	public function db_drop_table($table)
	{
		$sql =
		'DROP TABLE '.$this->con->escapeSystem($table);

		$this->con->exec($sql);
	}
	
	public function db_drop_field($table, $name)
	{
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP '.$this->con->escapeSystem($name);

		$this->con->exec($sql);
	}

	public function db_drop_reference($table, $name)
	{
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP FOREIGN KEY '.$this->con->escapeSystem($name);

		$this->con->exec($sql);
	}

	
	public function db_drop_index($table,$name)
	{
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP INDEX '.$this->con->escapeSystem($name);

		$this->con->exec($sql);
	}
	
	public function db_drop_primary($table,$name)
    {
        $sql = 'ALTER TABLE '.$this->con->escapeSystem($table).' DROP PRIMARY KEY';
        $this->con->exec($sql);
    }
	
	public function db_drop_unique($table,$name)
	{
		$sql =
		'ALTER TABLE '.$this->con->escapeSystem($table).' '.
		'DROP INDEX '.$this->con->escapeSystem($name);
		$this->con->exec($sql);
	}
}
?>
