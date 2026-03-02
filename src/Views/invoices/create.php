<?php $typeLabel = $docType === 'quotation' ? 'Quotation' : 'Invoice'; ?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-receipt"></i> New <?= $typeLabel ?></h4>
                <span class="badge bg-<?= $docType === 'quotation' ? 'info' : 'primary' ?> fs-6"><?= $typeLabel ?></span>
            </div>
            <div class="card-body">
                <form method="POST" action="/invoices" id="invoiceForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="document_type" value="<?= htmlspecialchars($docType) ?>">
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
                            min-height: 160px;
                        }
                        .notes-editor .ql-editor {
                            min-height: 110px;
                        }
                    </style>

                    <!-- Programme & Document Header -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="bi bi-collection"></i> Programme *</label>
                            <select name="programme_id" id="programmeSelect" class="form-select" required>
                                <option value="">-- Select Programme --</option>
                                <?php foreach ($programmes as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-prefix="<?= htmlspecialchars($p['prefix']) ?>">
                                        <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['prefix']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Determines the invoice number prefix</div>
                            <div class="mt-2">
                                <label class="form-label">Session Number</label>
                                <input type="text" name="session_number" class="form-control" placeholder="e.g. 01">
                            </div>
                            <label class="form-label"><?= $typeLabel ?> Number *</label>
                            <div class="input-group">
                                <input type="text" name="document_number" id="docNumber" class="form-control" required
                                       value="<?= htmlspecialchars($nextNumber) ?>" readonly>
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
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>

                    <!-- Company & Client -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">From (Your Company) *</label>
                            <select name="company_profile_id" class="form-select" required>
                                <option value="">-- Select Company --</option>
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['is_default'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                        <?= $c['is_default'] ? '(default)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <?= $docType === 'quotation' ? 'Quotation' : 'Invoice' ?> To (Client) *
                            </label>
                            <div class="input-group">
                                <select name="client_profile_id" class="form-select" required>
                                    <option value="">-- Select Client --</option>
                                    <?php foreach ($clients as $cl): ?>
                                        <option value="<?= $cl['id'] ?>">
                                            <?= htmlspecialchars($cl['company_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="/client-profiles/create" class="btn btn-outline-secondary" title="Add new client">
                                    <i class="bi bi-plus-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Context Fields -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">For (Project / Service Title)</label>
                            <input type="text" name="project_title" class="form-control"
                                   placeholder="e.g. Corporate Medical Clinic - Employee Health Screening">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                   placeholder="e.g. UTE Premises (UTE Wattala Branch)">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Requested By</label>
                            <input type="text" name="requested_by" class="form-control"
                                   placeholder="e.g. Safety Officer / HR Department">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client Email</label>
                            <input type="email" name="client_email" class="form-control"
                                   placeholder="e.g. info@client.lk">
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
                                <tr class="item-row">
                                    <td class="item-number text-center align-middle">1</td>
                                    <td>
                                        <div class="quill-editor" style="min-height:60px;"></div>
                                        <textarea name="item_description[]" class="form-control quill-value d-none" rows="2"
                                                  placeholder="Service description..."></textarea>
                                    </td>
                                    <td>
                                        <input type="number" name="item_quantity[]" class="form-control item-qty"
                                               value="1" min="0" step="0.01">
                                    </td>
                                    <td>
                                        <input type="number" name="item_unit_price[]" class="form-control item-price"
                                               value="0.00" min="0" step="0.01">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-amount" readonly value="0.00">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
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
                                            <input type="text" name="currency_code" class="form-control" value="LKR">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tax Label</label>
                                            <input type="text" name="tax_label" class="form-control" value="VAT">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tax Rate %</label>
                                            <input type="number" name="tax_rate" class="form-control" id="taxRate"
                                                   value="0" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Subtotal:</span>
                                        <strong id="subtotalDisplay">0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><span id="taxLabelDisplay">VAT</span>:</span>
                                        <strong id="taxDisplay">0.00</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span class="fs-5">Total:</span>
                                        <strong class="fs-5" id="totalDisplay">0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Background Template -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-image"></i> Background Template</label>
                        <div class="row g-3">
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="none" class="d-none" checked>
                                    <div class="bg-template-card active" style="width:120px;height:170px;border:2px solid #dee2e6;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;background:#fff;">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-file-earmark fs-1"></i><br>
                                            <small>Blank White</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-cream-minimalist" class="d-none">
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid #dee2e6;border-radius:8px;overflow:hidden;cursor:pointer;">
                                        <img src="/img/templates/blue-cream-minimalist.png" alt="Blue &amp; Cream" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue &amp; Cream</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="modern-elegant" class="d-none">
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid #dee2e6;border-radius:8px;overflow:hidden;cursor:pointer;">
                                        <img src="/img/templates/modern-elegant.jpg" alt="Modern Elegant" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Modern Elegant</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-white-minimalist" class="d-none">
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid #dee2e6;border-radius:8px;overflow:hidden;cursor:pointer;">
                                        <img src="/img/templates/blue-white-minimalist.png" alt="Blue & White Minimalist" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue &amp; White Minimalist</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-modern-medical" class="d-none">
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid #dee2e6;border-radius:8px;overflow:hidden;cursor:pointer;">
                                        <img src="/img/templates/blue-modern-medical.png" alt="Blue Modern Medical" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue Modern Medical</small>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="bg-template-option">
                                    <input type="radio" name="background_template" value="blue-white-geometric" class="d-none">
                                    <div class="bg-template-card" style="width:120px;height:170px;border:2px solid #dee2e6;border-radius:8px;overflow:hidden;cursor:pointer;">
                                        <img src="/img/templates/blue-white-geometric.png" alt="Blue White Geometric" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <small class="d-block text-center mt-1">Blue White Geometric</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Notes & Status -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><i class="bi bi-sticky"></i> Notes</label>
                            <div class="quill-editor notes-editor" style=""></div>
                            <textarea name="notes" class="form-control quill-value d-none" rows="3"
                                      placeholder="Custom notes for this document..."></textarea>
                            <div class="form-text">
                                Use placeholders: <code>{{programme}}</code>, <code>{{Session_no}}</code>, <code>{{invoice_no}}</code>, <code>{{issue_date}}</code>, <code>{{due_date}}</code>, <code>{{from_company}}</code>, <code>{{client}}</code>, <code>{{total_amount}}</code>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="paid">Paid</option>
                                <?php if($docType === 'quotation'): ?>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                <?php endif; ?>
                            </select>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="is_paid" id="isPaid">
                                <label class="form-check-label fw-bold text-success" for="isPaid">
                                    <i class="bi bi-check-circle"></i> Mark as PAID
                                </label>
                            </div>
                        
                        <div class="row mt-2">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-lg"></i> Create <?= $typeLabel ?>
                                </button>
                                <a href="/invoices" class="btn btn-secondary btn-lg">Cancel</a>
                            </div>
                        </div>
                    </div>
                    </div>
                    </div>

                    
                </form>
            </div>
        </div>
    </div>
</div>
  <footer class="text-center py-3 bg-light border-top mt-4 small text-muted">
                                &copy; <?= date('Y') ?> CashFlow System &mdash; All rights reserved.
                            </footer>
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
    qscript.onload = initQuills;

    // helper to initialize editors
    function initQuill(div) {
        if (div.__quill) return;
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
                <textarea name="item_description[]" class="form-control quill-value d-none" rows="2" placeholder="Service description..."></textarea>
            </td>
            <td><input type="number" name="item_quantity[]" class="form-control item-qty" value="1" min="0" step="0.01"></td>
            <td><input type="number" name="item_unit_price[]" class="form-control item-price" value="0.00" min="0" step="0.01"></td>
            <td><input type="text" class="form-control item-amount" readonly value="0.00"></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x-lg"></i></button></td>
        `;
        itemsBody.appendChild(row);
        // init new quill editor only
        initQuill(row.querySelector('.quill-editor'));
        recalculate();
    }

    addBtn.addEventListener('click', addItemRow);

    itemsBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
            recalculate();
        }
    });


    taxRateInput.addEventListener('input', recalculate);
    taxLabelInput.addEventListener('input', recalculate);

    itemsBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const rows = itemsBody.querySelectorAll('.item-row');
            if (rows.length > 1) {
                e.target.closest('.item-row').remove();
                recalculate();
            }
        }
    });

    recalculate();

    // ── Programme-based auto-numbering ──────────────────
    const programmeSelect = document.getElementById('programmeSelect');
    const docNumber = document.getElementById('docNumber');
    const refreshBtn = document.getElementById('refreshNumber');
    const numberHint = document.getElementById('numberHint');
    const docType = '<?= htmlspecialchars($docType) ?>';

    function fetchNextNumber() {
        const pid = programmeSelect.value;
        if (!pid) {
            docNumber.value = '';
            numberHint.textContent = 'Select a programme first';
            return;
        }
        const selectedOption = programmeSelect.options[programmeSelect.selectedIndex];
        const prefix = selectedOption.dataset.prefix;
        numberHint.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
        fetch(`/invoices/next-number?programme_id=${pid}&type=${encodeURIComponent(docType)}`)
            .then(r => r.json())
            .then(data => {
                if (data.number) {
                    docNumber.value = data.number;
                    numberHint.textContent = 'Prefix: ' + (data.prefix || prefix);
                } else {
                    numberHint.textContent = data.error || 'Error generating number';
                }
            })
            .catch(() => {
                numberHint.textContent = 'Failed to generate number';
            });
    }

    programmeSelect.addEventListener('change', fetchNextNumber);
    refreshBtn.addEventListener('click', fetchNextNumber);

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
