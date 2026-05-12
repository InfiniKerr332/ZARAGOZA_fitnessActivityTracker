<?php
$isAppPage = strpos($_SERVER['REQUEST_URI'], '/user/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
?>

<?php if ($isAppPage): ?>
    </div><!-- .content-area -->
</main><!-- .main-content -->
<?php else: ?>
<!-- Public Footer -->
<footer class="public-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="footer-logo" style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:50%; overflow:hidden; flex-shrink:0;">
                    <img src="<?= BASE_URL ?>/assets/image/logo21.png" alt="FitTrack Pro" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <span><?= APP_NAME ?></span>
            </div>
            <p>Your premium fitness tracking companion.</p>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. AdvancedDB Finals Project.</p>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Global Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h2 id="confirmTitle">Are you sure?</h2>
            <button class="modal-close" onclick="closeConfirm()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="text-align:center;padding:24px 30px">
            <div id="confirmIcon" style="width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px"></div>
            <p id="confirmMessage" style="color:var(--text-secondary);font-size:15px;margin-bottom:0"></p>
        </div>
        <div style="display:flex;gap:12px;padding:0 30px 24px">
            <button class="btn btn-ghost" style="flex:1" onclick="closeConfirm()">Cancel</button>
            <button class="btn" id="confirmAction" style="flex:1">Confirm</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
    // Initialize Lucide icons with guard against infinite loops
    let _lucideRunning = false;
    function initLucideIcons() {
        if (_lucideRunning) return;
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            try {
                _lucideRunning = true;
                lucide.createIcons();
            } catch(e) {
                console.warn('Lucide icon init error:', e);
            } finally {
                _lucideRunning = false;
            }
        }
    }
    initLucideIcons();
    // Re-init on DOMContentLoaded in case script ran before DOM was ready
    document.addEventListener('DOMContentLoaded', initLucideIcons);
    // Auto-init icons added dynamically (modals, AJAX, etc.) with debounce
    let _lucideTimer = null;
    const _lucideObserver = new MutationObserver(function(mutations) {
        if (_lucideRunning) return; // Skip mutations caused by createIcons itself
        let needsRefresh = false;
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType === 1) {
                    // Only trigger if we find unprocessed data-lucide elements (not SVGs)
                    if (node.tagName !== 'svg' && node.hasAttribute && node.hasAttribute('data-lucide')) {
                        needsRefresh = true; break;
                    }
                    if (node.tagName !== 'svg' && node.querySelector && node.querySelector('[data-lucide]:not(svg)')) {
                        needsRefresh = true; break;
                    }
                }
            }
            if (needsRefresh) break;
        }
        if (needsRefresh) {
            clearTimeout(_lucideTimer);
            _lucideTimer = setTimeout(initLucideIcons, 50);
        }
    });
    _lucideObserver.observe(document.body || document.documentElement, { childList: true, subtree: true });

    /* ── Global Confirmation Modal ── */
    let confirmCallback = null;
    function showConfirm(title, message, btnText, btnClass, callback) {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        const btn = document.getElementById('confirmAction');
        btn.textContent = btnText;
        btn.className = 'btn ' + (btnClass || 'btn-primary');
        btn.style.flex = '1';
        const icon = document.getElementById('confirmIcon');
        if (btnClass === 'btn-danger') {
            icon.style.background = 'rgba(239,68,68,0.15)';
            icon.innerHTML = '<i data-lucide="alert-triangle" style="color:#ef4444;width:28px;height:28px"></i>';
        } else if (btnClass === 'btn-warning') {
            icon.style.background = 'rgba(245,158,11,0.15)';
            icon.innerHTML = '<i data-lucide="log-out" style="color:#f59e0b;width:28px;height:28px"></i>';
        } else {
            icon.style.background = 'rgba(69,93,211,0.15)';
            icon.innerHTML = '<i data-lucide="check-circle" style="color:#455DD3;width:28px;height:28px"></i>';
        }
        confirmCallback = callback;
        document.getElementById('confirmModal').classList.add('show');
        document.body.style.overflow = 'hidden';
        initLucideIcons();
    }
    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('show');
        document.body.style.overflow = '';
        confirmCallback = null;
    }
    document.getElementById('confirmAction').addEventListener('click', () => {
        if (confirmCallback) confirmCallback();
        closeConfirm();
    });

    /* ── Intercept Logout Links ── */
    document.querySelectorAll('a[href*="logout.php"]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const href = link.href;
            showConfirm('Log Out', 'Are you sure you want to log out of your account?', 'Log Out', 'btn-warning', () => {
                window.location.href = href;
            });
        });
    });

    /* ── Intercept Delete Forms ── */
    document.querySelectorAll('form').forEach(form => {
        const actionInput = form.querySelector('input[name="action"][value="delete"]');
        if (actionInput) {
            form.onsubmit = null; // Remove old inline handler
            form.removeAttribute('onsubmit');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                showConfirm('Delete Item', 'This action cannot be undone. Are you sure you want to delete this item?', 'Delete', 'btn-danger', () => {
                    this.submit();
                });
            });
        }
    });
</script>
</body>
</html>
