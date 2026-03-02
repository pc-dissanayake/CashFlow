<?php
declare(strict_types=1);

namespace Controllers;

use Models\ClientProfile;

class ClientProfileController extends Controller {
    private ClientProfile $model;

    public function __construct() {
        $this->model = new ClientProfile();
    }

    public function index(): void {
        $this->requireAuth();
        $profiles = $this->model->findByUser($_SESSION['user_id']);
        $this->render('client_profiles/index', [
            'profiles' => $profiles,
            'csrf_token' => $this->csrf()
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $this->render('client_profiles/create', [
            'csrf_token' => $this->csrf()
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $data = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'tax_id' => trim($_POST['tax_id'] ?? ''),
            'user_id' => $_SESSION['user_id']
        ];

        if (empty($data['company_name'])) {
            $_SESSION['error'] = 'Company name is required.';
            $this->redirect('/client-profiles/create');
            return;
        }

        $this->model->create($data);
        $_SESSION['success'] = 'Client profile created successfully.';
        $this->redirect('/client-profiles');
    }

    public function edit(int $id): void {
        $this->requireAuth();
        $profile = $this->model->find($id);

        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Client not found.';
            $this->redirect('/client-profiles');
            return;
        }

        $this->render('client_profiles/edit', [
            'profile' => $profile,
            'csrf_token' => $this->csrf()
        ]);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $profile = $this->model->find($id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Client not found.';
            $this->redirect('/client-profiles');
            return;
        }

        $data = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'tax_id' => trim($_POST['tax_id'] ?? ''),
        ];

        $this->model->update($id, $data);
        $_SESSION['success'] = 'Client profile updated.';
        $this->redirect('/client-profiles');
    }

    public function delete(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $profile = $this->model->find($id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Client not found.';
            $this->redirect('/client-profiles');
            return;
        }

        $this->model->delete($id);
        $_SESSION['success'] = 'Client profile deleted.';
        $this->redirect('/client-profiles');
    }
}
