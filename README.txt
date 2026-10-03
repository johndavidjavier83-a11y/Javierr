ACADEMIC PORTFOLIO - SETUP
==========================
OWNER LOGIN (change it after first login!)
  Email   : owner@myportfolio.com
  Password: Admin@2026

RUN LOCALLY (XAMPP)
 1. Copy this folder to C:\xampp\htdocs\academic-portfolio
 2. Start Apache in XAMPP (enable pdo_sqlite in php.ini if needed - usually on)
 3. Open http://localhost/academic-portfolio/

RUN WITHOUT XAMPP:  php -S localhost:8000   (inside this folder)

ONLINE HOSTING: upload everything via FTP to any PHP host (InfinityFree, Hostinger...).
 Make sure uploads/ and database/ are writable (chmod 775).

Visitors can only view/download. Only the logged-in owner sees Upload/Edit/Delete.
Database: database/portfolio.sqlite (SQLite) - open with DB Browser for SQLite.
To upload big files raise upload_max_filesize and post_max_size in php.ini.
