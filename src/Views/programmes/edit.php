<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-folder2-open"></i> Edit Programme</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/programmes/<?= $programme['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="mb-3">
                        <label class="form-label">Programme Name *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= htmlspecialchars($programme['name']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Prefix *</label>
                        <input type="text" name="prefix" class="form-control" required
                               value="<?= htmlspecialchars($programme['prefix']) ?>"
                               maxlength="20"
                               style="text-transform: uppercase; font-family: monospace; font-size: 1.1rem;"
                               id="prefixInput">
                        <div class="form-text">
                            Changing the prefix won't affect existing invoice numbers.
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded">
                        <small class="text-muted">Preview:</small><br>
                        <span class="fw-bold fs-5">
                            <span id="prefixPreview"><?= htmlspecialchars($programme['prefix']) ?></span>-<?= date('y') ?>-001
                        </span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($programme['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Update Programme
                        </button>
                        <a href="/programmes" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('prefixInput').addEventListener('input', function() {
    const val = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    document.getElementById('prefixPreview').textContent = val || 'PREFIX';
});
</script>
