# Project Functionalities Overview

This file summarizes the main functionality implemented so far in StaffEase Pro. It is meant to help explain the project quickly during the exam.

## 1. Single dashboard shell

The dashboard uses a shared layout and a single modal shell for CRUD actions. Instead of jumping across multiple pages, the user stays inside the dashboard flow.

```php
<?php require __DIR__ . '/app/layout/crud-modal.php'; ?>
```

This approach keeps the interface compact and easier to present.

## 2. Role-based navigation

The navbar and sidebar change depending on the logged-in role.

```php
if ($role !== 'super_admin') {
    $rightIcons[] = [
        'type' => 'button',
        'title' => 'Settings',
        'target' => 'modal-settings',
    ];
}
```

Super Admin sees a cleaner navbar, while other roles keep access to settings.

## 3. CRUD modals for companies, users, departments, documents, and messages

The project uses one modal shell and different templates for each entity.

```php
<template id="crud-template-documents">
    <div class="crud-panel-grid crud-panel-grid-wide" data-crud-entity="documents">
```

This keeps the UI consistent and reduces duplicated code.

## 4. Department head management

Departments can store a head user through `head_user_id`, and the selected user receives the `department_manager` role.

```php
if (!empty($payload['head_user_id'])) {
    $userModel->update((int) $payload['head_user_id'], [
        'role' => 'department_manager',
    ]);
}
```

This makes the business rule explicit and easy to explain.

## 5. Document downloads

Documents can be downloaded from the dashboard through a dedicated route.

```php
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
readfile($resolvedPath);
```

This works for PDF and other file formats stored in the database as document paths.

## 6. Company directory and counts

The company directory shows companies, admins, department heads, departments, and user counts.

```php
'(SELECT COUNT(DISTINCT u_count.id)
    FROM users u_count
    INNER JOIN departments d_count ON d_count.id = u_count.department_id
    WHERE d_count.company_id = c.id) AS users_count'
```

This avoids double counting and keeps statistics reliable.

## 7. Flash messages and JSON helpers

The project centralizes utility logic in helper functions.

```php
function jsonResponse(array $payload, int $statusCode = 200): never
```

These helpers simplify controller code and make the architecture easier to explain.

## 8. Current file structure

- `backend/` contains controllers, models, helpers, and bootstrap code.
- `app/layout/` contains shared layout pieces.
- `assets/css/` contains the interface styles.
- `assets/js/` contains dashboard behaviors and flash interactions.
- `db/` contains the schema and migrations.

## 9. Exam talking points

1. Show the front controller and router.
2. Show how the dashboard changes by role.
3. Show one CRUD modal and one download flow.
4. Explain the department head rule.
5. Explain the difference between HTML rendering and JSON helpers.
