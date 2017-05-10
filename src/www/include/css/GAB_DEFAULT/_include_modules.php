<?php
header('Content-type: text/css');
foreach (glob("_default_*") as $cssInclude) {
    include($cssInclude);
}
