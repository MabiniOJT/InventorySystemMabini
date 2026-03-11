#!/usr/bin/env python3
"""
Automated cleanup script - moves obsolete files to archive
Creates proper folder structure for GSO Inventory System
"""
import os
import shutil
from pathlib import Path

# Base directory
BASE_DIR = Path(__file__).parent

def create_directories():
    """Create organized folder structure"""
    dirs = [
        'archive/migrations',
        'archive/obsolete',
        'scripts',
        'templates_import',
        'database/migrations'
    ]
    
    for dir_path in dirs:
        full_path = BASE_DIR / dir_path
        full_path.mkdir(parents=True, exist_ok=True)
        print(f"✓ Created: {dir_path}/")
    
    # Create README in migrations folder
    migrations_readme = BASE_DIR / 'database' / 'migrations' / 'README.md'
    if not migrations_readme.exists():
        migrations_readme.write_text("""# Database Migrations

This folder contains database migration scripts.

## How to use:
1. Create a new migration file with timestamp: `YYYY_MM_DD_description.py`
2. Run the migration: `python migration_file.py`
3. Document what changed in this README

## Migration History:
- 2026-03-11: Added quantity_requested and quantity_approved columns
- 2026-03-11: Created suppliers table
""")
        print("✓ Created migrations README")

def move_files_to_archive():
    """Move one-time scripts to archive"""
    migration_scripts = [
        'check_invalid_items.py',
        'cleanup_invalid_items.py',
        'compare_offices.py',
        'import_engineering_items.py',
        'import_engineering_office.py',
        'import_excel_items.py',
        'sync_offices.py',
        'check_user.py',
        'test_password.py',
        'verify_engineering_items.py',
        'add_quantity_tracking.py',
        'create_suppliers_table.py',
    ]
    
    for script in migration_scripts:
        src = BASE_DIR / script
        if src.exists():
            dst = BASE_DIR / 'archive' / 'migrations' / script
            shutil.move(str(src), str(dst))
            print(f"✓ Moved: {script} → archive/migrations/")
    
    # Move PHP files to obsolete
    php_files = ['index.php', 'setup_database.php']
    for php_file in php_files:
        src = BASE_DIR / php_file
        if src.exists():
            dst = BASE_DIR / 'archive' / 'obsolete' / php_file
            shutil.move(str(src), str(dst))
            print(f"✓ Moved: {php_file} → archive/obsolete/")

def move_active_scripts():
    """Move active utility scripts to scripts/ folder"""
    active_scripts = [
        'setup_db.py',
        'reset_password.py',
        'check_items.py',
        'verify_db.py',
        'list_all_items.py',
    ]
    
    for script in active_scripts:
        src = BASE_DIR / script
        if src.exists():
            dst = BASE_DIR / 'scripts' / script
            if not dst.exists():  # Don't overwrite if already moved
                shutil.copy2(str(src), str(dst))
                print(f"✓ Copied: {script} → scripts/")

def move_import_templates():
    """Move import templates to dedicated folder"""
    templates = [
        'inventory_import_template.xlsx',
        'inventory_template.xlsx',
    ]
    
    for template in templates:
        src = BASE_DIR / template
        if src.exists():
            dst = BASE_DIR / 'templates_import' / template
            if not dst.exists():
                shutil.copy2(str(src), str(dst))
                print(f"✓ Copied: {template} → templates_import/")

def create_scripts_readme():
    """Create README for scripts folder"""
    readme_path = BASE_DIR / 'scripts' / 'README.md'
    content = """# Utility Scripts

Active scripts for GSO Inventory System administration.

## Scripts:

### setup_db.py
Initialize database with schema and sample data.
```bash
python scripts/setup_db.py
```

### reset_password.py
Reset user password (for admin recovery).
```bash
python scripts/reset_password.py
```

### check_items.py
Validate item data integrity.
```bash
python scripts/check_items.py
```

### verify_db.py
Check database health and connections.
```bash
python scripts/verify_db.py
```

### list_all_items.py
Generate inventory report.
```bash
python scripts/list_all_items.py
```
"""
    readme_path.write_text(content)
    print("✓ Created scripts/README.md")

if __name__ == '__main__':
    print("=" * 70)
    print("GSO Inventory System - File Cleanup")
    print("=" * 70)
    print()
    
    print("📁 Creating organized folder structure...")
    create_directories()
    print()
    
    print("🗑️  Moving obsolete files to archive...")
    move_files_to_archive()
    print()
    
    print("🔧 Organizing active scripts...")
    move_active_scripts()
    create_scripts_readme()
    print()
    
    print("📊 Organizing import templates...")
    move_import_templates()
    print()
    
    print("=" * 70)
    print("✅ Cleanup Complete!")
    print("=" * 70)
    print()
    print("Next steps:")
    print("1. Review CLEANUP_RECOMMENDATIONS.md for detailed analysis")
    print("2. Test all functionality to ensure nothing broke")
    print("3. Commit changes to git")
    print("4. Update README.md with new folder structure")
