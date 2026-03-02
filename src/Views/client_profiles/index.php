<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Client Profiles</h2>
    <a href="/client-profiles/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Client
    </a>
</div>

<?php if (empty($profiles)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No client profiles yet. Add a client to create invoices for them.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['company_name']) ?></strong></td>
                        <td><?= htmlspecialchars($p['contact_person'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/client-profiles/<?= $p['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/client-profiles/<?= $p['id'] ?>/delete"
                                      onsubmit="return confirm('Delete this client?')">
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
