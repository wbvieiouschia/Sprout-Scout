SPROUT SCOUT (PHP version) – setup on XAMPP
1. Install XAMPP. In php.ini enable:  extension=pdo_pgsql  and  extension=curl   (remove the ';'), then restart Apache.
2. Copy this whole folder to C:\xampp\htdocs\sprout
3. Copy .env.example to .env and fill in your Supabase database password / pooler host
   (Supabase dashboard > Connect > Session pooler). Never upload .env to GitHub.
4. Open http://localhost/sprout/
Your Supabase tables, data and user accounts are used as-is. Passwords are hashed by Supabase Auth.
Roles: profile.role_id = 1 is Administrator (can edit/delete everything); others can only change records they created.
