<?php
interface i_commentaire
{
    /**
     *
     * renvoi le libéllé afficher en BO pour définir le type de cible d'un commentaire
     * @return string [ + mesage si cible a été supprimé]
     */
    public function getLibelleTypeCommentaire();

    /**
     *
     * Renvoi le libellé de la cible
     * @return string ou false
     */
    public function getLibelleCommentaire();

    /**
     *
     * renvoi un lien pour afficher le module/template en FO
     * @return string [url] ou false
     */
    public function getURLCommentaire();

    /**
     *
     * renvoi un lien pour afficher le module/template en Pseudo FO
     * @return string [url] ou false
     */
    public function getURLBO();

    /**
     *
     * renvoi 'true' si les commentaires pour cet instance du module doit être affiché, sinon 'false'
     */
    public function showCommentaire();
}
