<div class="page-header">
    <h1>Import vybavenia z Excelu</h1>
</div>

<?php if (!isset($counts)): ?>
    <!-- ===================== UPLOAD FORM ===================== -->
    <div class="detail-card" style="max-width:560px">
        <h2>Nahrať súbor</h2>
        <p style="color:var(--text-muted);margin-bottom:1.2rem">
            Akceptuje sa súbor <strong>.xlsx, .xls, .csv</strong> — prvý hárok zošita.
            Riadok 1 musí obsahovať názvy stĺpcov.
        </p>

        <form method="POST" action="<?= BASE_URL ?>/admin/import" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Súbor Excel (.xlsx, .xls, .csv)</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required
                       style="display:block;margin-top:.4rem;width:100%">
            </div>
            <div style="margin-top:1.4rem;display:flex;gap:1rem">
                <button type="submit" class="btn btn-success">Importovať</button>
                <a href="<?= BASE_URL ?>/admin" class="btn btn-outline">Zrušiť</a>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- ===================== IMPORT RESULTS ===================== -->
    <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card stat-green">
            <div class="stat-num"><?= $counts['inserted'] ?></div>
            <div class="stat-label">Pridané</div>
        </div>
        <div class="stat-card stat-yellow">
            <div class="stat-num"><?= $counts['updated'] ?></div>
            <div class="stat-label">Aktualizované</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $counts['archived'] ?></div>
            <div class="stat-label">Archivované</div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-num"><?= $counts['errors'] ?></div>
            <div class="stat-label">Chýb</div>
        </div>
    </div>

    <?php
// Filter only important log entries
    $importantLog = array_filter($log, fn($e) => in_array($e['status'], ['inserted', 'archived', 'conflict', 'error']));
    ?>

    <?php if (!empty($importantLog)): ?>
        <div class="detail-card">
            <h2>Dôležité udalosti</h2>
            <div class="items-table-wrap" style="max-height:520px;overflow-y:auto;margin-top:1rem">
                <table class="items-table">
                    <thead>
                    <tr>
                        <th>Stav</th>
                        <th>Riadok</th>
                        <th>SAP č.</th>
                        <th>Názov</th>
                        <th>Detaily</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $badgeMap = [
                            'inserted' => ['available',   'Pridané'],
                            'archived' => ['borrowed',    'Archivované'],
                            'conflict' => ['maintenance', 'Konflikt'],
                            'error'    => ['borrowed',    'Chyba'],
                    ];
                    foreach ($importantLog as $entry):
                        $b = $badgeMap[$entry['status']] ?? ['borrowed', $entry['status']];
                        ?>
                        <tr>
                            <td><span class="badge badge-<?= $b[0] ?>"><?= $b[1] ?></span></td>
                            <td class="mono"><?= htmlspecialchars((string)($entry['row'] ?? '—')) ?></td>
                            <td class="mono"><?= htmlspecialchars($entry['sap']  ?? '—') ?></td>
                            <td><?= htmlspecialchars($entry['name'] ?? '—') ?></td>
                            <td style="color:var(--text-muted);font-size:.85rem">
                                <?= htmlspecialchars($entry['msg'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div style="margin-top:1.5rem;display:flex;gap:1rem">
        <a href="<?= BASE_URL ?>/admin/import" class="btn">Importovať ďalšie</a>
        <a href="<?= BASE_URL ?>/items" class="btn btn-outline">Zoznam vybavenia</a>
        <a href="<?= BASE_URL ?>/admin" class="btn btn-outline">Panel správcu</a>
    </div>
<?php endif; ?>