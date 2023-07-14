![Wirecli Logo](/assets/img/favicon-16x16.png){.logo} **User**

---

## List

List all users.

```sh
$ wirecli user:list
```

### Available options:

```sh
--role : filter by user role (given the role exists)
```

### Examples

List all users.

```sh
$ wirecli user:list

Users: 2

 ========== =========== =========== ==================
  Username   E-Mail      Superuser   Roles
 ========== =========== =========== ==================
  admin      pw@ws.com   ✔           guest, superuser
  guest                              guest
 ========== =========== =========== ==================
```

List all superusers.

```sh
$ wirecli user:list --role=superuser

Users: 1

 ========== =========== =========== ==================
  Username   E-Mail      Superuser   Roles
 ========== =========== =========== ==================
  admin      pw@ws.com   ✔           guest, superuser
 ========== =========== =========== ==================
```

---

## Create

Create an user.

```sh
$ wirecli user:create {user-name}
```

### Available options:

```sh
--email : mail address for the user 
--password : password for the user
--roles : assign user roles, comma separated (given the role exist), role `guest` is attached by default
```

### Examples

Create a new user by given email, password and role.

```sh
$ wirecli user:create editor --email="editor@ws.pw" --password=cgBG+T9e7Nu2 --roles=editor
```

Create a new user with role guest.

```sh
$ wirecli user:create pwguest --email="guest@ws.pw" --password=ws6jem6un3V&
```

Create a new user with roles superuser and editor.

```sh
$ wirecli user:create pwadmin --roles=superuser,editor

Please enter a email address : pwadmin@ws.pw
Please enter a password :
```

---

## Delete

Delete an user or multiple users at once (by name or role).

```sh
$ wirecli user:delete {user-name},{user-name}*
```

\* This argument is optional. If you want to delete users by role instead, just skip it.

### Available options:

```sh
--role : role name
```

### Examples

Delete an user.

```sh
$ wirecli user:delete pweditor
```

Delete multiple users.

```sh
$ wirecli user:delete pwadmin,pwguest
```

Delete users by given role.

```sh
$ wirecli user:delete --role=editor
```

---

## Update

Update an existing user.

```sh
$ wirecli user:update {user-name}
```

### Available options:

```sh
--email : mail address for the user 
--password : password for the user
--roles : assign user roles, comma separated (given the role exist), role `guest` is attached by default
```

### Examples

Update an user; sets new email address.

```sh
$ wirecli user:update pweditor --email=otto@example.org
```

Update an user; sets new email, password and roles.

```sh
$ wirecli user:update pwguest --email=otto@example.org --roles=superuser,editor --password=somepass
```
