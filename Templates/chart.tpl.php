<div class="small-12 columns chart_controls" id="chart_controls_<?=$chart->id?>">
    <?= $chart->renderControls(); ?>
</div>
<ul class="chart_totals" id="chart_totals_<?=$chart->id?>">

</ul>
<div class="small-12 columns">
    <?= $chart->renderCanvas(); ?>
</div>
