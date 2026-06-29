<div class="page-header">
    <a href="<?= BASE_URL ?>/items" class="back-link">← Späť na zoznam</a>
    <h1><?= htmlspecialchars($item['name']) ?></h1>
</div>

<?php
$hasTempLocation = !empty($item['temp_closet']) || !empty($item['temp_shelf']) || !empty($item['temp_room']);
$isTemp          = $hasTempLocation;
?>

<div class="detail-grid">
    <div class="detail-card">
        <h2>Informácie</h2>
        <dl class="detail-list">

            <dt>SAP číslo</dt>
            <dd class="mono"><?= htmlspecialchars($item['sap_num'] ?? '—') ?></dd>

            <?php
            $invParts = array_filter([
                    $item['category_symbol'] ?? '',
                    $item['category_num1']   ?? '',
                    $item['category_num2']   ?? '',
                    $item['category_num3']   ?? '',
            ]);
            if (!empty($invParts)): ?>
                <dt>Inventárne číslo</dt>
                <dd><?= htmlspecialchars(implode(' / ', $invParts)) ?></dd>
            <?php endif; ?>

            <?php if (!empty($item['write_year'])): ?>
                <dt>Rok zápisu</dt>
                <dd><?= htmlspecialchars($item['write_year']) ?></dd>
            <?php endif; ?>

            <?php if (!empty($item['triedenie'])): ?>
                <dt>Triedenie</dt>
                <dd><?= htmlspecialchars($item['triedenie']) ?></dd>
            <?php endif; ?>

            <dt>Stav</dt>
            <dd><span class="badge badge-<?= !empty($item['personal']) ? 'personal' : $item['status'] ?>">
                <?= match(true) {
                    !empty($item['personal'])         => 'Osobná karta',
                    $item['status'] === 'available'   => 'Dostupné',
                    $item['status'] === 'borrowed'    => 'Požičané',
                    $item['status'] === 'maintenance' => 'Výučba' . (!empty($item['vyucba_label']) ? ': ' . htmlspecialchars($item['vyucba_label']) : ''),
                    default                           => $item['status']
                } ?>
            </span></dd>

            <?php if ($item['status'] === 'borrowed' && $activeLoan): ?>
                <dt>Požičal</dt>
                <dd>
                    <?= htmlspecialchars($activeLoan['user_name']) ?>
                    <span style="color:var(--text-muted);font-size:.85rem">
                        (<?= htmlspecialchars($activeLoan['borrowed_at']) ?>)
                    </span>
                </dd>
            <?php endif; ?>

            <?php if (!empty($item['personal'])): ?>
                <dt>Pridelené</dt>
                <dd><?= htmlspecialchars($item['personal']) ?></dd>
            <?php endif; ?>

            <?php if (!empty($item['sap_position'])): ?>
                <dt>Miestnosť<?= $hasTempLocation ? ' (pôvodná)' : '' ?></dt>
                <dd><?= htmlspecialchars($item['room_name'] ?? $item['sap_position']) ?></dd>
            <?php endif; ?>

            <?php if (!empty($item['closet']) || !empty($item['shelf'])): ?>
                <dt>Skriňa / Polica<?= $hasTempLocation ? ' (pôvodná)' : '' ?></dt>
                <dd class="mono"><?= htmlspecialchars(implode(' / ', array_filter([$item['closet'] ?? '', $item['shelf'] ?? '']))) ?></dd>
            <?php endif; ?>

            <?php if ($hasTempLocation): ?>
                <dt style="color:#6d28d9">Miestnosť (dočasná)</dt>
                <dd style="color:#6d28d9"><?= htmlspecialchars($item['temp_room_name'] ?? $item['temp_room'] ?? ($item['room_name'] ?? $item['sap_position'])) ?></dd>

                <?php if (!empty($item['temp_closet']) || !empty($item['temp_shelf'])): ?>
                    <dt style="color:#6d28d9">Skriňa / Polica (dočasná)</dt>
                    <dd class="mono" style="color:#6d28d9"><?= htmlspecialchars(implode(' / ', array_filter([$item['temp_closet'] ?? '', $item['temp_shelf'] ?? '']))) ?></dd>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($item['note'])): ?>
                <dt>Poznámka (Excel)</dt>
                <dd style="color:var(--text-muted)"><?= nl2br(htmlspecialchars($item['note'])) ?></dd>
            <?php endif; ?>

        </dl>
    </div>

    <div class="detail-card" style="display:flex;flex-direction:column;gap:1.5rem">

        <?php if ($item['status'] === 'available'): ?>
            <div>
                <h2>Požičať</h2>
                <?php if (!empty($item['personal'])): ?>
                    <button type="button" class="btn btn-success" disabled style="opacity:.4;cursor:not-allowed">
                        Požičať vybavenie
                    </button>
                <?php else: ?>
                    <form method="POST" action="<?= BASE_URL ?>/items/borrow">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-success">Požičať vybavenie</button>
                    </form>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/items/vyucba" style="margin-top:.75rem">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm">🎓 Označiť ako Výučba</button>
                </form>
            </div>

        <?php elseif ($item['status'] === 'borrowed' && $activeLoan): ?>
            <div>
                <h2>Momentálne požičané</h2>
                <dl class="detail-list">
                    <dt>Komu</dt><dd><?= htmlspecialchars($activeLoan['user_name']) ?></dd>
                    <dt>Od</dt>  <dd><?= htmlspecialchars($activeLoan['borrowed_at']) ?></dd>
                </dl>
                <?php if (AuthMiddleware::isAdmin() || Session::get('user_id') == $activeLoan['user_id']): ?>
                    <form method="POST" action="<?= BASE_URL ?>/items/return" style="margin-top:1rem">
                        <input type="hidden" name="loan_id" value="<?= $activeLoan['id'] ?>">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-outline">Vrátiť</button>
                    </form>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div>
                <h2>Výučba<?= !empty($item['vyucba_label']) ? ': ' . htmlspecialchars($item['vyucba_label']) : '' ?></h2>
                <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem">
                    Zariadenie je momentálne vo výučbe.
                </p>
                <form method="POST" action="<?= BASE_URL ?>/items/vyucba">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn btn-outline">🎓 Uvoľniť z výučby</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Pridať komentár -->
        <div>
            <h2>Pridať komentár</h2>
            <form method="POST" action="<?= BASE_URL ?>/items/comment">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <div class="form-group">
                    <textarea name="comment" rows="2" placeholder="Váš komentár..."></textarea>
                </div>
                <button type="submit" class="btn btn-outline">Pridať</button>
            </form>
        </div>

        <!-- Dočasné umiestnenie -->
        <div>
            <button type="button" class="btn btn-outline btn-sm" onclick="toggleSection('temp-location')">
                📦 Dočasné umiestnenie <?= $isTemp ? '🟡' : '' ?>
            </button>
            <div id="temp-location" style="display:none;margin-top:1rem">
                <form method="POST" action="<?= BASE_URL ?>/items/location">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <div class="form-group">
                        <label>Miestnosť</label>
                        <select name="temp_room">
                            <option value="">— rovnaké ako základné —</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= htmlspecialchars($room['new_code'] ?? $room['code']) ?>"
                                        <?= ($item['temp_room'] ?? '') === ($room['new_code'] ?? $room['code']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($room['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Skriňa</label>
                        <input type="text" name="temp_closet" value="<?= htmlspecialchars($item['temp_closet'] ?? '') ?>" placeholder="napr. 3D">
                    </div>
                    <div class="form-group">
                        <label>Polica</label>
                        <input type="text" name="temp_shelf" value="<?= htmlspecialchars($item['temp_shelf'] ?? '') ?>" placeholder="napr. 5">
                    </div>
                    <div style="display:flex;gap:.5rem">
                        <button type="submit" class="btn btn-outline">Uložiť</button>
                        <?php if ($isTemp): ?>
                            <button type="submit" name="clear_temp" value="1" class="btn btn-outline" style="color:#ef4444">Zrušiť dočasné</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Admin: zmena stavu -->
        <?php if (AuthMiddleware::isAdmin()): ?>
            <div>
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleSection('admin-status')">
                    ⚙️ Zmeniť stav
                </button>
                <div id="admin-status" style="display:none;margin-top:1rem">
                    <form method="POST" action="<?= BASE_URL ?>/items/status" style="display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <select name="status">
                            <option value="available"   <?= $item['status'] === 'available'   ? 'selected' : '' ?>>Dostupné</option>
                            <option value="borrowed"    <?= $item['status'] === 'borrowed'    ? 'selected' : '' ?>>Požičané</option>
                            <option value="maintenance" <?= $item['status'] === 'maintenance' ? 'selected' : '' ?>>Výučba</option>
                        </select>
                        <button type="submit" class="btn">Uložiť</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- História výpožičiek -->
<?php if (!empty($history)): ?>
    <div class="history-section">
        <div style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;background:var(--border);padding:.4rem .9rem;border-radius:7px" onclick="toggleSection('history-body')">
            <h2 style="margin:0;font-size:1rem">História výpožičiek</h2>
            <span style="font-size:.75rem">▼</span>
        </div>
        <div id="history-body" style="display:none;margin-top:1rem">
            <table class="items-table">
                <thead><tr><th>Kto</th><th>Prevzal</th><th>Vrátil</th><th>Poznámka</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['user_name']) ?></td>
                        <td><?= htmlspecialchars($h['borrowed_at']) ?></td>
                        <td><?= $h['returned_at'] ? htmlspecialchars($h['returned_at']) : '<span class="badge badge-borrowed">nevrátené</span>' ?></td>
                        <td><?= htmlspecialchars($h['notes'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Komentáre -->
<div class="history-section" style="margin-top:2rem">
    <div style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;background:var(--border);padding:.4rem .9rem;border-radius:7px" onclick="toggleSection('comments-body')">
        <h2 style="margin:0;font-size:1rem">Komentáre <?= !empty($comments) ? '<span style="font-size:.85rem;color:var(--text-muted)">(' . count($comments) . ')</span>' : '' ?></h2>
        <span style="font-size:.75rem">▼</span>
    </div>
    <div id="comments-body" style="display:none;margin-top:1rem">
        <?php if (empty($comments)): ?>
            <p style="color:var(--text-muted)">Zatiaľ žiadne komentáre.</p>
        <?php else: ?>
            <table class="items-table">
                <thead><tr><th>Kto</th><th>Kedy</th><th>Komentár</th></tr></thead>
                <tbody>
                <?php foreach ($comments as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['user_name']) ?></td>
                        <td style="white-space:nowrap;font-size:.85rem;color:var(--text-muted)"><?= htmlspecialchars($c['created_at']) ?></td>
                        <td><?= nl2br(htmlspecialchars($c['comment'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleSection(id) {
        const el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
</script>