<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;

class AuthController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function login(): void
    {
        $data = getJsonInput();

        if (empty($data['email']) || empty($data['password'])) {
            respBadRequest(['email' => 'Email y contraseña son obligatorios.']);
            return;
        }

        $user = $this->userService->getByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            respUnauthorized(['general' => 'Credenciales inválidas.']);
            return;
        }

        if (!$user['active']) {
            respForbidden(['general' => 'La cuenta está desactivada.']);
            return;
        }

        authLogin(
            (int) $user['id'],
            $user['username'],
            (int) $user['role_id'],
            $user['role_name'],
            (bool) $user['must_change_password']
        );

        respSuccess([
            'user' => [
                'id'                   => $user['id'],
                'username'             => $user['username'],
                'email'                => $user['email'],
                'must_change_password' => (bool) $user['must_change_password'],
                'role'                 => ['id' => $user['role_id'], 'name' => $user['role_name']],
            ],
        ]);
    }

    public function logout(): void
    {
        authLogout();
        respNoContent();
    }

    public function me(): void
    {
        if (!authCheck()) {
            respUnauthorized();
            return;
        }

        $user = authUser();
        $repo = new \App\Repositories\UserRepository();
        $full = $repo->findById((int) $user['id']);

        if (!$full) {
            respNotFound();
            return;
        }

        respSuccess(['user' => $full]);
    }
}
