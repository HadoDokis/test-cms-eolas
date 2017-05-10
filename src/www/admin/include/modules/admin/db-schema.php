<?php
/**
 *
 * Génération automatique du schéma de l'application
 * Date : 2010/04/19 09:52:46
 *
 * Modules :
 *
 *
 * Known Issues :
 * - Les caractères ', ", \ ne sont pas pris de manière totalement satisfaisante au sein des valeurs $type pour les champs
 * - Sous mysql l'attribut "on update CURRENT_TIMESTAMP" n'est pas pris en charge (+ limitations similaires sur les manipulations des dates par défaut)
 *
 *
 * *****************************************************************************
 * Design des champs:
 * {dbStructTable}->$name (
 * 		$type,         // Type de champ
 * 		$len=0,        // Longueur du champ (0 = longueur par défaut du type)
 * 		$attr=null,    // Attribut suplémentaire ['unsigned, etc.']
 * 		$null=true,    // null or not null ?
 * 		$default=false,// valeur par défaut (null => Default null, false => aucune)
 * 		$extra=''      // extra de type autoincrement, etc, etc
 * );
 * > EX :
 * > $s->DE_EX->->ID_EXEMPLE           ('int',11,'unsigned',false,false,'auto_increment')
 *
 * La clé primaire:
 * {dbStructTable}->primary(
 * 		$name, 	// Nom de la clé primaire
 * 		$col 	// Nom de la ou des colonnes utilisées pour former la clé primaire
 * );
 * > EX :
 * > $s->DE_EX->primary('PRIMARY', 'ID_TEST', 'ID_WEBOTHEQUE');
 *
 * Les clés uniques:
 * {dbStructTable}->unique(
 * 		$name, 	// Nom de la clé primaire
 * 		$col 	// Nom de la ou des colonnes utilisées pour former la clé unique
 * );
 * > EX :
 * > $s->DE_EX->unique('DE_EX_uk_ID_WEBOTHEQUE_EX_NUM', 'ID_WEBOTHEQUE', 'EX_NUM');
 *
 * Les index:
 * {dbStructTable}->index(
 * 		$name, 	// Nom de l'index
 * 		$type, 	// Type d'index (btree pour InnoDB)
 * 		$col 	// une ou plusieurs colonnes cible de l'index (un nom de colonne par parametre de la fonction)
 * );
 * > EX :
 * > $s->DE_EX->index('DE_EX_idx_EX_DATES','btree','EX_DATE_DEBUT', 'EX_DATE_FIN');
 *
 * Les clés étrangères:
 * {dbStructTable}->reference(
 * 		$name, 			// Nom de la clé étrangère
 * 		$c_cols, 		// Nom de la colonne cible
 * 		$p_table, 		// Nom de la table principale
 * 		$p_cols, 		// Nom de la colonne principale
 * 		$update=false, 	// Comportement sur l'update ?
 * 		$delete=false 	// Comportement sur le delete ?
 * );
 * > EX :
 * > $s->DE_EX->reference('DE_EX_fk_DD_SITE','SIT_CODE','DD_SITE','SIT_CODE', 'cascade', 'delete');
 * *****************************************************************************

 */

if (!($_s instanceof dbStruct)) {
    throw new Exception('No valid schema object');
}

/* Tables
-------------------------------------------------------- */

$_s->DD_MODULES
    ->MOD_CODE                 ('varchar',64,'',false)
    ->MOD_VERSION              ('varchar',32,'',false)
    ->MOD_SIGNATURE            ('text',0,'',false)
    ->MOD_DATETIME             ('datetime',0,'',false)

    ->primary('PRIMARY','MOD_CODE')
    ;
