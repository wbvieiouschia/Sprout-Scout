<?php
require 'lib.php'; require 'crud.php';
run_module([
  'title' => 'Farms', 'one' => 'farm', 'table' => 'farm', 'pk' => 'farm_id',
  'from' => 'farm t JOIN farmer fr ON fr.farmer_id = t.farmer_id JOIN irrigation_type i ON i.irrigation_type_id = t.irrigation_type_id',
  'select' => 't.farm_id, t.created_by, t.farm_name, t.location, t.size_hectares, fr.first_name, fr.last_name, i.description',
  'search' => ['t.farm_name', 't.location'], 'placeholder' => 'Search farm name or location…',
  'filter' => ['expr' => 't.irrigation_type_id', 'label' => 'All irrigation types', 'options' => 'SELECT irrigation_type_id, description FROM irrigation_type ORDER BY 2'],
  'sorts' => ['farm_name' => 't.farm_name', 'location' => 't.location', 'size' => 't.size_hectares'], 'defsort' => 'farm_name', 'defdir' => 'asc',
  'cols' => [['Farm', fn($r) => $r['farm_name'], 'farm_name'], ['Owner', fn($r) => $r['first_name'] . ' ' . $r['last_name']],
             ['Location', fn($r) => $r['location'], 'location'], ['Size (ha)', fn($r) => $r['size_hectares'], 'size'], ['Irrigation', fn($r) => $r['description']]],
  'fields' => [
    ['name' => 'farm_name', 'label' => 'Farm name', 'type' => 'text', 'max' => 100],
    ['name' => 'farmer_id', 'label' => 'Owner (farmer)', 'type' => 'select', 'options' => "SELECT farmer_id, last_name || ', ' || first_name FROM farmer ORDER BY 2"],
    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'max' => 150],
    ['name' => 'size_hectares', 'label' => 'Size (hectares)', 'type' => 'number', 'min' => 0.01, 'max' => 100000],
    ['name' => 'irrigation_type_id', 'label' => 'Irrigation type', 'type' => 'select', 'options' => 'SELECT irrigation_type_id, description FROM irrigation_type ORDER BY 2']],
  'fkmsg' => 'This farm still has planting records. Delete those first.',
]);
