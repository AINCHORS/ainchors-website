# Local development

Open `C:\xampp\htdocs\ainchors-website-v2` in VS Code, then run:

```powershell
composer install
npm install
php artisan key:generate
composer run dev
```

The project VS Code settings put Laravel Herd PHP 8.4 first in the integrated terminal. If port 8000 is preferred, run `php artisan serve` and open `http://127.0.0.1:8000/home`.
