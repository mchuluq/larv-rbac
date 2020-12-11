  **[under development]**

# LARV-RBAC

A Laravel Role Based Access Control.

### FEATURES

- [x] Role Based Access Control
- [x] Multi-account per-user
- [x] Google Authenticator

### COMPONENT

- guard
  - `rbac-web` web session guard, replacement for default laravel web guard
- user provider `rbac-user`
- middlewares
  - `Mchuluq\Larv\Rbac\Middlewares\ConfirmOtp` confirm OTP
  - `Mchuluq\Larv\Rbac\Middlewares\Authenticate` common auth and OTP, replacement for default laravel auth
  - `Mchuluq\Larv\Rbac\Middlewares\CheckPermission`
  - `Mchuluq\Larv\Rbac\Middlewares\CheckRole:role-id`
  - `Mchuluq\Larv\Rbac\Middlewares\CheckGroup:group-id`