<table class="liste">
    <thead>
        <tr>
            <th><?php echo gettext('indicateurs') ?></th>
            <?php foreach ($aMedium as $libelle) { ?>
                <th><?php echo gettext($libelle);
                /**
                  *  Clés de traduction possibles pour les mediums
                  *  Mises ici pour être parsées par xgettext
                  */
                    gettext('acces_direct');
                    gettext('moteur_recherche');
                    gettext('sites_affluents');
                    gettext('autres');
                    gettext('emailing');
                    gettext('lien_sponsorises');
                ?> </th>
            <?php } ?>
            <th><?php echo gettext('total') ?></th>
        </tr>
        <tr>
            <td class="aligncenter">Visites</td>
            <?php foreach ($aMedium as $gam_code => $libelle) {
                $totalVisite += $aGAD_VISITS[$gam_code]; ?>
                <td class="aligncenter"><?php echo $aGAD_VISITS[$gam_code]; ?></td>
            <?php } ?>
            <td class="aligncenter">
                <?php echo formatNum($totalVisite, $totalVisite) ?>
            </td>
        </tr>
        <tr>
            <td class="aligncenter">Pages par visite</td>
            <?php foreach ($aMedium as $gam_code => $libelle) {
                $value = $aGAD_PAGEVIEWS[$gam_code]/$aGAD_VISITS[$gam_code];
                $totalPageVues += $aGAD_PAGEVIEWS[$gam_code]; ?>
                <td class="aligncenter"><?php echo formatNum($value, $value); ?></td>
            <?php } ?>
            <td class="aligncenter">
                <?php echo formatNum($totalVisite, $totalPageVues/$totalVisite) ?>
            </td>
        </tr>
        <tr>
            <td class="aligncenter">Temps moyen</td>
            <?php foreach ($aMedium as $gam_code => $libelle) {
                $value = $aGAD_TIMEONSITE[$gam_code]/$aGAD_VISITS[$gam_code];
                $totalTime += $aGAD_TIMEONSITE[$gam_code]; ?>
                <td class="aligncenter"><?php echo formatTime($value); ?></td>
            <?php } ?>
            <td class="aligncenter">
                <?php echo formatTime($totalTime/$totalVisite) ?>
            </td>
        </tr>
        <tr>
            <td class="aligncenter">Nouvelles visites</td>
            <?php
            $totalVisite = 0;
            foreach ($aMedium as $gam_code => $libelle) {
                $totalVisite += $aGAD_VISITS[$gam_code];
                $totalNouvellesVisite += $aGAD_NEWVISITS[$gam_code]; ?>
                <td class="aligncenter"><?php echo formatNum($aGAD_VISITS[$gam_code], ($aGAD_NEWVISITS[$gam_code]/$aGAD_VISITS[$gam_code])*100); ?>%</td>
            <?php } ?>
            <td class="aligncenter">
                <?php echo formatNum($totalVisite, ($totalNouvellesVisite/$totalVisite)*100) ?>%
            </td>
        </tr>
        <tr>
            <td class="aligncenter">Taux de rebond</td>
            <?php
            $totalVisite = 0;
            foreach ($aMedium as $gam_code => $libelle) {
                $totalEntrances += $aGAD_ENTRANCES[$gam_code];
                $totalBounces += $aGAD_BOUNCES[$gam_code]; ?>
                <td class="aligncenter"><?php echo formatNum($aGAD_ENTRANCES[$gam_code], ($aGAD_BOUNCES[$gam_code]/$aGAD_ENTRANCES[$gam_code])*100); ?>%</td>
            <?php } ?>
            <td class="aligncenter">
                <?php echo formatNum($totalEntrances, ($totalBounces/$totalEntrances)*100) ?>%
            </td>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>
