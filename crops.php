<?php
require 'lib.php'; require 'crud.php';
run_module([
  'title' => 'Crops', 'one' => 'crop', 'table' => 'crop', 'pk' => 'crop_id',
  'from' => 'crop t JOIN crop_category cc ON cc.category_id = t.category_id',
  'select' => 't.crop_id, t.created_by, t.crop_name, t.unit_price, cc.description',
  'search' => ['t.crop_name'], 'placeholder' => 'Search crop name…',
  'filter' => ['expr' => 't.category_id', 'label' => 'All categories', 'options' => 'SELECT category_id, description FROM crop_category ORDER BY 2'],
  'sorts' => ['crop_name' => 't.crop_name', 'price' => 't.unit_price'], 'defsort' => 'crop_name', 'defdir' => 'asc',
  'cols' => [['Crop', fn($r) => $r['crop_name'], 'crop_name'], ['Category', fn($r) => $r['description']],
             ['Price per kg (₱)', fn($r) => number_format($r['unit_price'], 2), 'price']],
  'fields' => [
    ['name' => 'crop_name', 'label' => 'Crop name', 'type' => 'text', 'max' => 80],
    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => 'SELECT category_id, description FROM crop_category ORDER BY 2'],
    ['name' => 'unit_price', 'label' => 'Price per kg (₱)', 'type' => 'number', 'min' => 0, 'max' => 99999999]],
  'dupmsg' => 'A crop with that name already exists.',
  'fkmsg' => 'This crop is used in planting records. Delete those first.',
]);
