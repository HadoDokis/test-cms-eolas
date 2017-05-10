= Known Issues =

dbStruct :
 * Les caractères ', ", \ ne sont pas pris de manière totalement satisfaisante au sein des valeurs $type pour les champs
 * Sous mysql l'attribut "on update CURRENT_TIMESTAMP" n'est pas pris en charge (+ limitations similaires sur les manipulations des dates par défaut)
 * #3290 - Comparaison entre fichier db-struct et DB fausse sur la valeur par défaut

= TODO =

* La correction du #3949 mérite d'être optimisée afin de réduire le nombre de requête

dbStruct :
* Finir d'implémenter l'option "after" sur la création / modification des champs (et sur les "synchronize", "compare")

Application :
* Implémenter le système des controles de configuration
