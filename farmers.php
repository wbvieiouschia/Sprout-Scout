<?php
require 'lib.php'; require 'crud.php';
run_module([
  'title' => 'Farmers', 'one' => 'farmer', 'table' => 'farmer', 'pk' => 'farmer_id',
  'from' => 'farmer t JOIN municipality m ON m.municipality_id = t.municipality_id',
  'select' => 't.farmer_id, t.created_by, t.first_name, t.last_name, t.barangay, t.contact_no, m.municipality_name, m.province',
  'search' => ['t.first_name', 't.last_name', 't.barangay', 't.contact_no'], 'placeholder' => 'Search name, barangay or contact…',
  'filter' => ['expr' => 't.municipality_id', 'label' => 'All municipalities', 'options' => "SELECT municipality_id, municipality_name || ', ' || province FROM municipality ORDER BY 2"],
  'sorts' => ['last_name' => 't.last_name', 'barangay' => 't.barangay'], 'defsort' => 'last_name', 'defdir' => 'asc',
  'cols' => [['Name', fn($r) => $r['first_name'] . ' ' . $r['last_name'], 'last_name'], ['Barangay', fn($r) => $r['barangay'], 'barangay'],
             ['Municipality', fn($r) => $r['municipality_name']], ['Province', fn($r) => $r['province']], ['Contact', fn($r) => $r['contact_no']]],
  'fields' => [
    ['name' => 'first_name', 'label' => 'First name', 'type' => 'text', 'max' => 60],
    ['name' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'max' => 60],
    ['name' => 'barangay', 'label' => 'Barangay', 'type' => 'text', 'max' => 80],
    ['name' => 'municipality_id', 'label' => 'Municipality', 'type' => 'select', 'options' => "SELECT municipality_id, municipality_name || ', ' || province FROM municipality ORDER BY 2"],
    ['name' => 'contact_no', 'label' => 'Contact number', 'type' => 'text', 'max' => 20]],
  'validate' => fn($v) => preg_match('/^\d{7,15}$/', preg_replace('/[\s\-+]/', '', $v['contact_no'])) ? null : 'Contact number must be 7 to 15 digits.',
  'fkmsg' => 'This farmer still has farms. Delete or reassign those farms first.',
]);
