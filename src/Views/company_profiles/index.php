<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> Company Profiles</h2>
    <a href="/company-profiles/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Company
    </a>
</div>

<?php if (empty($profiles)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No company profiles yet. Create one to start generating invoices.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($profiles as $p): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <?php if ($p['logo_path']): ?>
                                <img src="<?= htmlspecialchars($p['logo_path']) ?>" alt="Logo" class="me-3" style="max-height:60px;">
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1">
                                    <?= htmlspecialchars($p['name']) ?>
                                    <?php if ($p['is_default']): ?>
                                        <span class="badge bg-success ms-2">Default</span>
                                    <?php endif; ?>
                                </h5>
                                <?php if ($p['tagline']): ?>
                                    <small class="text-muted"><?= htmlspecialchars($p['tagline']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr>
                        <div class="small">
                            <?php if ($p['address']): ?>
                                <div><i class="bi bi-geo-alt"></i> <?= nl2br(htmlspecialchars($p['address'])) ?></div>
                            <?php endif; ?>
                            <?php if ($p['phone']): ?>
                                <div><i class="bi bi-telephone"></i> <?= htmlspecialchars($p['phone']) ?></div>
                            <?php endif; ?>
                            <?php if ($p['email']): ?>
                                <div><i class="bi bi-envelope"></i> <?= htmlspecialchars($p['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="/company-profiles/<?= $p['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form method="POST" action="/company-profiles/<?= $p['id'] ?>/delete"
                              onsubmit="return confirm('Delete this company profile?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
