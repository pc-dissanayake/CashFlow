<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-people"></i> Add Client</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/client-profiles">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" required
                               placeholder="e.g. United Tractor & Equipment (Pvt) Ltd (UTE CAT)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control"
                               placeholder="e.g. Safety Officer / HR Department">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="Client address"></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. info@ute.lk">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tax ID</label>
                        <input type="text" name="tax_id" class="form-control">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Create Client
                        </button>
                        <a href="/client-profiles" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
