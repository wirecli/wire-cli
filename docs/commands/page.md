![Wirecli Logo](/assets/img/favicon-16x16.png){.logo} **Page**

---

## List

Output the page structure of the current ProcessWire installation with hierarchy, titles, IDs and page names.

```sh
$ wirecli page:list
```

### Available options:

```sh
--all : Get a list of all pages (recursive) without admin-pages
--trash : Get a list of trashed pages (recursive) without admin-pages
--level : How many levels to show
--start : start page id
```
### Examples

List all pages.

```sh
$ wirecli page:list

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|      |-- Child page example 1 { 1002, basic-page }
|      |-- Child page example 3 { 1014, basic-page }
|  |-- Site Map { 1005, sitemap }
```

Get a list of all (including hidden) pages (recursive) without admin-pages.

```sh
$ wirecli page:list --all

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|      |-- Child page example 1 { 1002, basic-page }
|      |-- Child page example 3 { 1014, basic-page }
|  |-- Site Map { 1005, sitemap }
|  |-- Search { 1000, search }
|  |-- 404 Page { 27, basic-page }
```

Get a list of trashed pages (recursive) without admin-pages.

```sh
$ wirecli page:list --trash

|-- Trash { 7, admin }
|  |-- Child page example 2 { 1004, basic-page }
```

Get a list of pages output 1 level.

```sh
$ wirecli page:list --level=1

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|  |-- Site Map { 1005, sitemap }
```

Get a list of pages, starting by the page with id 1001.

```sh
$ wirecli page:list --start=1001

|-- About { 1001, basic-page }
|  |-- Child page example 1 { 1002, basic-page }
|  |-- Child page example 3 { 1014, basic-page }
```

Get a list of all pages inluding trashed pages ouput 1 level.

```sh
$ wirecli page:list --all --trash --level=1

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|  |-- Site Map { 1005, sitemap }
|  |-- Search { 1000, search }
|  |-- 404 Page { 27, basic-page }
|  |-- Trash { 7, admin }
```

---

## Create

Create a new page with the given parameters.

```sh
$ wirecli page:create
```

### Available options:

```sh
--template : template for new page
--parent : parent page name
--title : page title
--file : field data file (json)
```

### Examples

Create a new page.

```sh
$ wirecli page:create example --template=basic-page --parent=home --title="Example Page"
```

Create multiple pages.

```sh
$ wirecli page:create example-1,example-2,example-3 --template=basic-page --parent=home
```

Create new page, ask for template.

```sh
$ wirecli page:create newpage --title="Child page example 3"

Please enter the template : basic-page
```

Create a new page and import field data from valid json file.

```sh
$ wirecli page:create example --template=basic-page --parent=home --title="Example Page" --file=import.json
```

---

## Delete

Put a page into the trash. Selector is either page name, page id or selector. 

```sh
$ wirecli page:delete {selector}
```

### Available options:

```sh
--rm : force delete the selected page without putting it in the trash first
```

### Examples

Delete all pages where the parent id equals 1004:

```sh
$ wirecli page:delete --rm "has_parent=1004"
```

Delete page with id 1005:

```sh
$ wirecli page:delete 1005
```

Delete pages with id 1002 and 1003:

```sh
$ wirecli page:delete 1002,1003
```

Delete pages with page name *About*:

```sh
$ wirecli page:delete About
```

---

## Empty Trash

Empty ProcessWire's trash.

```sh
$ wirecli page:emptytrash
```

---
