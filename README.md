  **[under development]**

# LARV-RBAC

A Laravel Role Based Access Control.

### FEATURES

- Role Based Access Control
- Multi-account per-user
- Google Authenticator

### COMPONENT

- guard
  - `rbac-web` web session guard
- user provider `rbac-user`
- middlewares
  - `Mchuluq\Larv\Rbac\Middlewares\ConfirmOtp`  
  - `Mchuluq\Larv\Rbac\Middlewares\HasPermission`  