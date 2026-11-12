<?php
require 'lib.php'; require 'crud.php';
run_module([
  'title' => 'Planting records', 'one' => 'planting record', 'table' => 'planting', 'pk' => 'planting_id',
  'from' => 'planting t JOIN farm f ON f.farm_id = t.farm_id JOIN crop c ON c.crop_id = t.crop_id JOIN planting_status ps ON ps.status_id = t.status_id',
  'select' => 't.planting_id, t.created_by, t.planting_date, t.expected_harvest_date, t.area_planted, f.farm_name, c.crop_name, ps.description',
  'search' => ['f.farm_name', 'c.crop_name'], 'placeholder' => 'Search farm or crop name…',
  'filter' => ['expr' => 't.status_id', 'label' => 'All statuses', 'options' => 'SELECT status_id, description FROM planting_status ORDER BY status_id'],
  'sorts' => ['planting_date' => 't.planting_date', 'area' => 't.area_planted', 'expected' => 't.expected_harvest_date'], 'defsort' => 'planting_date', 'defdir' => 'desc',
  'cols' => [['Planting date', fn($r) => $r['planting_date'], 'planting_date'], ['Farm', fn($r) => $r['farm_name']], ['Crop', fn($r) => $r['crop_name']],
             ['Area (ha)', fn($r) => $r['area_planted'], 'area'], ['Expected harvest', fn($r) => $r['expected_harvest_date'] ?: '–', 'expected'], ['Status', fn($r) => $r['description']]],
  'fields' => [
    ['name' => 'farm_id', 'label' => 'Farm', 'type' => 'select', 'options' => 'SELECT farm_id, farm_name FROM farm ORDER BY 2'],
    ['name' => 'crop_id', 'label' => 'Crop', 'type' => 'select', 'options' => 'SELECT crop_id, crop_name FROM crop ORDER BY 2'],
    ['name' => 'planting_date', 'label' => 'Planting date', 'type' => 'date'],
    ['name' => 'expected_harvest_date', 'label' => 'Expected harvest date (optional)', 'type' => 'date', 'optional' => true],
    ['name' => 'area_planted', 'label' => 'Area planted (hectares)', 'type' => 'number', 'min' => 0.01, 'max' => 999999.99],
    ['name' => 'status_id', 'label' => 'Status', 'type' => 'select', 'options' => 'SELECT status_id, description FROM planting_status ORDER BY status_id']],
  'validate' => function ($v) {
    if ($v['expected_harvest_date'] !== '' && $v['expected_harvest_date'] < $v['planting_date']) return 'Expected harvest date cannot be before the planting date.';
    $size = q('SELECT size_hectares FROM farm WHERE farm_id = ?', [$v['farm_id']])->fetchColumn();
    return ($size && $v['area_planted'] > $size) ? "Area planted ({$v['area_planted']} ha) is larger than the farm itself ($size ha)." : null;
  },
  'fkmsg' => 'This planting still has harvest records. Delete those first.',
]);
