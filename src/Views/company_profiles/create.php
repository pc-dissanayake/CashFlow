<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-building"></i> Create Company Profile</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/company-profiles" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Company Name *</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. Deep Diagnostics (Pvt) Ltd">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                                <label class="form-check-label" for="is_default">Set as Default</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="tagline" class="form-control"
                               placeholder="e.g. Corporate Health Screening & Diagnostics">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="e.g. 68, Jaya Mawatha, Bangalawatha, Kottawa."></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 077 977 6829">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. contact@deepdiagnostics.lk">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" class="form-control" placeholder="e.g. www.deepdiagnostics.lk">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tax ID</label>
                            <input type="text" name="tax_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Registration No.</label>
                            <input type="text" name="registration_no" class="form-control">
                        </div>
                    </div>

                    <hr>
                    <h6>Bank Details</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account No.</label>
                            <input type="text" name="bank_account" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <input type="text" name="bank_branch" class="form-control">
                        </div>
                    </div>

                    <hr>
                    <h6>Branding</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted">PNG or JPG, recommended 300x100px</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Authorized Signature</label>
                            <input type="file" name="signature" class="form-control" accept="image/*">
                            <small class="text-muted">PNG with transparent background recommended</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Create Profile
                        </button>
                        <a href="/company-profiles" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
