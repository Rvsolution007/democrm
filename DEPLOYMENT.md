# RV_CRM Deployment Guide

## Architecture Overview

```
LOCAL (main branch)
    ↓ git push origin main:staging
PRODUCTION 1: git_crm (staging branch) — bot.rvallsolutions.com — Auto Deploy
    ↓ git push origin staging:production → Manual Deploy in EasyPanel
PRODUCTION 2: git_crm_live (production branch) — live.rvallsolutions.com — Manual Deploy
```

---

## Quick Reference Commands

```bash
# Push local changes to staging (auto-deploys)
git add .
git commit -m "your message"
git push origin main:staging

# Promote staging to production (then manually deploy in EasyPanel)
git push origin staging:production
```

---

## Initial Setup (One-Time)

### Step 1: Create Branches

```bash
# Create staging branch from main
git checkout main
git checkout -b staging
git push origin staging

# Create production branch from staging
git checkout -b production
git push origin production

# Go back to main for development
git checkout main
```

### Step 2: Configure EasyPanel — git_crm (Production 1)

1. Open EasyPanel → Project: **git_crm**
2. **General Tab:**
   - Source: GitHub → `Rvsolution007/democrm`
   - Branch: `staging`
   - Build Method: **Dockerfile**
   - Auto Deploy: **Enabled** ✅
3. **Environment Tab:**
   - Copy all variables from `.env.staging` file
   - Paste into EasyPanel environment variables

### Step 3: Configure EasyPanel — git_crm_live (Production 2)

1. Open EasyPanel → Project: **git_crm_live**
2. **General Tab:**
   - Source: GitHub → `Rvsolution007/democrm` (same repo!)
   - Branch: `production`
   - Build Method: **Dockerfile**
   - Auto Deploy: **Disabled** ❌
3. **Environment Tab:**
   - Copy variables from `.env.production` file
   - Update `DB_PASSWORD` with live database password
   - Update any other live-specific values

### Step 4: MySQL Service in EasyPanel

Both projects need MySQL. In each EasyPanel project:
1. Add a **MySQL** service (if not already added)
2. Set database name: `democrm`
3. Set username: `democrm`
4. Set password: (your password)

> **Note:** The `DB_HOST=mysql` in env variables refers to EasyPanel's internal MySQL service hostname.

---

## Daily Workflow

### Making Changes

```bash
# 1. Work locally on main branch
git checkout main
# ... make your changes ...

# 2. Commit changes
git add .
git commit -m "fix: describe what you changed"

# 3. Push to staging (auto-deploys to bot.rvallsolutions.com)
git push origin main:staging

# 4. Test on https://bot.rvallsolutions.com/

# 5. If issues found → fix locally → repeat steps 2-4

# 6. When staging is OK → push to production
git push origin staging:production

# 7. Go to EasyPanel → git_crm_live → Click "Deploy"
```

### Rolling Back

```bash
# If staging has issues, reset to last working commit
git log --oneline -10                    # find the good commit hash
git push origin <good-commit>:staging --force

# If production has issues
git push origin <good-commit>:production --force
# Then manually deploy in EasyPanel → git_crm_live
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| App shows 500 error | Check EasyPanel logs. Set `APP_DEBUG=true` temporarily |
| Database connection refused | Verify `DB_HOST=mysql` in EasyPanel env. Check MySQL service is running |
| CSS/JS not loading | Run deploy again. Check `APP_URL` matches your domain |
| Migrations failed | Check EasyPanel container logs for specific error |
| Storage permission error | Container entrypoint auto-fixes this. Redeploy if needed |
| Changes not appearing | Check correct branch was pushed. Check auto-deploy is enabled (git_crm) |
