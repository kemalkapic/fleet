# Deployment Notes

## GitHub

Repository:

```bash
git remote add origin https://github.com/kemalkapic/fleet.git
git branch -M main
git add .
git commit -m "Initial fleet SaaS application"
git push -u origin main
```

## cPanel

Use `.cpanel.yml` as the starting point for cPanel Git Version Control deployment.

Before first deploy:

- Set the correct `APPPATH` in `.cpanel.yml`.
- Create the production `.env` file on the server manually.
- Put real `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, and OpenRouter values only on the server.
- Make sure the domain document root points to `public/`.
- Run migrations after database credentials are ready.

Do not commit `.env`, `vendor/`, `var/`, uploaded documents, or the original `tema/` folder.
