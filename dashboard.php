<?php
require 'lib.php';
page_start('Dashboard', 'dashboard.php');
$n = fn($t) => (int)q("SELECT count(*) FROM $t")->fetchColumn();
$totalQty = (float)q('SELECT COALESCE(SUM(quantity), 0) FROM harvest')->fetchColumn();
$cards = [['farmers.php', 'Farmers', $n('farmer')], ['farms.php', 'Farms', $n('farm')], ['crops.php', 'Crops', $n('crop')],
  ['plantings.php', 'Planting records', $n('planting')], ['harvests.php', 'Harvest records', $n('harvest')], ['harvests.php', 'Total harvested (kg)', number_format($totalQty, 2)]];
$status = q('SELECT ps.description, count(p.planting_id) AS n FROM planting_status ps LEFT JOIN planting p ON p.status_id = ps.status_id GROUP BY ps.status_id, ps.description ORDER BY ps.status_id')->fetchAll();
$max = max(1, ...array_column($status, 'n') ?: [1]);
$rp = q('SELECT p.planting_date, f.farm_name, c.crop_name, ps.description FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id JOIN planting_status ps ON ps.status_id = p.status_id ORDER BY p.planting_date DESC LIMIT 5')->fetchAll();
$rh = q('SELECT h.harvest_date, h.quantity, f.farm_name, c.crop_name FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id ORDER BY h.harvest_date DESC LIMIT 5')->fetchAll();
?>
<h1>Hello, <?= esc($ME['name']) ?>!</h1>
<div class="cards"><?php foreach ($cards as [$href, $label, $v]): ?>
  <a class="card stat" href="<?= $href ?>"><div class="big"><?= esc($v) ?></div><?= esc($label) ?></a><?php endforeach; ?></div>
<div class="dash-grid">
  <section class="card wide-card"><h3>Plantings by status</h3><div class="bars">
    <?php foreach ($status as $s): ?><div class="bar-row"><span class="bar-label"><?= esc($s['description']) ?></span>
      <span class="bar-track"><span class="bar-fill" style="width:<?= $s['n'] / $max * 100 ?>%"></span></span><span class="bar-num"><?= $s['n'] ?></span></div><?php endforeach; ?></div></section>
  <section class="card wide-card"><h3>Recent plantings</h3><div class="table-wrap plain"><table>
    <thead><tr><th>Date</th><th>Farm</th><th>Crop</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rp as $r): ?><tr><td><?= esc($r['planting_date']) ?></td><td><?= esc($r['farm_name']) ?></td><td><?= esc($r['crop_name']) ?></td><td><?= esc($r['description']) ?></td></tr><?php endforeach; ?>
    <?php if (!$rp): ?><tr><td colspan="4">No planting records yet.</td></tr><?php endif; ?></tbody></table></div></section>
  <section class="card wide-card"><h3>Recent harvests</h3><div class="table-wrap plain"><table>
    <thead><tr><th>Date</th><th>Farm</th><th>Crop</th><th>Quantity (kg)</th></tr></thead><tbody>
    <?php foreach ($rh as $r): ?><tr><td><?= esc($r['harvest_date']) ?></td><td><?= esc($r['farm_name']) ?></td><td><?= esc($r['crop_name']) ?></td><td><?= number_format($r['quantity'], 2) ?></td></tr><?php endforeach; ?>
    <?php if (!$rh): ?><tr><td colspan="4">No harvest records yet.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
<?php page_end();
