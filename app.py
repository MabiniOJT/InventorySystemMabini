"""
Mabini Inventory System - Simplified for GSO
Quick transaction logging + live inventory tracking (no approvals needed)
"""

import os
import random
import json
from datetime import datetime, date
from functools import wraps

from flask import (
    Flask, render_template, request, redirect, url_for,
    session, flash, jsonify, make_response
)
import pymysql
import pymysql.cursors
import bcrypt
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__, static_folder='static', static_url_path='/static')
app.secret_key = os.getenv('SECRET_KEY', 'mabini-inventory-secret-key-change-me')

# ─── Database Configuration ──────────────────────────────────────────────────

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASS', ''),
    'database': os.getenv('DB_NAME', 'mabini_inventory'),
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor,
}

def get_db():
    return pymysql.connect(**DB_CONFIG)


# ─── Authentication ──────────────────────────────────────────────────────────

def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if not session.get('logged_in'):
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated


# ─── Template Filters ────────────────────────────────────────────────────────

@app.template_filter('number_format')
def number_format_filter(value, decimals=0):
    try:
        return f"{float(value):,.{decimals}f}"
    except (ValueError, TypeError):
        return value

@app.template_filter('date_format')
def date_format_filter(value, fmt='%b %d, %Y'):
    if isinstance(value, str):
        try:
            value = datetime.strptime(value, '%Y-%m-%d')
        except ValueError:
            return value
    if isinstance(value, (datetime, date)):
        return value.strftime(fmt)
    return value

@app.context_processor
def inject_now():
    return {'now': datetime.now(), 'today': date.today()}


# ─── Routes ──────────────────────────────────────────────────────────────────

# ---------- Authentication Routes ----------

@app.route('/')
@app.route('/index.php')
def index():
    return render_template('index.html')

@app.route('/login', methods=['POST'])
@app.route('/login_process.php', methods=['POST'])
def login_process():
    username = request.form.get('email', '').strip()
    password = request.form.get('password', '').strip()

    if not username or not password:
        flash('Please enter both username and password.', 'error')
        return redirect(url_for('index'))

    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, username, password, full_name, email, role, status "
                "FROM users WHERE (username=%s OR email=%s) AND status='Active'",
                (username, username),
            )
            user = cur.fetchone()
        conn.close()

        if user and bcrypt.checkpw(password.encode(), user['password'].encode()):
            session['user_id'] = user['id']
            session['username'] = user['username']
            session['user'] = user['full_name'] or user['username']
            session['email'] = user['email'] or ''
            session['role'] = user['role']
            session['logged_in'] = True
            return redirect(url_for('dashboard'))

        flash('Invalid username or password.', 'error')
        return redirect(url_for('index'))

    except Exception as e:
        flash(f'Database error: {str(e)}', 'error')
        return redirect(url_for('index'))


@app.route('/logout', methods=['GET', 'POST'])
@app.route('/logout.php', methods=['GET', 'POST'])
def logout():
    session.clear()
    flash('You have been logged out successfully.', 'success')
    return redirect(url_for('index'))


# ---------- Dashboard ----------

@app.route('/dashboard')
@app.route('/dashboard.php')
@login_required
def dashboard():
    conn = get_db()
    try:
        with conn.cursor() as cur:
            # Get inventory stats
            cur.execute("SELECT COUNT(*) as total FROM items WHERE status='Active'")
            total_items = cur.fetchone()['total']
            
            cur.execute("SELECT COUNT(*) as low_stock FROM items WHERE status='Active' AND quantity_on_hand <= reorder_level")
            low_stock = cur.fetchone()['low_stock']
            
            cur.execute("SELECT SUM(quantity_on_hand * unit_cost) as total_value FROM items WHERE status='Active'")
            total_value = cur.fetchone()['total_value'] or 0
            
            # Get recent transactions
            cur.execute("""
                SELECT it.id, it.transaction_type, it.reference_number, it.quantity, 
                       i.item_name, u.full_name, it.created_at
                FROM inventory_transactions it
                JOIN items i ON it.item_id = i.id
                JOIN users u ON it.created_by = u.id
                ORDER BY it.created_at DESC LIMIT 10
            """)
            recent_transactions = cur.fetchall()
    finally:
        conn.close()
    
    return render_template('dashboard.html',
                          total_items=total_items,
                          low_stock=low_stock,
                          total_value=total_value,
                          recent_transactions=recent_transactions)


# ---------- Inventory Management ----------

@app.route('/inventory')
@app.route('/inventory.php')
@login_required
def inventory():
    """Live inventory view with search and filtering"""
    conn = get_db()
    try:
        # Get filter parameters
        search = request.args.get('search', '').strip()
        category_filter = request.args.get('category', '').strip()
        low_stock_only = request.args.get('low_stock', 'no') == 'yes'
        
        with conn.cursor() as cur:
            # Build query
            query = """
                SELECT i.id, i.item_code, i.item_name, c.category_name, i.unit,
                       i.quantity_on_hand, i.reorder_level, i.unit_cost,
                       (i.quantity_on_hand * i.unit_cost) as total_value,
                       CASE 
                           WHEN i.quantity_on_hand = 0 THEN 'Out of Stock'
                           WHEN i.quantity_on_hand <= i.reorder_level THEN 'Low Stock'
                           ELSE 'Available'
                       END as stock_status,
                       i.updated_at
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.status = 'Active'
            """
            params = []
            
            if search:
                query += " AND (i.item_code LIKE %s OR i.item_name LIKE %s)"
                search_param = f"%{search}%"
                params.extend([search_param, search_param])
            
            if category_filter:
                query += " AND c.id = %s"
                params.append(category_filter)
            
            if low_stock_only:
                query += " AND i.quantity_on_hand <= i.reorder_level"
            
            query += " ORDER BY i.item_name ASC"
            
            cur.execute(query, params)
            items = cur.fetchall()
            
            # Get categories for filter dropdown
            cur.execute("SELECT id, category_name FROM categories WHERE status='Active' ORDER BY category_name")
            categories = cur.fetchall()
    finally:
        conn.close()
    
    return render_template('inventory.html',
                          items=items,
                          categories=categories,
                          search=search,
                          category_filter=category_filter,
                          low_stock_only=low_stock_only)


# ---------- Quick Transaction Logging ----------

@app.route('/log-transaction', methods=['GET', 'POST'])
@login_required
def log_transaction():
    """Quick transaction logging - immediate recording"""
    conn = get_db()
    
    if request.method == 'POST':
        try:
            trans_type = request.form.get('transaction_type', '').upper()
            item_id = int(request.form.get('item_id', 0))
            quantity = int(request.form.get('quantity', 0))
            office_id = request.form.get('office_id') or None
            remarks = request.form.get('remarks', '').strip()
            
            if not trans_type or trans_type not in ['ISSUE', 'RECEIVE', 'ADJUSTMENT', 'RETURN']:
                raise ValueError('Invalid transaction type')
            if not item_id or quantity <= 0:
                raise ValueError('Invalid item or quantity')
            
            user_id = session.get('user_id')
            
            with conn.cursor() as cur:
                # Get item details
                cur.execute(
                    "SELECT id, item_code, item_name, quantity_on_hand, unit_cost FROM items WHERE id=%s",
                    (item_id,)
                )
                item = cur.fetchone()
                if not item:
                    raise ValueError('Item not found')
                
                # Generate reference number
                ref = f"{trans_type[:3]}-{datetime.now():%Y%m%d}-{random.randint(1000,9999)}"
                
                # Create transaction (IMMEDIATE - not pending)
                cur.execute("""
                    INSERT INTO inventory_transactions
                    (transaction_type, transaction_date, transaction_time, reference_number,
                     office_id, item_id, quantity, unit_cost, total_cost, remarks, created_by)
                    VALUES (%s, CURDATE(), CURTIME(), %s, %s, %s, %s, %s, %s, %s, %s)
                """, (
                    trans_type,
                    ref,
                    office_id,
                    item_id,
                    quantity,
                    item['unit_cost'],
                    quantity * float(item['unit_cost']),
                    remarks,
                    user_id
                ))
                txn_id = cur.lastrowid
                
                # Update inventory
                if trans_type == 'ISSUE':
                    if item['quantity_on_hand'] < quantity:
                        raise ValueError(f"Insufficient stock. Available: {item['quantity_on_hand']}")
                    cur.execute("UPDATE items SET quantity_on_hand = quantity_on_hand - %s, updated_at = NOW() WHERE id = %s",
                               (quantity, item_id))
                    movement_type = 'OUT'
                
                elif trans_type == 'RECEIVE':
                    cur.execute("UPDATE items SET quantity_on_hand = quantity_on_hand + %s, updated_at = NOW() WHERE id = %s",
                               (quantity, item_id))
                    movement_type = 'IN'
                
                elif trans_type == 'ADJUSTMENT':
                    # Adjustment can be positive or negative
                    cur.execute("UPDATE items SET quantity_on_hand = GREATEST(0, quantity_on_hand + %s), updated_at = NOW() WHERE id = %s",
                               (quantity, item_id))
                    movement_type = 'ADJUST'
                
                elif trans_type == 'RETURN':
                    cur.execute("UPDATE items SET quantity_on_hand = quantity_on_hand + %s, updated_at = NOW() WHERE id = %s",
                               (quantity, item_id))
                    movement_type = 'IN'
                
                # Get new balance
                cur.execute("SELECT quantity_on_hand FROM items WHERE id = %s", (item_id,))
                new_balance = cur.fetchone()['quantity_on_hand']
                
                # Log stock movement
                cur.execute("""
                    INSERT INTO stock_movements
                    (item_id, transaction_id, movement_type, quantity, balance_after,
                     from_office_id, to_office_id, remarks, created_by)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
                """, (
                    item_id,
                    txn_id,
                    movement_type,
                    quantity,
                    new_balance,
                    office_id if trans_type == 'ISSUE' else None,
                    office_id if trans_type in ['RECEIVE', 'RETURN'] else None,
                    remarks,
                    user_id
                ))
            
            conn.commit()
            flash(f"{trans_type} transaction logged successfully! Reference: {ref}", 'success')
            return redirect(url_for('transaction_history'))
        
        except ValueError as e:
            flash(f"Validation error: {str(e)}", 'error')
        except Exception as e:
            conn.rollback()
            flash(f"Error logging transaction: {str(e)}", 'error')
        finally:
            conn.close()
    
    # GET - show form
    conn = get_db()
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT id, item_code, item_name, quantity_on_hand FROM items WHERE status='Active' ORDER BY item_name")
            items = cur.fetchall()
            cur.execute("SELECT id, office_name FROM offices WHERE status='Active' ORDER BY office_name")
            offices = cur.fetchall()
    finally:
        conn.close()
    
    return render_template('log_transaction.html', items=items, offices=offices)


# ---------- Transaction History / Audit Log ----------

@app.route('/transaction-history')
@app.route('/transactions.php')
@login_required
def transaction_history():
    """View all transactions with audit trail"""
    conn = get_db()
    try:
        # Get filters
        trans_type = request.args.get('type', 'all')
        days = int(request.args.get('days', 30))
        
        with conn.cursor() as cur:
            query = """
                SELECT it.id, it.transaction_type, it.reference_number, it.transaction_date,
                       it.quantity, i.item_code, i.item_name, o.office_name, u.full_name as logged_by,
                       it.remarks, sm.balance_after as new_balance, it.created_at
                FROM inventory_transactions it
                JOIN items i ON it.item_id = i.id
                JOIN users u ON it.created_by = u.id
                LEFT JOIN offices o ON it.office_id = o.id
                LEFT JOIN stock_movements sm ON sm.transaction_id = it.id
                WHERE it.created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            """
            params = [days]
            
            if trans_type != 'all':
                query += " AND it.transaction_type = %s"
                params.append(trans_type)
            
            query += " ORDER BY it.created_at DESC LIMIT 100"
            cur.execute(query, params)
            transactions = cur.fetchall()
    finally:
        conn.close()
    
    return render_template('transaction_history.html',
                          transactions=transactions,
                          trans_type=trans_type,
                          days=days)


# ---------- Item Management ----------

@app.route('/items')
@app.route('/item-master-list.php')
@app.route('/item-master-list')
@login_required
def item_master_list():
    """Manage items - add, edit, delete"""
    conn = get_db()
    
    if request.method == 'POST':
        try:
            action = request.form.get('action', '')
            
            with conn.cursor() as cur:
                if action == 'add':
                    cur.execute("""
                        INSERT INTO items
                        (item_code, item_name, category_id, unit, unit_cost,
                         quantity_on_hand, reorder_level, status, created_by)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, 'Active', %s)
                    """, (
                        request.form.get('item_code'),
                        request.form.get('item_name'),
                        request.form.get('category_id') or None,
                        request.form.get('unit', 'piece'),
                        float(request.form.get('unit_cost', 0)),
                        int(request.form.get('quantity_on_hand', 0)),
                        int(request.form.get('reorder_level', 10)),
                        session.get('user_id')
                    ))
                    flash('Item added successfully!', 'success')
                
                elif action == 'edit':
                    item_id = int(request.form.get('item_id'))
                    cur.execute("""
                        UPDATE items SET
                        item_code=%s, item_name=%s, category_id=%s, unit=%s,
                        unit_cost=%s, reorder_level=%s, updated_at=NOW()
                        WHERE id=%s
                    """, (
                        request.form.get('item_code'),
                        request.form.get('item_name'),
                        request.form.get('category_id') or None,
                        request.form.get('unit', 'piece'),
                        float(request.form.get('unit_cost', 0)),
                        int(request.form.get('reorder_level', 10)),
                        item_id
                    ))
                    flash('Item updated successfully!', 'success')
                
                elif action == 'delete':
                    item_id = int(request.form.get('item_id'))
                    cur.execute("UPDATE items SET status='Inactive' WHERE id=%s", (item_id,))
                    flash('Item deactivated successfully!', 'success')
            
            conn.commit()
        except Exception as e:
            conn.rollback()
            flash(f"Error: {str(e)}", 'error')
        finally:
            conn.close()
        
        return redirect(url_for('item_master_list'))
    
    # GET - Handle AJAX requests for item details
    action = request.args.get('action', '')
    if action == 'get_item_details':
        item_id = request.args.get('id')
        try:
            with conn.cursor() as cur:
                cur.execute("""
                    SELECT i.*, c.category_name FROM items i
                    LEFT JOIN categories c ON i.category_id = c.id
                    WHERE i.id = %s
                """, (item_id,))
                item = cur.fetchone()
                
                if not item:
                    return jsonify({'error': 'Item not found'}), 404
                
                # Get recent transactions for this item
                cur.execute("""
                    SELECT st.id, st.transaction_type, st.quantity, st.created_at,
                           st.remarks, o1.office_name as from_office, o2.office_name as to_office
                    FROM stock_movements st
                    LEFT JOIN offices o1 ON st.from_office_id = o1.id
                    LEFT JOIN offices o2 ON st.to_office_id = o2.id
                    WHERE st.item_id = %s
                    ORDER BY st.created_at DESC
                    LIMIT 10
                """, (item_id,))
                movements = cur.fetchall()
                
                return jsonify({
                    'item': item,
                    'movements': movements
                })
        except Exception as e:
            return jsonify({'error': str(e)}), 500
        finally:
            conn.close()
    
    # GET - Regular page load
    try:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT i.*, c.category_name FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.status = 'Active'
                ORDER BY i.item_name
            """)
            items = cur.fetchall()
            
            cur.execute("SELECT id, category_name FROM categories WHERE status='Active'")
            categories = cur.fetchall()
            
            cur.execute("SELECT id, office_name FROM offices WHERE status='Active'")
            offices = cur.fetchall()
            
            # Get statistics
            cur.execute("SELECT COUNT(*) as count FROM items WHERE status='Active'")
            total_items = cur.fetchone()['count'] or 0
            
            cur.execute("""
                SELECT COUNT(*) as count FROM items 
                WHERE status='Active' AND quantity_on_hand <= reorder_level
            """)
            low_stock = cur.fetchone()['count'] or 0
            
            cur.execute("""
                SELECT SUM(quantity_on_hand * unit_cost) as total 
                FROM items WHERE status='Active'
            """)
            total_value_row = cur.fetchone()
            total_value = total_value_row['total'] or 0 if total_value_row['total'] else 0
    finally:
        conn.close()
    
    return render_template('item_master_list.html', items=items, categories=categories, offices=offices,
                         total_items=total_items, low_stock=low_stock, total_value=total_value)


# ---------- Offices / Departments ----------

@app.route('/offices')
@app.route('/offices.php')
@login_required
def offices():
    """Manage offices/departments"""
    conn = get_db()
    
    # Handle AJAX requests for office items
    if request.method == 'GET':
        office_id = request.args.get('office_id')
        if office_id:
            try:
                with conn.cursor() as cur:
                    # Get all items that have been received by this office
                    cur.execute("""
                        SELECT DISTINCT i.id, i.item_code, i.item_name, i.unit,
                               SUM(it.quantity) as total_received, 
                               c.category_name,
                               MIN(it.transaction_date) as first_received,
                               MAX(it.transaction_date) as last_received
                        FROM inventory_transactions it
                        JOIN items i ON it.item_id = i.id
                        LEFT JOIN categories c ON i.category_id = c.id
                        WHERE it.office_id = %s
                        AND it.transaction_type IN ('Issue', 'Receive')
                        GROUP BY i.id, i.item_code, i.item_name, i.unit, c.category_name
                        ORDER BY i.item_name
                    """, (office_id,))
                    items = cur.fetchall()
                    
                    return jsonify({'items': items})
            except Exception as e:
                return jsonify({'error': str(e)}), 500
            finally:
                conn.close()
    
    if request.method == 'POST':
        try:
            action = request.form.get('action', '')
            
            with conn.cursor() as cur:
                if action == 'add':
                    cur.execute("""
                        INSERT INTO offices (office_code, office_name, status)
                        VALUES (%s, %s, 'Active')
                    """, (
                        request.form.get('office_code'),
                        request.form.get('office_name')
                    ))
                    flash('Office added successfully!', 'success')
                
                elif action == 'edit':
                    office_id = int(request.form.get('office_id'))
                    cur.execute("""
                        UPDATE offices SET
                        office_code=%s, office_name=%s, updated_at=NOW()
                        WHERE id=%s
                    """, (
                        request.form.get('office_code'),
                        request.form.get('office_name'),
                        office_id
                    ))
                    flash('Office updated successfully!', 'success')
                
                elif action == 'delete':
                    office_id = int(request.form.get('office_id'))
                    cur.execute("UPDATE offices SET status='Inactive' WHERE id=%s", (office_id,))
                    flash('Office deactivated successfully!', 'success')
            
            conn.commit()
        except Exception as e:
            conn.rollback()
            flash(f"Error: {str(e)}", 'error')
        finally:
            conn.close()
        
        return redirect(url_for('offices'))
    
    # GET - Regular page load
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM offices WHERE status='Active' ORDER BY office_name")
            offices_list = cur.fetchall()
    finally:
        conn.close()
    
    return render_template('offices.html', offices=offices_list)


# ---------- API Endpoints ----------

@app.route('/api/item/<int:item_id>')
@login_required
def api_item_details(item_id):
    """Get item details for AJAX"""
    conn = get_db()
    try:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT i.*, c.category_name FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.id = %s
            """, (item_id,))
            item = cur.fetchone()
        
        if not item:
            return jsonify(error='Item not found'), 404
        
        return jsonify(item)
    finally:
        conn.close()


@app.route('/api/low-stock-items')
@login_required
def api_low_stock():
    """Get list of low stock items"""
    conn = get_db()
    try:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT id, item_code, item_name, quantity_on_hand, reorder_level
                FROM items
                WHERE status='Active' AND quantity_on_hand <= reorder_level
                ORDER BY quantity_on_hand ASC
            """)
            items = cur.fetchall()
        return jsonify(items)
    finally:
        conn.close()


if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)
