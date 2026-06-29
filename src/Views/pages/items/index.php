<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <h1>Vybavenie</h1>
    <div class="legend">
        <span class="legend-item"><span class="badge badge-available">Dostupné</span></span>
        <span class="legend-item"><span class="badge badge-borrowed">Požičané</span></span>
        <span class="legend-item"><span class="badge badge-maintenance">Výučba</span></span>
        <span class="legend-item" style="display:flex;align-items:center;gap:.3rem">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#ede9fe;border:2px solid #6d28d9"></span>
            <span style="font-size:.85rem;color:var(--text-muted)">Premiestnené</span>
        </span>
    </div>
</div>

<?php
function sortUrl(string $col, array $filters): string {
    $currentSort = $filters['sort'] ?? 'name';
    $currentDir  = $filters['dir']  ?? 'asc';
    $newDir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
    return BASE_URL . '/items?' . http_build_query(array_filter(['search' => $filters['search'] ?? '', 'status' => $filters['status'] ?? '', 'room' => $filters['room'] ?? '', 'sort' => $col, 'dir' => $newDir]));
}

function sortArrow(string $col, array $filters): string {
    if (($filters['sort'] ?? 'name') !== $col) return ' <span style="opacity:.3">↕</span>';
    return ($filters['dir'] ?? 'asc') === 'asc' ? ' ↑' : ' ↓';
}
?>

<form class="filter-bar" method="GET" action="<?= BASE_URL ?>/items">
    <?php if (!empty($filters['sort'])): ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
        <input type="hidden" name="dir"  value="<?= htmlspecialchars($filters['dir']) ?>">
    <?php endif; ?>

    <input type="text" name="search" placeholder="Hľadať: názov, inventárne číslo"
           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">

    <?php if (!empty($rooms)): ?>
        <select name="room">
            <option value="">Všetky miestnosti</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?= htmlspecialchars($room['code']) ?>" <?= ($filters['room'] ?? '') === $room['code'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($room['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <select name="status">
        <option value="">Všetky stavy</option>
        <option value="available"   <?= ($filters['status'] ?? '') === 'available'   ? 'selected' : '' ?>>Dostupné</option>
        <option value="borrowed"    <?= ($filters['status'] ?? '') === 'borrowed'    ? 'selected' : '' ?>>Požičané</option>
        <option value="maintenance" <?= ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Výučba</option>
    </select>

    <button type="submit" class="btn">Hľadať</button>
    <a href="<?= BASE_URL ?>/items" class="btn btn-outline">Resetovať</a>
    <?php
    $exportParams = array_filter([
            'search' => $filters['search'] ?? '',
            'status' => $filters['status'] ?? '',
            'room'   => $filters['room']   ?? '',
            'sort'   => $filters['sort']   ?? '',
            'dir'    => $filters['dir']    ?? '',
    ]);
    $exportUrl = BASE_URL . '/items/export?' . http_build_query($exportParams);
    ?>
    <a href="<?= $exportUrl ?>" class="btn btn-outline" style="margin-left:auto">📥 Export CSV</a>
</form>

<?php if (empty($items)): ?>
    <p class="empty-state">Nič sa nenašlo.</p>
<?php else: ?>
    <div class="items-table-wrap">
        <table class="items-table">
            <thead>
            <tr>
                <th><a href="<?= sortUrl('sap_num', $filters) ?>" class="sort-link">SAP č.<?= sortArrow('sap_num', $filters) ?></a></th>
                <th><a href="<?= sortUrl('category', $filters) ?>" class="sort-link">Inventárne č.<?= sortArrow('category', $filters) ?></a></th>
                <th><a href="<?= sortUrl('name', $filters) ?>" class="sort-link">Názov<?= sortArrow('name', $filters) ?></a></th>
                <th><a href="<?= sortUrl('room_name', $filters) ?>" class="sort-link">Miestnosť<?= sortArrow('room_name', $filters) ?></a></th>
                <th>Skriňa / Polica</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                if (!empty($item['personal'])) continue;

                $catParts = array_filter([
                        $item['category_symbol'] ?? '',
                        $item['category_num1']   ?? '',
                        $item['category_num2']   ?? '',
                        $item['category_num3']   ?? '',
                ]);
                $category = implode(' / ', $catParts) ?: '—';

                $hasTempLocation = !empty($item['temp_closet']) || !empty($item['temp_shelf']) || !empty($item['temp_room']);

                $displayRoom   = !empty($item['temp_room']) ? ($item['temp_room_name'] ?? $item['temp_room']) : ($item['room_name'] ?? $item['sap_position'] ?? '');
                $displayCloset = $item['temp_closet'] ?: $item['closet'];
                $displayShelf  = $item['temp_shelf']  ?: $item['shelf'];
                $loc           = array_filter([$displayCloset, $displayShelf]);
                $location      = $loc ? implode(' / ', $loc) : '—';

                $origRoom   = $item['room_name'] ?? $item['sap_position'] ?? '';
                $origCloset = $item['closet'] ?? '';
                $origShelf  = $item['shelf']  ?? '';
                $origLoc    = array_filter([$origCloset, $origShelf]);
                $origStr    = implode(' / ', $origLoc);

                $badgeClass = match(true) {
                    !empty($item['personal'])         => 'personal',
                    $item['status'] === 'available'   => 'available',
                    $item['status'] === 'borrowed'    => 'borrowed',
                    $item['status'] === 'maintenance' => 'maintenance',
                    default                           => $item['status'],
                };
                $badgeText = match(true) {
                    !empty($item['personal'])         => 'Osobná karta',
                    $item['status'] === 'available'   => 'Dostupné',
                    $item['status'] === 'borrowed'    => 'Požičané',
                    $item['status'] === 'maintenance' => 'Výučba' . (!empty($item['vyucba_label']) ? ': ' . htmlspecialchars($item['vyucba_label']) : ''),
                    default                           => $item['status'],
                };
                ?>
                <tr <?= $hasTempLocation ? 'style="background:#faf5ff"' : '' ?>>
                    <td class="mono"><?= htmlspecialchars($item['sap_num'] ?? '—') ?></td>
                    <td class="mono"><?= htmlspecialchars($category) ?></td>
                    <td>
        <span class="badge badge-<?= $badgeClass ?>" style="font-size:.95rem;padding:.35rem .75rem">
            <?= $badgeText ?>
        </span>
                        <span style="margin-left:.4rem"><?= htmlspecialchars($item['name']) ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars($displayRoom ?: '—') ?>
                        <?php if ($hasTempLocation && $origRoom && $origRoom !== $displayRoom): ?>
                            <span style="color:var(--text-muted);font-size:.78rem">(<?= htmlspecialchars($origRoom) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono">
                        <?= htmlspecialchars($location) ?>
                        <?php if ($hasTempLocation && $origStr && $origStr !== $location): ?>
                            <span style="color:var(--text-muted);font-size:.78rem">(<?= htmlspecialchars($origStr) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= BASE_URL ?>/items/show?id=<?= $item['id'] ?>" class="btn btn-sm">→</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
    .legend { display:flex; gap:.6rem; align-items:center; flex-wrap:wrap; }
    .legend-item { display:flex; align-items:center; gap:.3rem; font-size:.85rem; color:var(--text-muted); }
    .sort-link { color:inherit; text-decoration:none; white-space:nowrap; }
    .sort-link:hover { text-decoration:underline; }
    .items-table tr[style*="background:#faf5ff"]:hover td { background: #f3e8ff; }
</style>