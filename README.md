# LEXO Conserve Pages
Groups marked pages.

---
## Versioning
Release tags are created with Semantic versioning in mind. Commit messages were following convention of [Conventional Commits](https://www.conventionalcommits.org/).

---
## Compatibility
- WordPress version `>=4.7`. Tested and works fine up to `6.7.2`.
- PHP version `>=7.4.1`. Tested and works fine up to `8.3.11`.

---
## Installation
1. Go to the [latest release](https://github.com/lexo-ch/lexo-conserve-pages/releases/latest/).
2. Under Assets, click on the link named `Version x.y.z`. It's a compiled build.
3. Extract zip file and copy the folder into your `wp-content/plugins` folder and activate LEXO Conserve Pages in plugins admin page. Alternatively, you can use downloaded zip file to install it directly from your plugin admin page.

---
## Filters
#### - `cp/admin_localized_script`
*Parameters*
`apply_filters('cp/admin_localized_script', $args);`
- $args (array) The array which will be used for localizing `cpAdminLocalized` variable in the admin.

#### - `cp/enqueue/admin-cp.js`
*Parameters*
`apply_filters('cp/enqueue/admin-cp.js', $args);`
- $args (bool) Printing of the file `admin-cp.js` (script id is `cp/admin-cp.js-js`). It also affects printing of the localized `cpAdminLocalized` variable.

#### - `cp/enqueue/admin-cp.css`
*Parameters*
`apply_filters('cp/enqueue/admin-cp.css', $args);`
- $args (bool) Printing of the file `admin-cp.css` (stylesheet id is `cp/admin-cp.css-css`).

#### - `cp/exclude-pages`
*Parameters*
`apply_filters('cp/exclude-pages', $args);`
- $args (array) The array of IDs of the pages on which checkbox will be hidden. Additionally pages with those IDs and their descendant pages will be excluded from conserving.

---
## Actions
#### - `cp/init`
- Fires on LEXO Conserve Pages init.

#### - `cp/localize/admin-cp.js`
- Fires right before LEXO Conserve Pages admin script has been enqueued.

#### - `cp/before-conserving-pages`
- Fires right before pages have been conserved.

#### - `cp/after-conserving-pages`
- Fires right after pages have been conserved.

---
## Changelog
Changelog can be seen on [latest release](https://github.com/lexo-ch/lexo-conserve-pages/releases/latest/).
