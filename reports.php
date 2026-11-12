<?php
require 'lib.php';
// Each report: [title, SQL ({W} = optional date filter), date column or null, column to total or null]
$R = [
 'farmers' => ['Farmers and their farms',
   "SELECT fr.last_name || ', ' || fr.first_name AS farmer, f.farm_name AS farm, f.location, f.size_hectares AS size_ha, i.description AS irrigation
    FROM farmer fr LEFT JOIN farm f ON f.farmer_id = fr.farmer_id LEFT JOIN irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id ORDER BY 1, 2", null, null],
 'cropsfarm' => ['Crops planted per farm',
   "SELECT f.farm_name AS farm, c.crop_name AS crop, count(*) AS plantings, sum(p.area_planted) AS total_area_ha
    FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id WHERE 1=1 {W} GROUP BY f.farm_name, c.crop_name ORDER BY 1, 2", 'p.planting_date', 'plantings'],
 'status' => ['Planting records by status',
   "SELECT ps.description AS status, count(*) AS plantings, sum(p.area_planted) AS total_area_ha
    FROM planting p JOIN planting_status ps ON ps.status_id = p.status_id WHERE 1=1 {W} GROUP BY ps.status_id, ps.description ORDER BY ps.status_id", 'p.planting_date', 'plantings'],
 'harvests' => ['Harvest records',
   "SELECT h.harvest_date, f.farm_name AS farm, c.crop_name AS crop, h.quantity AS quantity_kg, h.selling_price AS price_per_kg, g.description AS grade
    FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
    JOIN quality_grade g ON g.grade_id = h.grade_id WHERE 1=1 {W} ORDER BY h.harvest_date DESC", 'h.harvest_date', null],
 'qty' => ['Total harvested quantity (per crop)',
   "SELECT c.crop_name AS crop, count(*) AS harvests, sum(h.quantity) AS total_kg
    FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN crop c ON c.crop_id = p.crop_id WHERE 1=1 {W} GROUP BY c.crop_name ORDER BY 3 DESC", 'h.harvest_date', 'total_kg'],
 'value' => ['Estimated harvest value (quantity × selling price)',
   "SELECT c.crop_name AS crop, sum(h.quantity) AS total_kg, sum(h.quantity * h.selling_price) AS est_value_php
    FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN crop c ON c.crop_id = p.crop_id WHERE 1=1 {W} GROUP BY c.crop_name ORDER BY 3 DESC", 'h.harvest_date', 'est_value_php'],
];
$key = isset($R[$_GET['r'] ?? '']) ? $_GET['r'] : 'farmers';
[$title, $sql, $dcol, $sumcol] = $R[$key];
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? ''; $args = []; $W = ''; $err = null;
$okd = fn($d) => ($x = DateTime::createFromFormat('Y-m-d', $d)) && $x->format('Y-m-d') === $d;
if ($dcol) {
  if ($from !== '' && !$okd($from)) $err = 'The "from" date is not valid.';
  elseif ($to !== '' && !$okd($to)) $err = 'The "to" date is not valid.';
  elseif ($from !== '' && $to !== '' && $to < $from) $err = 'The "to" date cannot be before the "from" date.';
  else {
    if ($from !== '') { $W .= " AND $dcol >= ?"; $args[] = $from; }
    if ($to !== '')   { $W .= " AND $dcol <= ?"; $args[] = $to; }
  }
}
page_start('Reports', 'reports.php');
$rows = $err ? [] : q(str_replace('{W}', $W, $sql), $args)->fetchAll();
?>
<h1>Reports</h1>
<form class="toolbar" method="get">
  <select name="r" onchange="this.form.submit()"><?php foreach ($R as $k => $d): ?><option value="<?= $k ?>"<?= $k === $key ? ' selected' : '' ?>><?= esc($d[0]) ?></option><?php endforeach; ?></select>
  <?php if ($dcol): ?><input type="date" name="from" value="<?= esc($from) ?>" title="From"><input type="date" name="to" value="<?= esc($to) ?>" title="To"><?php endif; ?>
  <button type="submit">Run report</button><a class="btn" href="reports.php?r=<?= $key ?>">Clear</a><button type="button" class="secondary" onclick="window.print()">Print</button>
</form>
<?php if ($err): ?><div class="msg error"><?= esc($err) ?></div><?php endif; ?>
<h2><?= esc($title) ?></h2>
<div class="table-wrap"><table><thead><tr>
  <?php foreach ($rows ? array_keys($rows[0]) : [] as $h): ?><th><?= esc(ucfirst(str_replace('_', ' ', $h))) ?></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><?php foreach ($r as $v): ?><td><?= esc(is_numeric($v) && strpos($v, '.') !== false ? number_format($v, 2) : $v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td>No data found for this report.</td></tr><?php endif; ?></tbody>
  <?php if ($rows && $sumcol): ?><tfoot><tr><td colspan="<?= count($rows[0]) ?>">Total <?= esc(str_replace('_', ' ', $sumcol)) ?>: <?= number_format(array_sum(array_column($rows, $sumcol)), 2) ?></td></tr></tfoot><?php endif; ?>
</table></div>
<?php page_end();
