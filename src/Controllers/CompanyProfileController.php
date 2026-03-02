<?php
declare(strict_types=1);

namespace Controllers;

use Models\CompanyProfile;

class CompanyProfileController extends Controller {
    private CompanyProfile $model;

    public function __construct() {
        $this->model = new CompanyProfile();
    }

    public function index(): void {
        $this->requireAuth();
        $profiles = $this->model->findByUser($_SESSION['user_id']);
        $this->render('company_profiles/index', [
            'profiles' => $profiles,
            'csrf_token' => $this->csrf()
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $this->render('company_profiles/create', [
            'csrf_token' => $this->csrf()
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = $_SESSION['user_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'tax_id' => trim($_POST['tax_id'] ?? ''),
            'registration_no' => trim($_POST['registration_no'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'bank_branch' => trim($_POST['bank_branch'] ?? ''),
            'is_default' => isset($_POST['is_default']),
            'user_id' => $userId
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Company name is required.';
            $this->redirect('/company-profiles/create');
            return;
        }

        // Handle logo upload
        if (!empty($_FILES['logo']['tmp_name'])) {
            $data['logo_path'] = $this->handleUpload($_FILES['logo'], 'logos');
        }

        // Handle signature upload
        if (!empty($_FILES['signature']['tmp_name'])) {
            $data['signature_path'] = $this->handleUpload($_FILES['signature'], 'signatures');
        }

        if ($data['is_default']) {
            $this->model->clearDefault($userId);
        }

        $this->model->create($data);
        $_SESSION['success'] = 'Company profile created successfully.';
        $this->redirect('/company-profiles');
    }

    public function edit(int $id): void {
        $this->requireAuth();
        $profile = $this->model->find($id);

        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Profile not found.';
            $this->redirect('/company-profiles');
            return;
        }

        $this->render('company_profiles/edit', [
            'profile' => $profile,
            'csrf_token' => $this->csrf()
        ]);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $profile = $this->model->find($id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Profile not found.';
            $this->redirect('/company-profiles');
            return;
        }

        $userId = $_SESSION['user_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'tax_id' => trim($_POST['tax_id'] ?? ''),
            'registration_no' => trim($_POST['registration_no'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'bank_branch' => trim($_POST['bank_branch'] ?? ''),
            'is_default' => isset($_POST['is_default']),
        ];

        if (!empty($_FILES['logo']['tmp_name'])) {
            $data['logo_path'] = $this->handleUpload($_FILES['logo'], 'logos');
        }

        if (!empty($_FILES['signature']['tmp_name'])) {
            $data['signature_path'] = $this->handleUpload($_FILES['signature'], 'signatures');
        }

        if ($data['is_default']) {
            $this->model->clearDefault($userId);
        }

        $this->model->update($id, $data);
        $_SESSION['success'] = 'Company profile updated successfully.';
        $this->redirect('/company-profiles');
    }

    public function delete(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $profile = $this->model->find($id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Profile not found.';
            $this->redirect('/company-profiles');
            return;
        }

        $this->model->delete($id);
        $_SESSION['success'] = 'Company profile deleted.';
        $this->redirect('/company-profiles');
    }

    private function handleUpload(array $file, string $folder): string {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $destination = PUBLIC_PATH . '/uploads/' . $folder . '/' . $filename;
        move_uploaded_file($file['tmp_name'], $destination);
        return '/uploads/' . $folder . '/' . $filename;
    }
}
