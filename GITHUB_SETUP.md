# 📤 GitHub Setup Guide

## Step 1: Create a GitHub Repository

1. Go to [GitHub.com](https://github.com) and sign in
2. Click the **"+"** icon in the top right corner
3. Select **"New repository"**
4. Fill in the details:
   - **Repository name:** `InventorySystemMabini` or `mabini-inventory-system`
   - **Description:** "Inventory Management System for Municipality of Mabini"
   - **Visibility:** Choose **Public** (for open collaboration) or **Private** (for team only)
   - **DO NOT** initialize with README, .gitignore, or license (we already have these)
5. Click **"Create repository"**

## Step 2: Connect Your Local Repository to GitHub

After creating the repository, GitHub will show you commands. Use these commands in your terminal:

```bash
# Add GitHub as the remote origin (replace <your-username> with your GitHub username)
git remote add origin https://github.com/<your-username>/InventorySystemMabini.git

# Rename branch to main (GitHub's default)
git branch -M main

# Push your code to GitHub
git push -u origin main
```

### Example:
If your GitHub username is `mabini-municipality`, run:
```bash
git remote add origin https://github.com/mabini-municipality/InventorySystemMabini.git
git branch -M main
git push -u origin main
```

## Step 3: Set Up Team Collaboration

### Option A: Add Collaborators (For Private Repository)

1. Go to your repository on GitHub
2. Click **Settings** → **Collaborators**
3. Click **"Add people"**
4. Enter team members' GitHub usernames or emails
5. They'll receive an invitation to collaborate

### Option B: Create an Organization (Recommended for Teams)

1. Go to [github.com/account/organizations/new](https://github.com/account/organizations/new)
2. Create a new organization (e.g., "Mabini-Municipality")
3. Transfer the repository to the organization
4. Add team members to the organization
5. Set permissions (Admin, Write, Read)

## Step 4: Team Workflow

### For Team Members:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/<your-username>/InventorySystemMabini.git
   cd InventorySystemMabini
   ```

2. **Before making changes, always pull latest code:**
   ```bash
   git pull origin main
   ```

3. **Create a new branch for your feature:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

4. **Make your changes, then commit:**
   ```bash
   git add .
   git commit -m "Description of your changes"
   ```

5. **Push your branch to GitHub:**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request on GitHub:**
   - Go to the repository on GitHub
   - Click "Pull requests" → "New pull request"
   - Select your branch
   - Add description and request review
   - Wait for approval and merge

## Step 5: Protecting the Main Branch

1. Go to **Settings** → **Branches**
2. Click **"Add rule"**
3. Branch name pattern: `main`
4. Enable:
   - ✅ Require pull request reviews before merging
   - ✅ Require status checks to pass
   - ✅ Require branches to be up to date
5. Save changes

## Common Git Commands

```bash
# Check status
git status

# View commit history
git log --oneline

# Switch branches
git checkout branch-name

# Create and switch to new branch
git checkout -b new-branch-name

# Merge branch into main
git checkout main
git merge feature-branch

# Discard changes
git checkout -- filename

# Update from remote
git fetch
git pull

# View remote URL
git remote -v
```

## Troubleshooting

### Authentication Error?
If you get an authentication error when pushing:

1. **Use Personal Access Token:**
   - Go to GitHub Settings → Developer settings → Personal access tokens
   - Generate new token (classic)
   - Select scopes: `repo`, `workflow`
   - Copy the token
   - When pushing, use token as password

2. **Or use GitHub CLI:**
   ```bash
   # Install GitHub CLI: https://cli.github.com/
   gh auth login
   ```

### Already exists error?
If remote already exists:
```bash
git remote remove origin
git remote add origin <your-github-url>
```

## 🎉 You're All Set!

Your team can now:
- ✅ Clone the repository
- ✅ Create branches
- ✅ Make changes
- ✅ Submit pull requests
- ✅ Review and merge code
- ✅ Track issues
- ✅ Collaborate effectively

## 📚 Resources

- [GitHub Docs](https://docs.github.com)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)
- [Pull Request Tutorial](https://docs.github.com/en/pull-requests)
