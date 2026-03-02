<?php
declare(strict_types=1);

namespace Controllers;

use Models\Programme;

class ProgrammeController extends Controller {
    private Programme $model;

    public function __construct() {
        $this->model = new Programme();
    }

    public function index(): void {
        $this->requireAuth();
        $programmes = $this->model->findByUser($_SESSION['user_id']);
        $this->render('programmes/index', [
            'programmes' => $programmes,
            'csrf_token' => $this->csrf()
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $this->render('programmes/create', [
            'csrf_token' => $this->csrf()
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = $_SESSION['user_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'prefix' => strtoupper(trim($_POST['prefix'] ?? '')),
            'description' => trim($_POST['description'] ?? ''),
            'user_id' => $userId
        ];

        if (empty($data['name']) || empty($data['prefix'])) {
            $_SESSION['error'] = 'Programme name and prefix are required.';
            $this->redirect('/programmes/create');
            return;
        }

        // Sanitize prefix: only letters, numbers, hyphens
        $data['prefix'] = preg_replace('/[^A-Z0-9\-]/', '', $data['prefix']);

        // Check uniqueness
        $existing = $this->model->findByPrefix($data['prefix'], $userId);
        if ($existing) {
            $_SESSION['error'] = 'A programme with prefix "' . $data['prefix'] . '" already exists.';
            $this->redirect('/programmes/create');
            return;
        }

        $this->model->create($data);
        $_SESSION['success'] = 'Programme created successfully.';
        $this->redirect('/programmes');
    }

    public function edit(int $id): void {
        $this->requireAuth();
        $programme = $this->model->find($id);

        if (!$programme || $programme['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Programme not found.';
            $this->redirect('/programmes');
            return;
        }

        $this->render('programmes/edit', [
            'programme' => $programme,
            'csrf_token' => $this->csrf()
        ]);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $programme = $this->model->find($id);
        if (!$programme || $programme['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Programme not found.';
            $this->redirect('/programmes');
            return;
        }

        $userId = $_SESSION['user_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'prefix' => strtoupper(trim($_POST['prefix'] ?? '')),
            'description' => trim($_POST['description'] ?? ''),
        ];

        $data['prefix'] = preg_replace('/[^A-Z0-9\-]/', '', $data['prefix']);

        // Check uniqueness (excluding self)
        $existing = $this->model->findByPrefix($data['prefix'], $userId);
        if ($existing && $existing['id'] !== $id) {
            $_SESSION['error'] = 'A programme with prefix "' . $data['prefix'] . '" already exists.';
            $this->redirect('/programmes/' . $id . '/edit');
            return;
        }

        $this->model->update($id, $data);
        $_SESSION['success'] = 'Programme updated successfully.';
        $this->redirect('/programmes');
    }

    public function delete(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $programme = $this->model->find($id);
        if (!$programme || $programme['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Programme not found.';
            $this->redirect('/programmes');
            return;
        }

        $this->model->delete($id);
        $_SESSION['success'] = 'Programme deleted.';
        $this->redirect('/programmes');
    }
}
