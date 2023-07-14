![Wirecli Logo](/assets/img/favicon-16x16.png){.logo} **Role**

---

## List

List available roles.

```sh
$ wirecli role:list
```

### Examples

List all roles.

```sh
$ wirecli role:list

  - editor
  - guest
  - newsletter
  - superuser
```

---

## Create

Create new role(s) with the given parameters.

```sh
$ wirecli role:create {role-name,role-name}
```

### Examples

Create a new role.

```sh
$ wirecli role:create editor

Role 'editor' created successfully!
```

---

## Delete

Delete role(s) with the given parameters.

```sh
$ wirecli role:delete {role-name,role-name}
```

### Examples

Delete a role.

```sh
$ wirecli role:delete editor

Role 'editor' deleted successfully!
```

---
