<?php $typeLabel = $invoice['document_type'] === 'quotation' ? 'Quotation' : 'Invoice'; ?>
<?php $typeLabel = $docType === 'quotation' ? 'Quotation' : 'Invoice'; ?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-pencil"></i> Edit <?= $typeLabel ?> #<?= htmlspecialchars($invoice['document_number']) ?></h4>
                <span class="badge bg-<?= $invoice['document_type'] === 'quotation' ? 'info' : 'primary' ?> fs-6"><?= $typeLabel ?></span>
            </div>
            <div class="card-body">
                <form method="POST" action="/invoices/<?= $invoice['id'] ?>" id="invoiceForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <style>
                        /* Prevent Quill editors from overlapping form content */
                        .quill-editor, .ql-container { 
                            z-index: 0; 
                            margin-bottom: 1rem;
                        }
                        .ql-container,
                        .ql-toolbar {
                            position: relative;
                        }
                        .form-actions-section {
                            position: relative;
                            z-index: 2;
                            clear: both;
                            padding-top: 1rem;
                        }
                        .notes-editor .ql-container {
                            min-height: 5rem;
                        }
                        .notes-editor .ql-editor {
                            min-height: 5rem;
                        }
                    </style>

                    <!-- Programme, Session and Document Number (single row) -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="bi bi-collection"></i> Programme *</label>
                            <select name="programme_id" id="programmeSelect" class="form-select" required>
                                <option value="">-- Select Programme --</option>
                                <?php foreach ($programmes as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-prefix="<?= htmlspecialchars($p['prefix']) ?>"
                                        <?= ($p['id'] == ($invoice['programme_id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['prefix']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Determines the invoice number prefix</div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Session Number</label>
                            <input type="text" name="session_number" class="form-control" value="<?= htmlspecialchars($invoice['session_number'] ?? '') ?>" placeholder="e.g. 01">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><?= $typeLabel ?> Number *</label>
                            <div class="input-group">
                                <input type="text" name="document_number" id="docNumber" class="form-control" required
                                       value="<?= htmlspecialchars($invoice['document_number']) ?>">
                                <button type="button" class="btn btn-outline-secondary" id="refreshNumber" title="Refresh number">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="form-text" id="numberHint">Select a programme first</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Issue Date *</label>
                            <input type="date" name="issue_date" class="form-control" required
                                   value="<?= htmlspecialchars($invoice['issue_date']) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control"
                                   value="<?= htmlspecialchars($invoice['due_date'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Company & Client -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">From (Your Company) *</label>
                            <select name="company_profile_id" class="form-select" required>
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= $c['id'] == $invoice['company_profile_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $typeLabel ?> To (Client) *</label>
                            <select name="client_profile_id" class="form-select" required>
                                <?php foreach ($clients as $cl): ?>
                                    <option value="<?= $cl['id'] ?>"
                                        <?= $cl['id'] == $invoice['client_profile_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cl['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Context Fields -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">For (Project / Service Title)</label>
                            <input type="text" name="project_title" class="form-control"
                                   value="<?= htmlspecialchars($invoice['project_title'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?= htmlspecialchars($invoice['location'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Requested By</label>
                            <input type="text" name="requested_by" class="form-control"
                                   value="<?= htmlspecialchars($invoice['requested_by'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client Email</label>
                            <input type="email" name="client_email" class="form-control"
                                   value="<?= htmlspecialchars($invoice['client_email'] ?? '') ?>">
                        </div>
                    </div>

                    <hr>

                    <!-- Line Items -->
                    <h5 class="mb-3"><i class="bi bi-list-ol"></i> Description of Services</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Description</th>
                                    <th width="120">Quantity</th>
                                    <th width="160">Unit Price</th>
                                    <th width="160">Amount</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <?php if (!empty($invoice['items'])): ?>
                                    <?php foreach ($invoice['items'] as $idx => $item): ?>
                                        <tr class="item-row">
                                            <td class="item-number text-center align-middle"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="quill-editor" style="min-height:100px;"><?=
                                                    htmlspecialchars($item['description'])
                                                ?></div>
                                                <textarea name="item_description[]" class="form-control quill-value d-none"><?= htmlspecialchars($item['description']) ?></textarea>
                                            </td>
                                            <td>
                                                <input type="number" name="item_quantity[]" class="form-control item-qty"
                                                       value="<?= $item['quantity'] ?>" min="0" step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" name="item_unit_price[]" class="form-control item-price"
                                                       value="<?= $item['unit_price'] ?>" min="0" step="0.01">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-amount" readonly
                                                       value="<?= number_format((float)$item['amount'], 2) ?>">
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="item-row">
                                        <td class="item-number text-center align-middle">1</td>
                                        <td>
                                            <div class="quill-editor" style="min-height:60px;"></div>
                                            <textarea name="item_description[]" class="form-control quill-value d-none"></textarea>
                                        </td>
                                        <td><input type="number" name="item_quantity[]" class="form-control item-qty" value="1" min="0" step="0.01"></td>
                                        <td><input type="number" name="item_unit_price[]" class="form-control item-price" value="0.00" min="0" step="0.01"></td>
                                        <td><input type="text" class="form-control item-amount" readonly value="0.00"></td>
                                        <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x-lg"></i></button></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addItem">
                        <i class="bi bi-plus-lg"></i> Add Item
                    </button>

                    <!-- Totals & Tax -->
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Currency</label>
                                            <input type="text" name="currency_code" class="form-control"
                                                   value="<?= htmlspecialchars($invoice['currency_code'] ?? 'LKR') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tax Label</label>
                                            <input type="text" name="tax_label" class="form-control"
                                                   value="<?= htmlspecialchars($invoice['tax_label'] ?? 'VAT') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tax Rate %</label>
                                            <input type="number" name="tax_rate" class="form-control" id="taxRate"
                                                   value="<?= $invoice['tax_rate'] ?? 0 ?>" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Subtotal:</span>
                                        <strong id="subtotalDisplay"><?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><span id="taxLabelDisplay"><?= htmlspecialchars($invoice['tax_label'] ?? 'VAT') ?></span>:</span>
                                        <strong id="taxDisplay"><?= number_format((float)($invoice['tax_amount'] ?? 0), 2) ?></strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span class="fs-5">Total:</span>
                                        <strong class="fs-5" id="totalDisplay"><?= number_format((float)($invoice['total'] ?? 0), 2) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Background Template -->
                    <?php $bgTemplate = $invoice['background_template'] ?? 'none'; ?>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-image"></i> Background Template</label>
                        <div class="row g-3">
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="none" class="d-none" <?= $bgTemplate === 'none' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'none' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;background:#fff;<?= $bgTemplate === 'none' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-file-earmark fs-1"></i><br>
                                            <small>Blank White</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                                                        <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="basic" class="d-none" <?= $bgTemplate === 'basic' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'basic' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;background:#fff;<?= $bgTemplate === 'basic' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-file-earmark fs-1"></i><br>
                                            <small>Basics</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-cream-minimalist" class="d-none" <?= $bgTemplate === 'blue-cream-minimalist' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'blue-cream-minimalist' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;overflow:hidden;cursor:pointer;<?= $bgTemplate === 'blue-cream-minimalist' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <img src="/img/templates/blue-cream-minimalist.png" alt="Blue &amp; Cream" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue &amp; Cream</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="modern-elegant" class="d-none" <?= $bgTemplate === 'modern-elegant' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'modern-elegant' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;overflow:hidden;cursor:pointer;<?= $bgTemplate === 'modern-elegant' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <img src="/img/templates/modern-elegant.jpg" alt="Modern Elegant" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Modern Elegant</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-white-minimalist" class="d-none" <?= $bgTemplate === 'blue-white-minimalist' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'blue-white-minimalist' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;overflow:hidden;cursor:pointer;<?= $bgTemplate === 'blue-white-minimalist' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <img src="/img/templates/blue-white-minimalist.png" alt="Blue &amp; White Minimalist" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue &amp; White Minimalist</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-modern-medical" class="d-none" <?= $bgTemplate === 'blue-modern-medical' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'blue-modern-medical' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;overflow:hidden;cursor:pointer;<?= $bgTemplate === 'blue-modern-medical' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <img src="/img/templates/blue-modern-medical.png" alt="Blue Modern Medical" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue Modern Medical</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-white-geometric" class="d-none" <?= $bgTemplate === 'blue-white-geometric' ? 'checked' : '' ?>>
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid <?= $bgTemplate === 'blue-white-geometric' ? '#0d9488' : '#dee2e6' ?>;border-radius:8px;overflow:hidden;cursor:pointer;<?= $bgTemplate === 'blue-white-geometric' ? 'box-shadow:0 0 0 3px rgba(13,148,136,0.25)' : '' ?>">
                                        <img src="/img/templates/blue-white-geometric.png" alt="Blue White Geometric" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue White Geometric</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Notes & Status -->
                    <section class="row mb-3" style="height: auto;">
                        <div class="col-md-8">
                            <label class="form-label"><i class="bi bi-sticky"></i> Notes</label>
                            <div class="quill-editor notes-editor" style=""><?= htmlspecialchars($invoice['notes'] ?? '') ?></div>
                            <textarea name="notes" class="form-control quill-value d-none"><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
                            <div class="form-text">
                                Use placeholders: <code>{{programme}}</code>, <code>{{Session_no}}</code>, <code>{{invoice_no}}</code>, <code>{{issue_date}}</code>, <code>{{due_date}}</code>, <code>{{from_company}}</code>, <code>{{client}}</code>, <code>{{total_amount}}</code>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= ($invoice['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="sent" <?= ($invoice['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                                <?php if($docType === 'quotation'): ?>
                                <option value="accepted" <?= ($invoice['status'] ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                <option value="rejected" <?= ($invoice['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <?php else: ?>
                                <option value="overdue" <?= ($invoice['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                <option value="unpaid" <?= ($invoice['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="partially_paid" <?= ($invoice['status'] ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                                <?php endif; ?>
                                <option value="paid" <?= ($invoice['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="cancelled" <?= ($invoice['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="void" <?= ($invoice['status'] ?? '') === 'void' ? 'selected' : '' ?>>Void</option>

                            </select>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="is_paid" id="isPaid"
                                    <?= ($invoice['is_paid'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-success" for="isPaid">
                                    <i class="bi bi-check-circle"></i> Mark as PAID
                                </label>
                            </div>
                            <div class="row mt-2">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-lg"></i> Update <?= $typeLabel ?>
                                    </button>
                                    <a href="/invoices/<?= $invoice['id'] ?>" class="btn btn-secondary btn-lg">Cancel</a>
                                </div>
                            </div>
                        </div>
                        </div>
                    </section>

                        

                    
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // load Quill assets
    const qlink = document.createElement('link');
    qlink.href = 'https://cdn.quilljs.com/1.3.6/quill.snow.css';
    qlink.rel = 'stylesheet';
    document.head.appendChild(qlink);
    const qscript = document.createElement('script');
    qscript.src = 'https://cdn.quilljs.com/1.3.6/quill.min.js';
    document.head.appendChild(qscript);

    // initialize editors after quill loads
    function initQuill(div) {
        if (div.__quill) return; // already initialized
        const ta = div.nextElementSibling;
        const quill = new Quill(div, {
            theme: 'snow',
            modules: { toolbar: [
                ['bold','italic','underline','strike'],
                [{ 'header': [1,2,3,false] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ] }
        });
        div.__quill = quill;
        if (ta && ta.value) quill.root.innerHTML = ta.value;
        quill.on('text-change', () => { if (ta) ta.value = quill.root.innerHTML; });
    }
    function initQuills() {
        document.querySelectorAll('.quill-editor').forEach(initQuill);
    }
    qscript.onload = initQuills;

    const itemsBody = document.getElementById('itemsBody');
    const addBtn = document.getElementById('addItem');
    const taxRateInput = document.getElementById('taxRate');
    const taxLabelInput = document.querySelector('[name="tax_label"]');

    function recalculate() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach((row, idx) => {
            row.querySelector('.item-number').textContent = idx + 1;
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const amount = qty * price;
            row.querySelector('.item-amount').value = amount.toFixed(2);
            subtotal += amount;
        });
        const taxRate = parseFloat(taxRateInput.value) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        const total = subtotal + taxAmount;
        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
        document.getElementById('taxDisplay').textContent = taxAmount.toFixed(2);
        document.getElementById('totalDisplay').textContent = total.toFixed(2);
        document.getElementById('taxLabelDisplay').textContent = taxLabelInput.value || 'VAT';
    }

    function addItemRow() {
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.innerHTML = `
            <td class="item-number text-center align-middle">0</td>
            <td>
                <div class="quill-editor" style="min-height:60px;"></div>
                <textarea name="item_description[]" class="form-control quill-value d-none"></textarea>
            </td>
            <td><input type="number" name="item_quantity[]" class="form-control item-qty" value="1" min="0" step="0.01"></td>
            <td><input type="number" name="item_unit_price[]" class="form-control item-price" value="0.00" min="0" step="0.01"></td>
            <td><input type="text" class="form-control item-amount" readonly value="0.00"></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x-lg"></i></button></td>
        `;
        itemsBody.appendChild(row);
        // init quill on new editor only
        initQuill(row.querySelector('.quill-editor'));
        recalculate();
    }

    addBtn.addEventListener('click', addItemRow);
    itemsBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) recalculate();
    });


    taxRateInput.addEventListener('input', recalculate);
    taxLabelInput.addEventListener('input', recalculate);
    itemsBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const rows = itemsBody.querySelectorAll('.item-row');
            if (rows.length > 1) { e.target.closest('.item-row').remove(); recalculate(); }
        }
    });
    recalculate();

    // ── Background template selection ───────────────────
    document.querySelectorAll('.bg-template-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.bg-template-card').forEach(c => {
                c.style.borderColor = '#dee2e6';
                c.style.boxShadow = 'none';
            });
            const card = this.closest('.bg-template-option').querySelector('.bg-template-card');
            card.style.borderColor = '#0d9488';
            card.style.boxShadow = '0 0 0 3px rgba(13,148,136,0.25)';
        });
    });
});
</script>
