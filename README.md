# StaffEase Pro

Simple PHP/MySQL application for managing shifts, attendance, requests, and documents.

## Goal
The project should stay readable, simple, and easy to present for the exam. The current structure uses a front controller, PHP controllers, PDO models, and server-rendered views.

## Structure
- `index.php`: single entry point.
- `backend/`: bootstrap, helpers, controllers, models, JSON endpoints.
- `app/layout/`: shared interface elements.
- `public/views/`: role-based pages.
- `public/assets/`: CSS, icons, and images.
- `config/`: connection settings.
- `db/`: SQL schema.

## Basic rules
- All visible text must stay in English.
- The login form is shared by all roles.
- The home page remains public.
- The dashboard changes according to the signed-in role.
- JSON endpoints are used for REST or AJAX needs and return JSON through the router.

## Existing JSON route
- `?route=api-dashboard` returns a JSON response.
- It exposes dashboard data without HTML.

## Suggested exam flow
1. Show the router and authentication.
2. Explain the separation between controllers, models, and views.
3. Show a simple CRUD action.
4. Show the JSON endpoint.
5. Explain the hosting setup.

## Deployment
- The project is designed to work on a standard PHP/MySQL hosting such as InfinityFree.
- Paths are built relatively so they remain valid at the root or inside a subfolder.

## Notes
- Temporary test files were removed.
- The code should stay lean, without unnecessary layers or hidden logic in the views.
