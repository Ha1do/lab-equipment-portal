<div class="auth-wrap">
    <div class="auth-card">
        <h1>Prihlásenie</h1>
        <p class="auth-sub">Inventarizačný portál vybavenia</p>
        <form method="POST" action="<?= BASE_URL ?>/login">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="you@university.edu">
            </div>
            <div class="form-group">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-full">Prihlásiť sa</button>
        </form>
    </div>
</div>