<div class="page-header">
    <h1>Môj profil</h1>
</div>

<div class="detail-grid">

    <!-- Osobné údaje -->
    <div class="detail-card">
        <h2>Osobné údaje</h2>
        <form method="POST" action="<?= BASE_URL ?>/profile/update">
            <div class="form-group">
                <label>Meno</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Skratka <span style="color:var(--text-muted);font-size:.85rem">(používa sa na priradenie zariadení)</span></label>
                <input type="text" name="abbreviation" value="<?= htmlspecialchars($user['abbreviation'] ?? '') ?>" maxlength="10" placeholder="napr. Kv">
            </div>
            <div class="form-group">
                <label>Nové heslo <span style="color:var(--text-muted);font-size:.85rem">(nechajte prázdne ak nechcete meniť)</span></label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Potvrdiť heslo</label>
                <input type="password" name="password_confirm" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-success">Uložiť zmeny</button>
        </form>
    </div>

    <!-- Moje zariadenia -->
    <div class="detail-card">
        <h2>Moje zariadenia</h2>

        <?php if (empty($borrowedItems) && empty($personalItems)): ?>
            <p style="color:var(--text-muted)">Nemáte žiadne zariadenia.</p>
        <?php endif; ?>

        <?php if (!empty($borrowedItems)): ?>
            <h3 style="margin:1rem 0 .5rem;font-size:1rem">Aktuálne požičané</h3>
            <table class="items-table">
                <thead><tr><th>SAP č.</th><th>Názov</th><th>Miestnosť</th><th>Od</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($borrowedItems as $it): ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars($it['sap_num']) ?></td>
                        <td>
                            <span class="badge badge-borrowed" style="font-size:.9rem">
                                <?= htmlspecialchars($it['name']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(
                                (!empty($it['temp_room']) ? ($it['temp_room_name'] ?? $it['temp_room']) : ($it['room_name'] ?? $it['sap_position'] ?? '—'))
                            ) ?></td>
                        <td style="font-size:.85rem;color:var(--text-muted)"><?= htmlspecialchars($it['borrowed_at']) ?></td>
                        <td><a href="<?= BASE_URL ?>/items/show?id=<?= $it['id'] ?>" class="btn btn-sm">→</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($personalItems)): ?>
            <h3 style="margin:1.5rem 0 .5rem;font-size:1rem">Pridelené na osobnú kartu</h3>
            <table class="items-table">
                <thead><tr><th>SAP č.</th><th>Názov</th><th>Miestnosť</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($personalItems as $it): ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars($it['sap_num']) ?></td>
                        <td>
                            <span class="badge badge-personal" style="font-size:.9rem;display:inline-block">
                                <?= htmlspecialchars($it['name']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($it['room_name'] ?? $it['sap_position'] ?? '—') ?></td>
                        <td><a href="<?= BASE_URL ?>/items/show?id=<?= $it['id'] ?>" class="btn btn-sm">→</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>