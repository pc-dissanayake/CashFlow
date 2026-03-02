<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-folder2-open"></i> Programmes</h2>
    <a href="/programmes/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Programme
    </a>
</div>

<p class="text-muted mb-3">
    Programmes define the invoice/quotation number prefix. Each programme generates its own sequence: <code>PREFIX-YY-001</code>, <code>PREFIX-YY-002</code>, etc.
</p>

<?php if (empty($programmes)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No programmes yet. Create a programme to start auto-numbering invoices.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Programme Name</th>
                    <th>Prefix</th>
                    <th>Number Format</th>
                    <th>Description</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programmes as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><code class="fs-6"><?= htmlspecialchars($p['prefix']) ?></code></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= htmlspecialchars($p['prefix']) ?>-<?= date('y') ?>-001
                            </span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($p['description'] ?? '-') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/programmes/<?= $p['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/programmes/<?= $p['id'] ?>/delete"
                                      onsubmit="return confirm('Delete this programme? Existing invoices will keep their numbers.')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
