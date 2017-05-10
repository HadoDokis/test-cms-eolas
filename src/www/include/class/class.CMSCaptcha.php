<?php

abstract class CMSCaptcha
{
    // identifiant unique
    protected $id;
    //
    protected $reponse;

    public function __construct()
    {
        $this->id = sha1(uniqid(rand(0, time()) . '@' . $_SERVER['REMOTE_ADDR'], true));
    }

    public function getID()
    {
        return $this->id;
    }

    public static function check($id, $value)
    {
        $dbh = DB::getInstance();

        // Purge de la table CAPTCHA
        $delta = time() - 60 * 10; // 10 minutes
        $sql = "delete from CAPTCHA where CAP_DATE < " . $delta;
        $dbh->exec($sql);
        foreach (new DirectoryIterator(UPLOAD_CAPTCHA_PHYSIQUE) as $file) {
            if ($file->isFile() && $file->getMTime() < $delta) {
                unlink($file->getPathname());
            }
        }

        $sql = "select CAP_REPONSE from CAPTCHA where ID_CAPTCHA=" . $dbh->quote($id);
        $CAP_REPONSE = $dbh->query($sql)->fetchColumn();
        $sql = "delete from CAPTCHA where ID_CAPTCHA=" . $dbh->quote($id);
        $dbh->exec($sql);
        return ($CAP_REPONSE != '') && ($CAP_REPONSE == $value);
    }

    protected function save()
    {
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("insert into CAPTCHA values (:ID_CAPTCHA, :CAP_DATE, :CAP_REPONSE)");
        $stmt->bindValue(':ID_CAPTCHA', $this->getID(), PDO::PARAM_STR);
        $stmt->bindValue(':CAP_DATE', time(), PDO::PARAM_INT);
        $stmt->bindValue(':CAP_REPONSE', $this->reponse, PDO::PARAM_STR);
        $stmt->execute();
    }
}

class Numerical_CAPTCHA extends CMSCaptcha
{
    // valeur min et max
    private $min = 1;

    private $max = 20;

    private $question;

    public function __construct($minus = false)
    {
        parent::__construct();
        $this->operator = $minus ? '-' : '+';

        $n1 = $n2 = mt_rand($this->min, $this->max);
        while ($n1 == $n2) {
            $n2 = mt_rand($this->min, $this->max);
        }
        $question = $reponse = '';
        if ($minus) {
            if ($n1 < $n2) {
                $_n = $n2;
                $n2 = $n1;
                $n1 = $_n;
            }
            $this->question = $n1 . " moins " . $n2 . " égale ";
            $this->reponse = $n1 - $n2;
        } else {
            $this->question = $n1 . " plus " . $n2 . " égale ";
            $this->reponse = $n1 + $n2;
        }

        $this->save();
    }

    public function render($inputHidden, $inputName, $onlyInner = false)
    {
        $inner = '
            <span class="captchaOperation">' . $this->question . '</span>
            <input type="text" name="' . $inputName . '" id="' . $inputName . '" size="8" autocomplete="false" class="captchaInput" data-type="integer" required>
            <input type="hidden" name="' . $inputHidden . '" value="' . $this->getID() . '">
            ';
        if ($onlyInner) {
            return $inner;
        }
        $html = '
            <p>
                <label for="' . $inputName . '">Captcha</label> ' . $inner . '
            </p>';
        return $html;
    }
}

class Graphical_CAPTCHA extends CMSCaptcha
{
    // nom du fichier
    private $name;
    // nb de caractère pour le catpcha
    private $length;
    // largeur de l'image catpcha
    private $width;
    // hauteur de l'image catpcha
    private $height;
    // largeur des marge horizontale/verticale de l'image catpcha
    private $marginW = 10;

    private $marginH = 5;
    // image
    private $image;
    // couleur 1 pour le dégradé
    private $color1 = array(
        255,
        255,
        255
    );
    // couleur 2 pour le dégradé
    private $color2 = array(
        200,
        200,
        200
    );
    // tableau de couleurs pour les lignes
    private $aColorLine = array();
    // modele de chaine
    private $modele = 'abdeghjkmnpqrtuABCDEFGHJKLMNPQRSTUVWXYZ123456789';

    public function __construct($length = 6, $width = 150, $height = 30)
    {
        parent::__construct();
        $this->name = "captcha_" . session_id() . rand(0, time());
        $this->length = intval($length);
        $this->width = intval($width);
        $this->height = intval($height);

        // couleurs aléatoires pour dégradé et trait
        $a = mt_rand(100, 255);
        $b = mt_rand(100, 255);
        $c = mt_rand(100, 255);
        $this->color2 = array(
            mt_rand(150, 200),
            mt_rand(150, 200),
            mt_rand(150, 200)
        );
        $this->aColorLine = array(
            array(
                $a,
                $b,
                $c
            ),
            array(
                $a,
                $c,
                $b
            ),
            array(
                $b,
                $c,
                $a
            ),
            array(
                $b,
                $a,
                $c
            ),
            array(
                $c,
                $a,
                $b
            ),
            array(
                $c,
                $b,
                $a
            )
        );

        // calcule de la chaine de texte
        $modeleLength = mb_strlen($this->modele) - 1;
        $this->reponse = '';
        for ($i = 0; $i < $this->length; $i ++) {
            $this->reponse .= $this->modele[mt_rand(0, $modeleLength)];
        }

        // creation de l'image (vide)
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // ajout du fond
        for ($i = 0; $i < $this->width; $i ++) {
            $r = $this->color1[0] + $i * ($this->color2[0] - $this->color1[0]) / $this->width;
            $v = $this->color1[1] + $i * ($this->color2[1] - $this->color1[1]) / $this->width;
            $b = $this->color1[2] + $i * ($this->color2[2] - $this->color1[2]) / $this->width;
            $color = imagecolorallocate($this->image, $r, $v, $b);
            imageline($this->image, $i, 0, $i, $this->height, $color);
        }

        // ajout du texte
        $color = imagecolorallocate($this->image, 0, 0, 0);
        // calcule de la largeur des caractères en fonction du nb de caractère et de la longueur de l'image
        $charWidth = floor(($this->width - 2 * $this->marginW) / $this->length);
        for ($i = 0; $i < $this->length; $i ++) {
            $x = ($i * $charWidth) + $this->marginW;
            imagettftext($this->image, $this->height - 2 * $this->marginH, mt_rand(- 10, 10), $x, $this->height - $this->marginH, $color, PHYSICAL_PATH . 'include/police/arial.ttf', $this->reponse[$i]);
        }

        // quelques lignes qui embètent
        for ($i = 0; $i < count($this->aColorLine); $i ++) {
            $color = imagecolorallocate($this->image, $this->aColorLine[$i][0], $this->aColorLine[$i][1], $this->aColorLine[$i][2]);
            imageline($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }

        $this->save();

        imagepng($this->image, UPLOAD_CAPTCHA_PHYSIQUE . $this->name . ".png");
    }

    public function getImageSRC()
    {
        return UPLOAD_CAPTCHA . $this->name . ".png";
    }

    public function render($inputHidden, $inputName, $onlyInner = false)
    {
        require_once CLASS_DIR . 'class.db_page.php';
        Page::setNoCache();
        $inner = '
                <span class="cases">
                    <span id="SPAN_' . $inputHidden . '">
                        <img src="' . $this->getImageSRC() . '" alt="Captcha anti-spam" class="captchaImg">
                        <input type="hidden" name="' . $inputHidden . '" value="' . $this->getID() . '">
                    </span>
                    <input type="text" name="' . $inputName . '" id="' . $inputName . '" size="8" autocomplete="false" class="captchaInput" required>
                    <br><small><a href="#" onclick="$(\'#SPAN_' . $inputHidden . '\').load(\'/include/ajax/ajax.captcha.php\', {\'name\':\'' . $inputHidden . '\'}); return false; ">(Regénérer)</a></small>
                </span>
            ';
        if ($onlyInner) {
            return $inner;
        }
        $html = '
            <p>
                <label for="' . $inputName . '">Captcha <span class="helper">Merci de respecter la casse</span></label> ' . $inner . '
            </p>';
        return $html;
    }
}
