<div class="page-header">
    <h1>Panel správcu</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-num"><?= $stats['total'] ?></div>
        <div class="stat-label">Celkový počet</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-num"><?= $stats['available'] ?></div>
        <div class="stat-label">Dostupné</div>
    </div>
    <div class="stat-card stat-yellow">
        <div class="stat-num"><?= $stats['borrowed'] ?></div>
        <div class="stat-label">Požičané</div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-num"><?= $stats['maintenance'] ?></div>
        <div class="stat-label">Vo výučbe </div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $stats['archived'] ?></div>
        <div class="stat-label">Archivované</div>
    </div>
</div>

<div class="admin-sections">

    <!-- Používatelia -->
    <div class="detail-card">
        <h2>Používatelia</h2>
        <table class="items-table">
            <thead><tr><th>Meno</th><th>Email</th><th>Skratka</th><th>Rola</th><th>Stav</th><th>Registrovaný</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr id="user-row-<?= $u['id'] ?>">
                    <td>
                        <span class="user-display"><?= htmlspecialchars($u['name']) ?></span>
                        <input class="user-edit" type="text" name="name" value="<?= htmlspecialchars($u['name']) ?>" style="display:none;width:120px">
                    </td>
                    <td>
                        <span class="user-display"><?= htmlspecialchars($u['email']) ?></span>
                        <input class="user-edit" type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" style="display:none;width:160px">
                    </td>
                    <td>
                        <span class="user-display mono"><?= htmlspecialchars($u['abbreviation'] ?? '—') ?></span>
                        <input class="user-edit" type="text" name="abbreviation" value="<?= htmlspecialchars($u['abbreviation'] ?? '') ?>" style="display:none;width:60px" maxlength="10">
                    </td>
                    <td>
                        <span class="user-display"><span class="badge badge-<?= $u['role'] === 'admin' ? 'available' : 'borrowed' ?>"><?= $u['role'] ?></span></span>
                        <select class="user-edit" name="role" style="display:none">
                            <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>user</option>
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                        </select>
                    </td>
                    <td>
                        <span class="user-display"><span class="badge badge-<?= $u['active'] ? 'available' : 'maintenance' ?>"><?= $u['active'] ? 'Aktívny' : 'Neaktívny' ?></span></span>
                        <label class="user-edit" style="display:none;align-items:center;gap:.4rem">
                            <input type="checkbox" name="active" value="1" <?= $u['active'] ? 'checked' : '' ?>> Aktívny
                        </label>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars($u['created_at']) ?></td>
                    <td style="white-space:nowrap">
                        <button type="button" class="btn btn-outline btn-sm user-edit-btn" onclick="toggleUserEdit(<?= $u['id'] ?>)">Upraviť</button>
                        <form class="user-edit" method="POST" action="<?= BASE_URL ?>/admin/users/edit" style="display:none;margin-top:.5rem">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="name"         class="user-val-name"         value="<?= htmlspecialchars($u['name']) ?>">
                            <input type="hidden" name="email"        class="user-val-email"        value="<?= htmlspecialchars($u['email']) ?>">
                            <input type="hidden" name="abbreviation" class="user-val-abbreviation" value="<?= htmlspecialchars($u['abbreviation'] ?? '') ?>">
                            <input type="hidden" name="role"         class="user-val-role"         value="<?= htmlspecialchars($u['role']) ?>">
                            <input type="hidden" name="active"       class="user-val-active"       value="<?= $u['active'] ? '1' : '' ?>">
                            <div class="form-group" style="margin-top:.5rem">
                                <label style="font-size:.82rem">Nové heslo <span style="color:var(--text-muted)">(Nechajte prázdne, ak heslo nemeníte)</span></label>
                                <input type="password" name="password" style="width:160px" placeholder="••••••••">
                            </div>
                            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                                <button type="submit" class="btn btn-success btn-sm">Uložiť</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="cancelUserEdit(<?= $u['id'] ?>)">Zrušiť</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:1.5rem">
            <button type="button" class="btn btn-outline btn-sm" onclick="toggleSection('add-user')">
                + Pridať používateľa
            </button>
            <div id="add-user" style="display:none;margin-top:1rem">
                <form method="POST" action="<?= BASE_URL ?>/admin/users/create">
                    <div style="display:flex;flex-wrap:wrap;gap:1rem">
                        <div class="form-group" style="flex:1;min-width:160px">
                            <label>Meno</label>
                            <input type="text" name="name" required placeholder="Ján Novák">
                        </div>
                        <div class="form-group" style="flex:1;min-width:160px">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="jan@example.com">
                        </div>
                        <div class="form-group" style="min-width:100px">
                            <label>Skratka <span style="color:var(--text-muted);font-size:.85rem">(sk m)</span></label>
                            <input type="text" name="abbreviation" placeholder="napr. Kv" maxlength="10">
                        </div>
                        <div class="form-group" style="flex:1;min-width:160px">
                            <label>Heslo</label>
                            <input type="password" name="password" required placeholder="••••••••">
                        </div>
                        <div class="form-group" style="min-width:120px">
                            <label>Rola</label>
                            <select name="role">
                                <option value="user">user</option>
                                <option value="admin">admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Vytvoriť</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Viditeľnosť triedení -->
    <div class="detail-card" style="margin-top:1.5rem">
        <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer" onclick="toggleSection('triedenie-body')">
            <h2 style="margin:0">Viditeľné triedy zariadení</h2>
            <span id="triedenie-arrow">▼</span>
        </div>
        <div id="triedenie-body" style="display:none;margin-top:1rem">
            <p style="color:var(--text-muted);margin-bottom:.75rem;font-size:.9rem">
                Zaškrtnuté triedenia sa zobrazujú používateľom v zozname vybavenia.
            </p>
            <div style="display:flex;gap:.5rem;margin-bottom:1rem">
                <button type="button" class="btn btn-outline btn-sm" onclick="setAllCheckboxes('triedenie-checks', true)">Označiť všetky</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="setAllCheckboxes('triedenie-checks', false)">Odznačiť všetky</button>
                <span id="triedenie-status" style="font-size:.8rem;color:var(--text-muted);align-self:center;margin-left:.5rem"></span>
            </div>
            <div id="triedenie-checks" style="display:flex;flex-direction:column;gap:.5rem">
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer">
                    <input type="checkbox" data-key="show_no_triedenie" data-type="setting"
                            <?= !empty($showNoTriedenie) ? 'checked' : '' ?>>
                    <span style="color:var(--text-muted);font-style:italic">(bez označenia)</span>
                </label>
                <?php foreach ($triedenie as $t): ?>
                    <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer">
                        <input type="checkbox" data-id="<?= $t['id'] ?>" data-type="triedenie"
                                <?= $t['visible'] ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($t['triedenie']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Rýchle akcie -->
    <div class="detail-card" style="margin-top:1.5rem">
        <h2>Rýchle akcie</h2>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem">
            <a href="<?= BASE_URL ?>/items" class="btn">📦 Všetko vybavenie</a>
            <a href="<?= BASE_URL ?>/admin/import" class="btn btn-success">📥 Import Excel</a>
        </div>
    </div>

    <!-- Správa miestností -->
    <div class="detail-card" style="margin-top:1.5rem">
        <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer" onclick="toggleSection('rooms-body')">
            <h2 style="margin:0">Miestnosti</h2>
            <span id="rooms-arrow">▼</span>
        </div>
        <div id="rooms-body" style="display:none;margin-top:1rem">

            <button type="button" class="btn btn-outline btn-sm" onclick="toggleSection('add-room')">
                + Pridať miestnosť
            </button>
            <div id="add-room" style="display:none;margin-top:1rem">
                <form method="POST" action="<?= BASE_URL ?>/admin/rooms/create">
                    <div style="display:flex;flex-wrap:wrap;gap:1rem">
                        <div class="form-group" style="min-width:120px">
                            <label>Nový kód <span style="color:var(--text-muted);font-size:.85rem">(povinné)</span></label>
                            <input type="text" name="new_code" placeholder="napr. 108" required>
                        </div>
                        <div class="form-group" style="flex:1;min-width:160px">
                            <label>Názov <span style="color:var(--text-muted);font-size:.85rem">(povinné)</span></label>
                            <input type="text" name="name" placeholder="napr. L8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Pridať</button>
                </form>
            </div>

            <table class="items-table" style="margin-bottom:1rem">
                <thead><tr><th>Starý kód</th><th>Nový kód</th><th>Názov</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr id="room-row-<?= $r['id'] ?>">
                        <td class="mono">
                            <span class="room-display"><?= htmlspecialchars($r['code'] ?? '—') ?></span>
                            <input class="room-edit" type="text" name="code" value="<?= htmlspecialchars($r['code'] ?? '') ?>" style="display:none;width:80px">
                        </td>
                        <td class="mono">
                            <span class="room-display"><?= htmlspecialchars($r['new_code'] ?? '—') ?></span>
                            <input class="room-edit" type="text" name="new_code" value="<?= htmlspecialchars($r['new_code'] ?? '') ?>" style="display:none;width:80px">
                        </td>
                        <td>
                            <span class="room-display"><?= htmlspecialchars($r['name']) ?></span>
                            <input class="room-edit" type="text" name="name" value="<?= htmlspecialchars($r['name']) ?>" style="display:none;width:120px">
                        </td>
                        <td style="white-space:nowrap">
                            <button type="button" class="btn btn-outline btn-sm room-edit-btn" onclick="toggleRoomEdit(<?= $r['id'] ?>)">Upraviť</button>
                            <form class="room-edit" method="POST" action="<?= BASE_URL ?>/admin/rooms/update" style="display:none;margin-top:.5rem">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="code"     class="room-edit-val-code"     value="<?= htmlspecialchars($r['code'] ?? '') ?>">
                                <input type="hidden" name="new_code" class="room-edit-val-new_code" value="<?= htmlspecialchars($r['new_code'] ?? '') ?>">
                                <input type="hidden" name="name"     class="room-edit-val-name"     value="<?= htmlspecialchars($r['name']) ?>">
                                <button type="submit" class="btn btn-success btn-sm">Uložiť</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="cancelRoomEdit(<?= $r['id'] ?>)">Zrušiť</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    function toggleSection(id) {
        const el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }

    function setAllCheckboxes(containerId, checked) {
        document.querySelectorAll('#' + containerId + ' input[type=checkbox]').forEach(cb => cb.checked = checked);
    }

    function toggleRoomEdit(id) {
        const row = document.getElementById('room-row-' + id);
        row.querySelectorAll('.room-display').forEach(el => el.style.display = 'none');
        row.querySelectorAll('.room-edit').forEach(el => el.style.display = '');
        row.querySelector('.room-edit-btn').style.display = 'none';

        // Sync input values to hidden fields on change
        row.querySelectorAll('input.room-edit[name]').forEach(input => {
            input.addEventListener('input', () => {
                const hidden = row.querySelector('.room-edit-val-' + input.name);
                if (hidden) hidden.value = input.value;
            });
        });
    }

    function cancelRoomEdit(id) {
        const row = document.getElementById('room-row-' + id);
        row.querySelectorAll('.room-display').forEach(el => el.style.display = '');
        row.querySelectorAll('.room-edit').forEach(el => el.style.display = 'none');
        row.querySelector('.room-edit-btn').style.display = '';
    }

    // Auto-save triedenie checkboxes
    document.addEventListener('change', function(e) {
        const cb = e.target;
        if (!cb.dataset.type) return;

        const status = document.getElementById('triedenie-status');
        status.textContent = 'Ukladám...';

        const body = new FormData();
        body.append('type',    cb.dataset.type);
        body.append('checked', cb.checked ? '1' : '0');

        if (cb.dataset.type === 'triedenie') {
            body.append('id', cb.dataset.id);
        } else {
            body.append('key', cb.dataset.key);
        }

        fetch('<?= BASE_URL ?>/admin/triedenie-toggle', {
            method: 'POST',
            body: body
        }).then(r => r.json()).then(data => {
            status.textContent = data.ok ? '✓ Uložené' : '✗ Chyba';
            setTimeout(() => status.textContent = '', 2000);
        });
    });

    function setAllCheckboxes(containerId, checked) {
        document.querySelectorAll('#' + containerId + ' input[type=checkbox]').forEach(cb => {
            if (cb.checked !== checked) {
                cb.checked = checked;
                cb.dispatchEvent(new Event('change'));
            }
        });
    }

    function toggleUserEdit(id) {
        const row = document.getElementById('user-row-' + id);
        row.querySelectorAll('.user-display').forEach(el => el.style.display = 'none');
        row.querySelectorAll('.user-edit').forEach(el => el.style.display = '');
        row.querySelector('.user-edit-btn').style.display = 'none';

        row.querySelectorAll('input.user-edit[name], select.user-edit[name]').forEach(input => {
            input.addEventListener('change', () => {
                const hidden = row.querySelector('.user-val-' + input.name);
                if (hidden) hidden.value = input.type === 'checkbox' ? (input.checked ? '1' : '') : input.value;
            });
            input.addEventListener('input', () => {
                const hidden = row.querySelector('.user-val-' + input.name);
                if (hidden) hidden.value = input.value;
            });
        });
    }

    function cancelUserEdit(id) {
        const row = document.getElementById('user-row-' + id);
        row.querySelectorAll('.user-display').forEach(el => el.style.display = '');
        row.querySelectorAll('.user-edit').forEach(el => el.style.display = 'none');
        row.querySelector('.user-edit-btn').style.display = '';
    }
</script>