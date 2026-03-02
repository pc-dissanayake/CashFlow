<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-receipt"></i> Invoices & Quotations</h2>
        <?php
        // helper to build query string preserving filters
        function buildQuery(array $params): string {
            return http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''), '', '&amp;');
        }
        $commonFilters = [
            'programme_id' => $programme_id ?? null,
            'start_date' => $start_date ?? null,
            'end_date' => $end_date ?? null,
        ];
        ?>
        <div class="btn-group mt-2" role="group">
            <a href="/invoices?<?= buildQuery(array_merge($commonFilters, ['type' => null])) ?>" class="btn btn-sm <?= !$type ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
            <a href="/invoices?<?= buildQuery(array_merge($commonFilters, ['type' => 'invoice'])) ?>" class="btn btn-sm <?= $type === 'invoice' ? 'btn-dark' : 'btn-outline-dark' ?>">Invoices</a>
            <a href="/invoices?<?= buildQuery(array_merge($commonFilters, ['type' => 'quotation'])) ?>" class="btn btn-sm <?= $type === 'quotation' ? 'btn-dark' : 'btn-outline-dark' ?>">Quotations</a>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="/invoices/create?type=invoice" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Invoice
        </a>
        <a href="/invoices/create?type=quotation" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> New Quotation
        </a>
    </div>
</div>


<!-- filters -->
<form method="GET" class="row gy-2 gx-3 align-items-end mb-4">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type ?? '') ?>">
    <div class="col-auto">
        <label for="programmeFilter" class="form-label">Filter by Programme</label>
        <select id="programmeFilter" name="programme_id" class="form-select">
            <option value="">All</option>
            <?php foreach ($programmes as $p): ?>
                <option value="<?= $p['id'] ?>" <?= isset($programme_id) && $programme_id == $p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label for="startDate" class="form-label">From</label>
        <input type="date" id="startDate" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="endDate" class="form-label">To</label>
        <input type="date" id="endDate" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date ?? '') ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="/invoices?<?= $type ? 'type=' . urlencode($type) : '' ?>" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<?php if (empty($invoices)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No documents yet. Create your first invoice or quotation.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Number</th>
                    <th>Programme</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td>
                            <?php if ($inv['document_type'] === 'invoice'): ?>
                                <span class="badge bg-primary">Invoice</span>
                            <?php else: ?>
                                <span class="badge bg-info">Quotation</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/invoices/<?= $inv['id'] ?>" class="fw-bold text-decoration-none">
                                <?= htmlspecialchars($inv['document_number']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($inv['programme_name'])): ?>
                                <span class="badge bg-dark"><?= htmlspecialchars($inv['programme_prefix'] ?? '') ?></span>
                                <small><?= htmlspecialchars($inv['programme_name']) ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($inv['client_name']) ?></td>
                        <td><?= date('d M Y', strtotime($inv['issue_date'])) ?></td>
                        <td class="text-end fw-bold">
                            <?= htmlspecialchars($inv['currency_code']) ?>
                            <?= number_format((float)$inv['total'], 2) ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'draft' => 'secondary',
                                'sent' => 'warning',
                                'paid' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $color = $statusColors[$inv['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>">
                                <?= ucfirst($inv['status']) ?>
                            </span>
                            <?php if ($inv['is_paid']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> PAID</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                
                                <a href="/invoices/<?= $inv['id'] ?>/edit" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/invoices/<?= $inv['id'] ?>/duplicate" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Duplicate">
                                            <i class="bi bi-files"></i>
                                        </button>
                                </form>
                                <a href="/invoices/<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
