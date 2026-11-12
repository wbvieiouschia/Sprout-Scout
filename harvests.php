<?php
require 'lib.php'; require 'crud.php';
run_module([
  'title' => 'Harvest records', 'one' => 'harvest record', 'table' => 'harvest', 'pk' => 'harvest_id',
  'from' => 'harvest t JOIN planting p ON p.planting_id = t.planting_id JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
             JOIN quality_grade g ON g.grade_id = t.grade_id JOIN harvest_remark hr ON hr.remark_id = t.remark_id',
  'select' => 't.harvest_id, t.created_by, t.harvest_date, t.quantity, t.selling_price, f.farm_name, c.crop_name, g.description, hr.remark_description',
  'search' => ['f.farm_name', 'c.crop_name'], 'placeholder' => 'Search farm or crop name…',
  'filter' => ['expr' => 't.grade_id', 'label' => 'All quality grades', 'options' => 'SELECT grade_id, description FROM quality_grade ORDER BY grade_id'],
  'sorts' => ['harvest_date' => 't.harvest_date', 'quantity' => 't.quantity', 'price' => 't.selling_price'], 'defsort' => 'harvest_date', 'defdir' => 'desc',
  'cols' => [['Harvest date', fn($r) => $r['harvest_date'], 'harvest_date'], ['Farm', fn($r) => $r['farm_name']], ['Crop', fn($r) => $r['crop_name']],
             ['Quantity (kg)', fn($r) => number_format($r['quantity'], 2), 'quantity'], ['Price/kg (₱)', fn($r) => number_format($r['selling_price'], 2), 'price'],
             ['Est. value (₱)', fn($r) => number_format($r['quantity'] * $r['selling_price'], 2)], ['Grade', fn($r) => $r['description']], ['Remarks', fn($r) => $r['remark_description']]],
  'fields' => [
    ['name' => 'planting_id', 'label' => 'Planting record', 'type' => 'select',
     'options' => "SELECT p.planting_id, f.farm_name || ' – ' || c.crop_name || ' (planted ' || p.planting_date || ')' FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id ORDER BY p.planting_date DESC"],
    ['name' => 'harvest_date', 'label' => 'Harvest date', 'type' => 'date'],
    ['name' => 'quantity', 'label' => 'Quantity harvested (kg)', 'type' => 'number', 'min' => 0.01, 'max' => 99999999.99],
    ['name' => 'selling_price', 'label' => 'Selling price per kg (₱)', 'type' => 'number', 'min' => 0, 'max' => 99999999.99],
    ['name' => 'grade_id', 'label' => 'Quality grade', 'type' => 'select', 'options' => 'SELECT grade_id, description FROM quality_grade ORDER BY grade_id'],
    ['name' => 'remark_id', 'label' => 'Remarks', 'type' => 'select', 'options' => 'SELECT remark_id, remark_description FROM harvest_remark ORDER BY remark_id']],
  'validate' => function ($v) {
    $d = q('SELECT planting_date FROM planting WHERE planting_id = ?', [$v['planting_id']])->fetchColumn();
    return ($d && $v['harvest_date'] < $d) ? "Harvest date cannot be before the planting date ($d)." : null;
  },
  'fkmsg' => 'Could not delete the harvest record.',
]);
