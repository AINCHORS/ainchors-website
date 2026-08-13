# Modules

`app/Services/SiteContent.php` centralises approved page content. Blade views live under `resources/views/modules`, while reusable UI is under `resources/views/components` and the common shell is under `resources/views/layouts`.

Each legacy route can be migrated independently from the read-only bridge into a controller, content service and Blade module without introducing a SPA.
