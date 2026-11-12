<?php
// One reusable engine for all five modules: list + search + filter + sort + pagination + add/view/edit/delete.
const PAGE_SIZE = 10;

function opts($f) { return q($f['options'])->fetchAll(PDO::FETCH_KEY_PAIR); }

function check_fields($c, $v) {
  foreach ($c['fields'] as $f) {
    [$n, $label, $type] = [$f['name'], $f['label'], $f['type']]; $x = $v[$n];
    if ($x === '') { if (!empty($f['optional'])) continue; return "$label is required."; }
    if ($type === 'text' && mb_strlen($x) > ($f['max'] ?? 150)) return "$label is too long.";
    if ($type === 'number') {
      if (!is_numeric($x)) return "$label must be a number.";
      if ($x < $f['min']) return "$label must be at least {$f['min']}.";
      if ($x > $f['max']) return "$label must be at most {$f['max']}.";
    }
    if ($type === 'date') {
      $d = DateTime::createFromFormat('Y-m-d', $x);
      if (!$d || $d->format('Y-m-d') !== $x) return "$label is not a valid date.";
    }
    if ($type === 'select' && !array_key_exists($x, opts($f))) return "Please choose a valid $label.";
  }
  return null;
}

function run_module(array $c) {
  global $ME;
  $pk = $c['pk']; $t = $c['table']; $self = basename($_SERVER['PHP_SELF']);
  $mine = fn($r) => $ME['admin'] || ($r['created_by'] ?? null) === $ME['uid'];
  $form = null; $formErr = null; $mode = null; $id = 0;

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['act'] ?? '') === 'delete') {
      $row = q("SELECT created_by FROM $t WHERE $pk = ?", [$id])->fetch();
      if (!$row || !$mine($row)) flash('You are not allowed to delete that record.', 'error');
      else try { q("DELETE FROM $t WHERE $pk = ?", [$id]); flash(ucfirst($c['one']) . ' deleted.'); }
      catch (PDOException $e) { flash($e->getCode() === '23503' ? $c['fkmsg'] : 'Could not delete the record.', 'error'); }
      header("Location: $self"); exit;
    }
    $v = []; foreach ($c['fields'] as $f) $v[$f['name']] = trim($_POST[$f['name']] ?? '');
    $formErr = check_fields($c, $v) ?? (isset($c['validate']) ? $c['validate']($v) : null);
    $old = $id ? q("SELECT created_by FROM $t WHERE $pk = ?", [$id])->fetch() : null;
    if (!$formErr && $id && (!$old || !$mine($old))) $formErr = 'You are not allowed to change that record.';
    if (!$formErr) {
      $vals = []; foreach ($c['fields'] as $f) $vals[$f['name']] = $v[$f['name']] === '' ? null : $v[$f['name']];
      try {
        if ($id) { q("UPDATE $t SET " . implode(', ', array_map(fn($k) => "$k = ?", array_keys($vals))) . " WHERE $pk = ?", [...array_values($vals), $id]); }
        else { $vals['created_by'] = $ME['uid']; q("INSERT INTO $t (" . implode(', ', array_keys($vals)) . ") VALUES (" . implode(', ', array_fill(0, count($vals), '?')) . ")", array_values($vals)); }
        flash(ucfirst($c['one']) . ($id ? ' updated.' : ' added.')); header("Location: $self"); exit;
      } catch (PDOException $e) { $formErr = $e->getCode() === '23505' ? ($c['dupmsg'] ?? 'That record already exists.') : "Could not save the {$c['one']}. Please check the details and try again."; }
    }
    $form = $v; $mode = $id ? 'edit' : 'add';
  } elseif (isset($_GET['add'])) { $mode = 'add'; $form = []; }
  elseif (isset($_GET['view']) || isset($_GET['edit'])) {
    $mode = isset($_GET['view']) ? 'view' : 'edit'; $id = (int)($_GET['view'] ?? $_GET['edit']);
    $form = q("SELECT * FROM $t WHERE $pk = ?", [$id])->fetch();
    if (!$form || ($mode === 'edit' && !$mine($form))) { flash('Record not available.', 'error'); header("Location: $self"); exit; }
  }

  page_start($c['title'], $self);
  echo '<div class="page-head"><h1>' . esc($c['title']) . '</h1>' . ($mode ? '' : '<a class="btn primary" href="?add=1">+ Add ' . esc($c['one']) . '</a>') . '</div>';

  if ($mode) {   // ---------- form (add / edit / view) ----------
    $ro = $mode === 'view' ? ' disabled' : '';
    echo '<form method="post" class="card formcard" novalidate><h2>' . ucfirst($mode) . ' ' . esc($c['one']) . '</h2>';
    echo '<input type="hidden" name="csrf" value="' . csrf() . '"><input type="hidden" name="id" value="' . $id . '">';
    foreach ($c['fields'] as $f) {
      $n = $f['name']; $val = $form[$n] ?? '';
      echo '<label for="' . $n . '">' . esc($f['label']) . '</label>';
      if ($f['type'] === 'select') {
        echo "<select id=\"$n\" name=\"$n\"$ro><option value=\"\">Choose…</option>";
        foreach (opts($f) as $k => $lab) echo '<option value="' . esc($k) . '"' . ((string)$k === (string)$val ? ' selected' : '') . '>' . esc($lab) . '</option>';
        echo '</select>';
      } else {
        $attr = $f['type'] === 'number' ? ' type="number" step="0.01" min="' . $f['min'] . '" max="' . $f['max'] . '"' : ($f['type'] === 'date' ? ' type="date"' : ' maxlength="' . ($f['max'] ?? 150) . '"');
        echo "<input id=\"$n\" name=\"$n\" value=\"" . esc($val) . "\"$attr$ro>";
      }
    }
    if ($formErr) echo '<div class="msg error">' . esc($formErr) . '</div>';
    echo '<div class="dlg-actions"><a class="btn" href="' . $self . '">Close</a>' . ($mode === 'view' ? '' : '<button type="submit">Save</button>') . '</div></form>';
    return page_end();
  }

  // ---------- list ----------
  $s = trim($_GET['s'] ?? ''); $fv = $_GET['f'] ?? ''; $page = max(0, (int)($_GET['p'] ?? 0));
  $sort = isset($c['sorts'][$_GET['sort'] ?? '']) ? $_GET['sort'] : $c['defsort']; $asc = ($_GET['dir'] ?? $c['defdir']) !== 'desc';
  $where = []; $args = [];
  if ($s !== '') { $where[] = '(' . implode(' OR ', array_map(fn($e) => "$e ILIKE ?", $c['search'])) . ')'; foreach ($c['search'] as $_) $args[] = "%$s%"; }
  if ($fv !== '' && ctype_digit($fv)) { $where[] = $c['filter']['expr'] . ' = ?'; $args[] = (int)$fv; }
  $W = $where ? 'WHERE ' . implode(' AND ', $where) : '';
  $total = (int)q("SELECT count(*) FROM {$c['from']} $W", $args)->fetchColumn();
  $pages = max(1, (int)ceil($total / PAGE_SIZE)); $page = min($page, $pages - 1);
  $rows = q("SELECT {$c['select']} FROM {$c['from']} $W ORDER BY {$c['sorts'][$sort]} " . ($asc ? 'ASC' : 'DESC') . " LIMIT " . PAGE_SIZE . " OFFSET " . ($page * PAGE_SIZE), $args)->fetchAll();

  echo '<form class="toolbar" method="get"><input name="s" type="search" placeholder="' . esc($c['placeholder']) . '" value="' . esc($s) . '">';
  echo '<select name="f" onchange="this.form.submit()"><option value="">' . esc($c['filter']['label']) . '</option>';
  foreach (opts($c['filter']) as $k => $lab) echo '<option value="' . esc($k) . '"' . ((string)$k === $fv ? ' selected' : '') . '>' . esc($lab) . '</option>';
  echo '</select><button type="submit">Search</button><a class="btn" href="' . $self . '">Clear</a></form>';

  $qs = fn($over) => '?' . http_build_query(array_merge(['s' => $s, 'f' => $fv, 'sort' => $sort, 'dir' => $asc ? 'asc' : 'desc'], $over));
  echo '<div class="table-wrap"><table><thead><tr>';
  foreach ($c['cols'] as [$label, , $key]) {
    echo '<th>' . ($key ? '<a href="' . $qs(['sort' => $key, 'dir' => ($sort === $key && $asc) ? 'desc' : 'asc']) . '">' . esc($label) . ($sort === $key ? ($asc ? ' ▲' : ' ▼') : '') . '</a>' : esc($label)) . '</th>';
  }
  echo '<th>Actions</th></tr></thead><tbody>';
  foreach ($rows as $r) {
    echo '<tr>'; foreach ($c['cols'] as [, $fn]) echo '<td>' . esc($fn($r)) . '</td>';
    echo '<td class="actions"><a class="btn" href="?view=' . $r[$pk] . '">View</a>';
    if ($mine($r)) echo ' <a class="btn" href="?edit=' . $r[$pk] . '">Edit</a>
      <form method="post" onsubmit="return confirm(\'Delete this ' . esc($c['one']) . ' permanently? This cannot be undone.\')">
      <input type="hidden" name="csrf" value="' . csrf() . '"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="' . $r[$pk] . '">
      <button class="danger" style="padding:.15rem .6rem;font-size:.85rem">Delete</button></form>';
    echo '</td></tr>';
  }
  if (!$rows) echo '<tr><td colspan="' . (count($c['cols']) + 1) . '">No records found.</td></tr>';
  echo '</tbody></table></div><div class="pager">';
  echo '<a href="' . $qs(['p' => $page - 1]) . '" class="' . ($page === 0 ? 'off' : '') . '">‹ Prev</a>';
  echo '<span>Page ' . ($page + 1) . ' of ' . $pages . ' (' . $total . ' records)</span>';
  echo '<a href="' . $qs(['p' => $page + 1]) . '" class="' . ($page + 1 >= $pages ? 'off' : '') . '">Next ›</a></div>';
  page_end();
}
