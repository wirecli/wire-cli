![Wirecli Logo](/assets/img/favicon-16x16.png){.logo} **Template**

---

## List

List all ProcessWire templates.

```sh
$ wirecli template:list
```

### Available options:

```sh
--advanced : Show system templates as well; by default, system/internal templates are not shown
```

### Examples

List all available ProcessWire templates.

```sh
$ wirecli templates:list

 ============ ======== ======= ============= ========
  Template     Fields   Pages   Modified      Access
 ============ ======== ======= ============= ========
  basic-page   6        1       3 weeks ago
  home         6        1       3 weeks ago
 ============ ======== ======= ============= ========
```

List all available ProcessWire templates including system/internal templates.

```sh
$ wirecli templates:list --advanced

 ============ ======== ======= ============= ========
  Template     Fields   Pages   Modified      Access
 ============ ======== ======= ============= ========
  admin        2        26      3 weeks ago   ✖
  basic-page   6        1       3 weeks ago
  home         6        1       3 weeks ago
  permission   1        15      Never         ✖
  role         1        4       Never         ✖
  user         3        7       Never         ✖
 ============ ======== ======= ============= ========
```

---

## Create

Create a ProcessWire template.

```sh
$ wirecli template:create {name}
```

### Available options:

```sh
--fields : Attach existing fields to template, comma separated
--nofile : Prevent template file creation
```

### Examples

Create a template with given name.

```sh
$ wirecli template:create new-template
```

Create a template with given name but prevent template file creation.

```sh
$ wirecli template:create new-template --nofile
```

Create a template with given name and assign some fields.

```sh
$ wirecli template:create new-template --fields=images,headline,body
```

---

## Delete

Delete one or more ProcessWire templates.

```sh
$ wirecli template:delete {template},{template}
```

### Available options:

```sh
--nofile : Prevent template file deletion
```

### Examples

Delete template and belonging file.

```sh
$ wirecli template:delete basic-page
```

Delete template but keep belonging file.

```sh
$ wirecli template:delete basic-page --nofile
```

---

## Fields

Assign given fields to a given template.

```sh
$ wirecli template:fields {template}
```

### Available options:

```sh
--fields : Attach existing fields to template, comma separated
```

### Examples

Assign existing fields to template.

```sh
$ wirecli template:fields basic-page --fields=images,headline,body
```

---

## Tag

Tag one or more existing templates.

```sh
$ wirecli template:tag {template} --tag={tag}
```

### Available options:

```sh
--tag : tag name
```

### Examples

Tag template `basic-page` and `home` with tag `general`.

```sh
$ wirecli template:tag basic-page,home --tag=general
```

---

## Info

Displays detailed information about a specific template.

```sh
$ wirecli template:info {template}
```

### Examples

Get detailed information about template `basic-page`.

```sh
$ wirecli template:info basic-page
```

---
